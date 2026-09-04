<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ProfileInterest;
use App\Models\Referral;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->loadMissing([]);
            $activeSub = $user->activeSubscription();

            return response()->json([
                'success' => 200,
                'user' => UserResource::toPayload($user),
                'subscription' => $activeSub ? [
                    'plan_name' => $activeSub->plan->name ?? 'Free',
                    'plan_type' => $activeSub->plan->type ?? 'Free',
                    'status' => $activeSub->status,
                    'is_active' => $activeSub->isActive(),
                    'expires_at' => $activeSub->expires_at?->format('d M Y'),
                    'membership_badge' => app(\App\Services\SubscriptionAccessService::class)->membershipBadge($user),
                ] : ['plan_name' => 'Free', 'plan_type' => 'Free', 'status' => 'free', 'is_active' => true, 'membership_badge' => null],
                'refer_and_earn' => [
                    'title' => 'Refer & Earn',
                    'subtitle' => 'Invite friends and earn rewards',
                    'referral_link' => referral_link($user->ensureReferralCode()),
                    'reward_points' => $user->reward_points,
                ],
                'visibility' => [
                    'profile_photo_visible' => (bool) $user->profile_photo_visible,
                    'additional_photos_visible' => (bool) $user->additional_photos_visible,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateVisibility(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'profile_photo_visible' => 'sometimes|boolean',
                'additional_photos_visible' => 'sometimes|boolean',
            ]);

            $user = $request->user();
            $user->update($data);

            return response()->json([
                'success' => 200,
                'message' => 'Visibility settings updated.',
                'visibility' => [
                    'profile_photo_visible' => (bool) $user->profile_photo_visible,
                    'additional_photos_visible' => (bool) $user->additional_photos_visible,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update visibility',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deactivate(Request $request): JsonResponse
    {
        try {
            $request->user()->update(['status' => 'inactive']);
            $request->user()->tokens()->delete();

            return response()->json([
                'success' => 200,
                'message' => 'Account deactivated. Your profile is hidden.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate account',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'password' => 'required|string',
                'confirm' => 'required|accepted',
            ]);

            $user = $request->user();

            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['success' => false, 'message' => 'Incorrect password.'], 422);
            }

            $user->tokens()->delete();
            $user->update([
                'status' => 'deleted',
                'email' => 'deleted_' . $user->id . '_' . $user->email,
            ]);

            return response()->json([
                'success' => 200,
                'message' => 'Account deleted successfully.',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function notifications(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $notifications = [];

            $likedMeCount = ProfileInterest::where('to_user_id', $user->id)
                ->where('action', 'interest')
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            if ($likedMeCount > 0) {
                $notifications[] = [
                    'type' => 'interest_received',
                    'title' => 'Interest Received',
                    'message' => "You received interest from {$likedMeCount} profile(s) this week.",
                    'created_at' => now()->toDateTimeString(),
                ];
            }

            $activeSub = $user->activeSubscription();
            if (!$activeSub || $activeSub->plan?->type === 'Free') {
                $notifications[] = [
                    'type' => 'package_reminder',
                    'title' => 'Upgrade to Premium',
                    'message' => 'Upgrade your package to unlock unlimited chats and see who liked you.',
                    'action' => 'upgrade_now',
                    'created_at' => now()->toDateTimeString(),
                ];
            }

            if ($activeSub && $activeSub->expires_at && $activeSub->expires_at->lte(now()->addDays(3))) {
                $notifications[] = [
                    'type' => 'package_expiring',
                    'title' => 'Package Expiring',
                    'message' => 'Your subscription is ending on ' . $activeSub->expires_at->format('d M Y') . '.',
                    'created_at' => now()->toDateTimeString(),
                ];
            }

            if (!$user->profile_completed) {
                $notifications[] = [
                    'type' => 'complete_profile',
                    'title' => 'Complete Profile',
                    'message' => 'Complete your profile to get better matches.',
                    'created_at' => now()->toDateTimeString(),
                ];
            }

            $referralCount = Referral::where('referrer_id', $user->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            if ($referralCount > 0) {
                $points = (int) SystemSetting::getVal('invite_reward_points', 50) * $referralCount;
                $notifications[] = [
                    'type' => 'referral_reward',
                    'title' => 'Referral Reward',
                    'message' => "You earned {$points} points from {$referralCount} new registration(s).",
                    'created_at' => now()->toDateTimeString(),
                ];
            }

            return response()->json([
                'success' => 200,
                'notifications' => $notifications,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
