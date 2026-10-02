<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\SocialiteCredential;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {

            try {
                $socialUser = Socialite::driver($request->provider)->stateless()->userFromToken($request->token);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid social login token.',
                ], 401);
            }

            $email = $socialUser->getEmail() ?: $request->email;
            $name = $socialUser->getName() ?: $request->name ?: 'Pairi Family User';

            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email is required for social login.',
                ], 422);
            }

            $user = User::where('email', $email)->first();

            if ($user && ($user->marriage_bureau_id || !($user->can_login ?? true))) {
                return response()->json([
                    'success' => false,
                    'message' => 'This profile was created by a marriage bureau and cannot log in to the app. Please contact the bureau or create your own account.',
                ], 403);
            }

            if ($user && $user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact the admin.',
                ], 403);
            }

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'is_verified' => true,
                    'email_verified_at' => now(),
                    'terms_accepted_at' => now(),
                    'social_provider' => $request->provider,
                    'social_id' => $socialUser->getId(),
                    'status' => 'active',
                ]);
            } else {
                $user->update([
                    'social_provider' => $request->provider,
                    'social_id' => $socialUser->getId(),
                    'is_verified' => true,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            SocialiteCredential::updateOrCreate(
                ['user_id' => $user->id, 'provider_name' => $request->provider],
                ['provider_id' => $socialUser->getId(), 'access_token' => $request->token]
            );

            return response()->json([
                'success' => 200,
                'message' => 'Logged in successfully.',
                'user' => UserResource::toPayload($user->fresh()),
                'token' => $user->createToken('auth')->plainTextToken,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to login with social provider.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
