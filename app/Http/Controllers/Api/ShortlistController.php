<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileCardResource;
use App\Models\ProfileInterest;
use App\Models\User;
use App\Services\MatchService;
use App\Services\SubscriptionAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShortlistController extends Controller
{
    public function __construct(
        private MatchService $matchService,
        private SubscriptionAccessService $accessService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $viewer = $request->user();
            $tab = $this->resolveShortlistTab($request->get('tab', 'i_liked'));

            if ($tab === 'liked_me') {
                $this->accessService->assertCan($viewer, 'see_who_liked');

                $userIds = ProfileInterest::query()
                    ->where('to_user_id', $viewer->id)
                    ->whereIn('action', ['interest', 'super_like'])
                    ->pluck('from_user_id');

                $users = User::query()
                    ->withActivePlan()
                    ->whereIn('id', $userIds)
                    ->where('status', 'active')
                    ->latest()
                    ->get();
            } else {
                $userIds = ProfileInterest::query()
                    ->where('from_user_id', $viewer->id)
                    ->whereIn('action', ['interest', 'super_like'])
                    ->pluck('to_user_id');

                $users = User::query()
                    ->withActivePlan()
                    ->whereIn('id', $userIds)
                    ->where('status', 'active')
                    ->latest()
                    ->get();
            }

            $this->matchService->attachDistances($viewer, $users);

            return response()->json([
                'success' => 200,
                'tab' => $tab,
                'tab_label' => $tab === 'liked_me' ? 'Liked Me' : 'Profiles I Liked',
                'total' => $users->count(),
                'see_who_liked' => $this->accessService->can($viewer, 'see_who_liked'),
                'access' => $this->accessService->access($viewer),
                'profiles' => ProfileCardResource::collection($users),
            ], 200);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shortlists.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function sendInterest(Request $request, User $user): JsonResponse
    {
        try {
            $sender = $request->user();

            if ($user->id === $sender->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot send interest to yourself.',
                ], 422);
            }

            if ($user->status !== 'active' || !$user->profile_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not available.',
                ], 404);
            }

            $opposite = match ($sender->gender) {
                'male' => 'female',
                'female' => 'male',
                default => null,
            };

            if ($opposite && $user->gender !== $opposite) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only send interest to opposite gender profiles.',
                ], 422);
            }

            $existing = ProfileInterest::query()
                ->where('from_user_id', $sender->id)
                ->where('to_user_id', $user->id)
                ->first();

            if ($existing && in_array($existing->action, ['interest', 'super_like'], true)) {
                $existing->delete();

                return response()->json([
                    'success' => 200,
                    'message' => 'Interest removed.',
                    'shortlisted' => false,
                    'mutual_match' => false,
                    'from_user_id' => $sender->id,
                    'to_user_id' => $user->id,
                    'access' => $this->accessService->access($sender),
                ], 200);
            }

            $interest = ProfileInterest::updateOrCreate(
                [
                    'from_user_id' => $sender->id,
                    'to_user_id' => $user->id,
                ],
                ['action' => 'interest']
            );

            $mutual = ProfileInterest::query()
                ->where('from_user_id', $user->id)
                ->where('to_user_id', $sender->id)
                ->whereIn('action', ['interest', 'super_like'])
                ->exists();

            return response()->json([
                'success' => 200,
                'message' => 'Interest sent successfully. Profile shortlisted.',
                'shortlisted' => true,
                'mutual_match' => $mutual,
                'shortlist_tab' => 'i_liked',
                'from_user_id' => $sender->id,
                'to_user_id' => $user->id,
                'interest_id' => $interest->id,
                'access' => $this->accessService->access($sender),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send interest.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function pass(Request $request, User $user): JsonResponse
    {
        try {
            $viewer = $request->user();

            if ($user->id === $viewer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action.',
                ], 422);
            }

            ProfileInterest::updateOrCreate(
                [
                    'from_user_id' => $viewer->id,
                    'to_user_id' => $user->id,
                ],
                ['action' => 'pass']
            );

            return response()->json([
                'success' => 200,
                'message' => 'Profile skipped.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pass profile.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function resolveShortlistTab(?string $tab): string
    {
        return match ($tab) {
            'i_like', 'i_liked', 'profiles_i_liked' => 'i_liked',
            'like_me', 'liked_me' => 'liked_me',
            default => 'i_liked',
        };
    }
}
