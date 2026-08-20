<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{

public function register(Request $request): JsonResponse
{
    try {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        $otp = $this->generateOtp(6);
        $resendSeconds = config('pairi_family.otp_resend_seconds', 45);

        Cache::put(
            $this->pendingRegistrationCacheKey($request->email),
            [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password,
                'referral_code' => $request->referral_code,

                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(10)->toDateTimeString(),
                'otp_resend_available_at' => now()->addSeconds($resendSeconds)->toDateTimeString(),
            ],
            now()->addMinutes(30)
        );

        $this->sendOtpEmail($request->email, $otp, 'Email Verification - Piyari Family');

        return response()->json([
            'success' => 200,
            'message' => 'OTP sent to your email. Please verify to complete registration.',
            'data' => [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'referral_code' => $request->referral_code,
            ],
            'resend_after_seconds' => $resendSeconds,
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->validator->errors()->first(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Registration failed.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

public function registerComplete(Request $request): JsonResponse
{
    try {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        $referrerId = null;
        if (!empty($request->referral_code)) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            if ($referrer) {
                $referrerId = $referrer->id;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'is_verified' => true,
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
            'status' => 'active',
            'referred_by' => $referrerId,
        ]);

        Cache::forget($this->pendingRegistrationCacheKey($request->email));

        $this->processReferralReward($user);

        return response()->json([
            'success' => 200,
            'message' => 'OTP verified successfully.',
            'user' => UserResource::toPayload($user->fresh()),
            'token' => $user->createToken('auth')->plainTextToken,
        ], 201);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->validator->errors()->first(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Registration failed.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
    public function verifyEmailOtp(Request $request): JsonResponse
{
    try {

        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $cacheKey = $this->pendingRegistrationCacheKey($request->email);

        $pendingRegistration = Cache::get($cacheKey);

        \Log::info('OTP verification attempt', [
            'email' => $request->email,
            'cache_key' => $cacheKey,
            'pending_registration' => $pendingRegistration,
            'request_otp' => $request->otp,
        ]);

        if (!$pendingRegistration) {
            return response()->json([
                'success' => false,
                'message' => 'Registration session expired. Please register again.',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Check OTP
        |--------------------------------------------------------------------------
        */

        if (
            !isset($pendingRegistration['otp']) ||
            (string) $pendingRegistration['otp'] !== (string) $request->otp
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Check OTP expiry
        |--------------------------------------------------------------------------
        */

        if (!empty($pendingRegistration['otp_expires_at'])) {

            $expiresAt = \Carbon\Carbon::parse(
                $pendingRegistration['otp_expires_at']
            );

            if ($expiresAt->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new OTP.',
                ], 400);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Check duplicate email
        |--------------------------------------------------------------------------
        */

        if (User::where('email', $pendingRegistration['email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already registered.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Check duplicate phone
        |--------------------------------------------------------------------------
        */

        if (User::where('phone', $pendingRegistration['phone'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is already registered.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Referral
        |--------------------------------------------------------------------------
        */

        $referrerId = null;

        if (!empty($pendingRegistration['referral_code'])) {

            $referrer = User::where(
                'referral_code',
                $pendingRegistration['referral_code']
            )->first();

            if ($referrer) {
                $referrerId = $referrer->id;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create User
        |--------------------------------------------------------------------------
        */

        $user = User::create([
            'name' => $pendingRegistration['name'],
            'email' => $pendingRegistration['email'],
            'phone' => $pendingRegistration['phone'],
            'password' => $pendingRegistration['password'],
            'is_verified' => true,
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
            'status' => 'active',
            'referred_by' => $referrerId,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Remove pending registration
        |--------------------------------------------------------------------------
        */

        Cache::forget($cacheKey);

        /*
        |--------------------------------------------------------------------------
        | Referral Reward
        |--------------------------------------------------------------------------
        */

        $this->processReferralReward($user);

        /*
        |--------------------------------------------------------------------------
        | Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'success' => 200,
            'message' => 'Email verified successfully. Registration completed.',
            'user' => UserResource::toPayload($user->fresh()),
            'token' => $token,
        ], 201);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->validator->errors()->first(),
            'errors' => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {

        \Log::error('Failed to verify email OTP', [
            'email' => $request->email ?? null,
            'otp' => $request->otp ?? null,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to verify email OTP.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

   public function resendEmailOtp(Request $request): JsonResponse
{
    try {
        Log::info('Resend OTP request received.', [
            'email' => $request->email
        ]);

        $request->validate([
            'email' => 'required|email'
        ]);

        $resendSeconds = config('pairi_family.otp_resend_seconds', 45);

        // Pending registration (not yet stored in users table)
        $cacheKey = $this->pendingRegistrationCacheKey($request->email);
        $pending = Cache::get($cacheKey);

        if ($pending) {
            if (!empty($pending['otp_resend_available_at']) && now()->lessThan($pending['otp_resend_available_at'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting a new code.',
                    'resend_after_seconds' => now()->diffInSeconds($pending['otp_resend_available_at']),
                ], 429);
            }

            $otp = $this->generateOtp(6);
            $pending['otp'] = $otp;
            $pending['otp_expires_at'] = now()->addMinutes(10);
            $pending['otp_resend_available_at'] = now()->addSeconds($resendSeconds);

            Cache::put($cacheKey, $pending, now()->addMinutes(30));

            $this->sendOtpEmail($request->email, $otp, 'Email Verification - Pairi Family');

            return response()->json([
                'success' => 200,
                'message' => 'OTP resent to your email.',
                'resend_after_seconds' => $resendSeconds,
            ], 200);
        }

        // Fallback: existing (already registered) unverified user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No pending registration or account found for this email.'
            ], 404);
        }

        Log::info('User found.', [
            'user_id' => $user->id,
            'email' => $user->email,
            'is_verified' => $user->is_verified
        ]);

        if ($user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified.'
            ], 400);
        }

        if ($user->otp_resend_available_at && $user->otp_resend_available_at->isFuture()) {
            Log::info('OTP resend blocked due to timer.', [
                'available_at' => $user->otp_resend_available_at
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new code.',
                'resend_after_seconds' => now()->diffInSeconds($user->otp_resend_available_at),
            ], 429);
        }

        $otp = $this->generateOtp(6);

        Log::info('Generated OTP.', [
            'otp' => $otp
        ]);

        $user->update([
            'otp' => $otp,
            'email_otp_expires_at' => now()->addMinutes(10),
            'otp_resend_available_at' => now()->addSeconds($resendSeconds),
        ]);

        Log::info('User OTP updated in database.');

        Log::info('Sending OTP email...', [
            'email' => $user->email
        ]);

        $this->sendOtpEmail($user->email, $otp, 'Email Verification - Pairi Family');

        Log::info('OTP email sent successfully.');

        return response()->json([
            'success' => 200,
            'message' => 'OTP resent to your email.',
            'resend_after_seconds' => $resendSeconds,
        ], 200);

    } catch (ValidationException $e) {

        Log::error('Validation Error', [
            'errors' => $e->errors()
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->validator->errors()->first(),
        ], 422);

    } catch (\Exception $e) {

        Log::error('Resend OTP Error', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to resend OTP.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    public function login(Request $request): JsonResponse
    {
        try {
           

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
            }

            if ($user->marriage_bureau_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This profile was created by a marriage bureau and cannot log in to the app. Please contact the bureau or create your own account.',
                ], 403);
            }

            if ($user->status !== 'active') {
                return response()->json(['success' => false, 'message' => 'Your account is inactive.'], 403);
            }

            if (!$user->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email first.',
                    'requires_verification' => true,
                    'email' => $user->email,
                ], 403);
            }

            return response()->json([
                'success' => 200,
                'message' => 'Logged in successfully.',
                'user' => UserResource::toPayload($user),
                'token' => $user->createToken('auth')->plainTextToken,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

  public function forgotPassword(Request $request): JsonResponse
{
    try {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        
        // Generate 6-digit OTP
        $otp = $this->ForgetgenerateOtp(6);
        $resendSeconds = config('pairi_family.otp_resend_seconds', 45);

        // Update user with OTP
        $user->otp = $otp;
        $user->email_otp_expires_at = now()->addMinutes(10);
        $user->reset_code_verified = false;
        $user->otp_resend_available_at = now()->addSeconds($resendSeconds);
		$user->is_verified = 0; // Reset verification status
        $user->save();

        // Send OTP email
        $this->sendOtpEmail($user->email, $otp, 'Password Reset - Pairi Family');

        return response()->json([
            'success' => 200,
            'message' => 'Reset code sent to your email.',
            'resend_after_seconds' => $resendSeconds,
        ], 200);
        
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false, 
            'message' => $e->getMessage(), 
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Forgot password request failed.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

/**
 * Generate OTP with specified length
 */
private function ForgetgenerateOtp($length = 6): string
{
    // Generate random 6-digit number
    return str_pad((string) random_int(0, 999999), $length, '0', STR_PAD_LEFT);
}



public function verifyResetOtp(Request $request): JsonResponse
{
    try {

        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Check OTP
        if (empty($user->otp) || $user->otp != $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP code.',
            ], 400);
        }

        // Check expiry (10 minutes)
        if (
            !empty($user->reset_token_expires_at) &&
            Carbon::parse($user->reset_token_expires_at)->lt(now())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 400);
        }

        // Mark OTP verified
        $user->update([
            'reset_code_verified' => true,
            'is_verified' => 1,
        ]);

        return response()->json([
            'success' => 200,
            'message' => 'OTP verified successfully. You can now reset your password.',
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->validator->errors()->first(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'OTP verification failed.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

    public function setNewPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user->reset_code_verified || ($user->reset_token_expires_at && $user->reset_token_expires_at->isPast())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your reset code first.',
                ], 400);
            }

            $user->update([
                'password' => Hash::make($request->password),
                'reset_otp' => null,
                'reset_token_expires_at' => null,
                'reset_code_verified' => false,
                'reset_password_token' => null,
            ]);

            return response()->json([
                'success' => 200,
                'message' => 'Password updated successfully.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Set new password failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
				]);

            $user = User::where('email', $request->email)->first();

            if ($user->reset_otp !== $request->otp || ($user->reset_token_expires_at && $user->reset_token_expires_at->isPast())) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired code.'], 400);
            }

            $user->update([
                'password' => Hash::make($request->password),
                'reset_otp' => null,
                'reset_token_expires_at' => null,
                'reset_code_verified' => false,
                'reset_password_token' => null,
            ]);

            return response()->json(['success' => 200, 'message' => 'Password reset successfully.'], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
{
    try {

        $user = Auth::user();

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 400);
        }

        // Check if new password is same as current password
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'This is your current password. Please enter a new password.'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => 200,
            'message' => 'Password changed successfully.'
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->validator->errors()->first(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Password change failed.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    public function sendPhoneOtp(Request $request): JsonResponse
    {
        try {

            $user = Auth::user();
			 if ($user->phone_verified === 1) {
            return response()->json([
                'success' => false, 
                'message' => 'Phone is already verified.'
            ], 400);
        }
            $resendSeconds = config('pairi_family.otp_resend_seconds', 45);

            if ($user->phone_otp_resend_available_at && $user->phone_otp_resend_available_at->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting a new code.',
                    'resend_after_seconds' => now()->diffInSeconds($user->phone_otp_resend_available_at),
                ], 429);
            }

            $otp = $this->generateOtp(6);

            $user->update([
                'phone' => $request->phone,
                'phone_otp' => $otp,
                'phone_otp_expires_at' => now()->addMinutes(10),
                'phone_otp_resend_available_at' => now()->addSeconds($resendSeconds),
                'phone_verified' => false,
            ]);

            $response = [
                'success' => 200,
                'message' => 'Verification code sent to your phone.',
                'resend_after_seconds' => $resendSeconds,
            ];

            if (config('app.debug')) {
                $response['debug_otp'] = $otp;
            }

            return response()->json($response, 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Send phone OTP failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function resendPhoneOtp(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $resendSeconds = config('pairi_family.otp_resend_seconds', 45);

            if ($user->phone_verified) {
                return response()->json(['success' => false, 'message' => 'Phone is already verified.'], 400);
            }

            if ($user->phone_otp_resend_available_at && $user->phone_otp_resend_available_at->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting a new code.',
                    'resend_after_seconds' => now()->diffInSeconds($user->phone_otp_resend_available_at),
                ], 429);
            }

            $otp = $this->generateOtp(6);

            $user->update([
                'phone_otp' => $otp,
                'phone_otp_expires_at' => now()->addMinutes(10),
                'phone_otp_resend_available_at' => now()->addSeconds($resendSeconds),
            ]);

            $response = [
                'success' => 200,
                'message' => 'Verification code resent.',
                'resend_after_seconds' => $resendSeconds,
            ];

            if (config('app.debug')) {
                $response['debug_otp'] = $otp;
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resend phone OTP failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

   public function verifyPhoneOtp(Request $request): JsonResponse
{
    try {
        $user = Auth::user();

        // Remove this line - it's causing the error
        // return $user->phone_verified;

        if ($user->phone_verified === 1) {
            return response()->json([
                'success' => false, 
                'message' => 'Phone is already verified.'
            ], 400);
        }

        // Validate OTP
        $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        if ($user->phone_otp !== $request->otp || 
            ($user->phone_otp_expires_at && $user->phone_otp_expires_at->isPast())) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid or expired code.'
            ], 400);
        }

        $user->update([
            'phone_verified' => true,
            'phone_otp' => null,
            'phone_otp_expires_at' => null,
            'phone_otp_resend_available_at' => null,
        ]);

        return response()->json([
            'success' => 200,
            'message' => 'Number verified! Your profile has been verified successfully.',
            'user' => UserResource::toPayload($user->fresh()),
        ], 200);
        
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false, 
            'message' => $e->getMessage(), 
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Verify phone OTP failed.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}



public function profile(Request $request): JsonResponse
{
    try {
        $user = $request->user();

        $photos = collect($user->photos ?? [])->map(fn ($photo) => [
            'url' => media_url($photo['path'] ?? null),
            'path' => $photo['path'] ?? null,
            'is_main' => (bool) ($photo['is_main'] ?? false),
        ])->values()->all();

        // Get main photo URL
        $mainPhoto = null;
        foreach ($user->photos ?? [] as $photo) {
            if (isset($photo['is_main']) && $photo['is_main'] === true) {
                $mainPhoto = media_url($photo['path'] ?? null);
                break;
            }
        }
        // If no main photo, use first photo
        if (!$mainPhoto && !empty($photos)) {
            $mainPhoto = $photos[0]['url'] ?? null;
        }
        // Fallback to profile_photo column
        if (!$mainPhoto) {
            $mainPhoto = $user->profile_photo ?? null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'         => $user->id,
                'name'       => $user->name,  // ✅ Added name
                'image'      => $mainPhoto,   // ✅ Main profile photo
                'photos'     => $photos,
                'education'  => $user->qualification,
                'career'     => $user->job_title,
                'religion'   => $user->religion,
                'bio'        => $user->bio,
                'age'        => $user->birthday
                    ? Carbon::parse($user->birthday)->age
                    : null,
                'height'     => $user->height,
                'sect'       => $user->sect,
                'language'   => $user->mother_tongue,
                'interests'  => $user->interests,  // ✅ Fixed key name
                'city'       => $user->city,
                'country'    => $user->country,
                'community'  => $user->community,   // ✅ Added
                'gender'     => $user->gender,       // ✅ Added
                'marital_status' => $user->marital_status, // ✅ Added
                'profile_step' => $user->profile_step,     // ✅ Added
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Fetch profile failed.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['success' => 200, 'message' => 'Logged out successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function pendingRegistrationCacheKey(string $email): string
    {
        return 'pending_registration_' . strtolower(trim($email));
    }

    private function generateOtp(int $length): string
    {
        $max = (10 ** $length) - 1;
        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function sendOtpEmail(string $email, string $otp, string $subject): void
    {
        try {
            $logoUrl = url('assets/img/piyari_logo.png');
            $heading = str_contains($subject, 'Password Reset') ? 'Password Reset Code' : 'Email Verification';

            Mail::send('emails.otp', [
                'subject' => $subject,
                'heading' => $heading,
                'logoUrl' => $logoUrl,
                'otp' => $otp,
                'greeting' => 'Dear User,',
                'messageLine' => str_contains($subject, 'Password Reset')
                    ? 'We received a request to reset your password. Use the code below to continue:'
                    : 'Thank you for joining Piyari Family. Use the verification code below to complete your registration:',
            ], function ($mail) use ($email, $subject) {
                $mail->to($email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), 'Piyari Family');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email.', [
                'email' => $email,
                'subject' => $subject,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function processReferralReward(User $user): void
    {
        if (!$user->referred_by || $user->referred_by === $user->id) {
            return;
        }

        if (\App\Models\Referral::where('referred_user_id', $user->id)->exists()) {
            return;
        }

        $referrer = User::find($user->referred_by);
        if (!$referrer) {
            return;
        }

        $points = (int) \App\Models\SystemSetting::getVal('invite_reward_points', 50);

        \App\Models\Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'points_earned' => $points,
        ]);

        $referrer->increment('reward_points', $points);
    }
}
