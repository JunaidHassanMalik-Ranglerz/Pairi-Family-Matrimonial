<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileCardResource;
use App\Models\ProfileInterest;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\MatchService;
use App\Services\SubscriptionAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PremiumFeatureController extends Controller
{
    public function __construct(
        private SubscriptionAccessService $accessService,
        private MatchService $matchService
    ) {
    }

    public function boost(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->accessService->applyBoost($user);

            return response()->json([
                'success' => 200,
                'message' => 'Profile boost activated.',
                'profile_boost_until' => $result['profile_boost_until'],
                'boost_duration_hours' => $result['boost_duration_hours'],
                'access' => $result['access'],
            ], 200);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate boost.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function superLike(Request $request, User $user): JsonResponse
    {
        try {
            $sender = $request->user();

            if ($user->id === $sender->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot super like yourself.',
                ], 422);
            }

            if ($user->status !== 'active' || !$user->profile_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not available.',
                ], 404);
            }

            $opposite = $this->matchService->oppositeGender($sender->gender);
            if ($opposite && $user->gender !== $opposite) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only super like opposite gender profiles.',
                ], 422);
            }

            $existing = ProfileInterest::query()
                ->where('from_user_id', $sender->id)
                ->where('to_user_id', $user->id)
                ->first();

            if ($existing && $existing->action === 'super_like') {
                return response()->json([
                    'success' => 200,
                    'message' => 'You already super liked this profile.',
                    'shortlisted' => true,
                    'super_liked' => true,
                    'access' => $this->accessService->access($sender),
                ], 200);
            }

            $this->accessService->assertCan($sender, 'super_like');
            $this->accessService->consume($sender, SubscriptionAccessService::FEATURE_SUPER_LIKE);

            $interest = ProfileInterest::updateOrCreate(
                [
                    'from_user_id' => $sender->id,
                    'to_user_id' => $user->id,
                ],
                ['action' => 'super_like']
            );

            UserNotification::create([
                'user_id' => $user->id,
                'title' => 'Super Like',
                'message' => "{$sender->name} super liked your profile.",
            ]);

            $mutual = ProfileInterest::query()
                ->where('from_user_id', $user->id)
                ->where('to_user_id', $sender->id)
                ->whereIn('action', ['interest', 'super_like'])
                ->exists();

            return response()->json([
                'success' => 200,
                'message' => 'Super like sent successfully.',
                'shortlisted' => true,
                'super_liked' => true,
                'mutual_match' => $mutual,
                'interest_id' => $interest->id,
                'access' => $this->accessService->access($sender),
                'profile' => ProfileCardResource::make($user),
            ], 200);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send super like.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function startChat(Request $request, User $user): JsonResponse
    {
        try {
            $starter = $request->user();

            if ($user->id === $starter->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot start a chat with yourself.',
                ], 422);
            }

            if ($user->status !== 'active' || !$user->profile_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not available.',
                ], 404);
            }

            $result = $this->accessService->startChat($starter, $user);

            return response()->json([
                'success' => 200,
                'message' => $result['created']
                    ? 'Chat started successfully.'
                    : 'Chat already exists.',
                'thread_id' => $result['thread_id'],
                'created' => $result['created'],
                'access' => $result['access'],
            ], 200);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start chat.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
