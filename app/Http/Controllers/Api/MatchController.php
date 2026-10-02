<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileCardResource;
use App\Http\Resources\ProfileDetailResource;
use App\Models\User;
use App\Services\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(private MatchService $matchService)
    {
    }

    public function home(Request $request): JsonResponse
    {
        try {
            $viewer = $request->user();

            if (!$viewer->gender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete your profile gender to see matches.',
                ], 422);
            }

            $candidates = $this->matchService
                ->baseQuery($viewer)
                ->latest('created_at')
                ->limit(100)
                ->get();

            $ranked = $this->matchService->rankProfilesForHome($viewer, $candidates);

            $topMatch = $ranked->first();
            $suggested = $ranked->slice(1, 10)->values();
            $this->matchService->attachDistances(
                $viewer,
                collect([$topMatch])->filter()->concat($suggested)
            );

            return response()->json([
                'success' => 200,
                'greeting' => $this->greeting($viewer->name),
                'top_match' => $topMatch ? ProfileCardResource::make($topMatch) : null,
                'suggested_matches' => ProfileCardResource::collection($suggested),
                'total_matches' => $ranked->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load home matches.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $viewer = $request->user();
            $filters = $this->parseFilters($request);

            $query = $this->matchService->fallbackQuery($viewer);
            $query = $this->matchService->applyFilters($query, $viewer, $filters);

            $perPage = min((int) $request->get('per_page', 20), 50);
            $candidates = $query->latest('created_at')->limit(500)->get();
            $ranked = $this->matchService->rankProfiles($viewer, $candidates);

            $page = max((int) $request->get('page', 1), 1);
            $total = $ranked->count();
            $items = $ranked->slice(($page - 1) * $perPage, $perPage)->values();
            $this->matchService->attachDistances($viewer, $items);

            return response()->json([
                'success' => 200,
                'filters_applied' => array_filter($filters, fn ($value) => $value !== null && $value !== '' && $value !== false),
                'quick_filters' => [
                    'near_me' => 'Same city or within 50km',
                    'new_profiles' => 'Joined in last 3 days',
                    'verified' => 'Phone verified profiles',
                ],
                'data' => ProfileCardResource::collection($items),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => (int) ceil($total / max($perPage, 1)),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search matches.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function filter(Request $request): JsonResponse
    {
        return $this->search($request);
    }

    public function profileDetails(Request $request, User $user): JsonResponse
    {
        return $this->show($request, $user);
    }

    public function bestMatch(Request $request): JsonResponse
    {
        try {
            $viewer = $request->user();

            if (!$viewer->gender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete your profile gender to see matches.',
                ], 422);
            }

            $candidates = $this->matchService->baseQuery($viewer)->limit(500)->get();
            $ranked = $this->matchService->rankProfiles($viewer, $candidates);
            $top = $ranked->first();

            if (!$top) {
                return response()->json([
                    'success' => 200,
                    'message' => 'No matching profile found.',
                    'profile' => null,
                    'match_score' => 0,
                ], 200);
            }

            $profile = $this->prepareProfile($viewer, $top);

            return response()->json([
                'success' => 200,
                'match_score' => (int) $profile->match_score,
                'profile' => ProfileDetailResource::make($profile),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load best match profile.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show(Request $request, User $user): JsonResponse
    {
        try {
            $viewer = $request->user();

            if ($user->id === $viewer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot view your own profile here.',
                ], 422);
            }

            if ($user->status !== 'active' || !$user->profile_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not available.',
                ], 404);
            }

            $opposite = $this->matchService->oppositeGender($viewer->gender);
            if ($opposite && $user->gender !== $opposite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not available.',
                ], 404);
            }

            $profile = $this->prepareProfile($viewer, $user);

            return response()->json([
                'success' => 200,
                'profile' => ProfileDetailResource::make($profile),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile details.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function prepareProfile(User $viewer, User $user): User
    {
        if (!$user->relationLoaded('subscriptions')) {
            $user->load(['subscriptions' => function ($q) {
                $q->with('plan')
                    ->whereIn('status', ['verified', 'free'])
                    ->whereNull('cancelled_at')
                    ->where(function ($sub) {
                        $sub->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->latest();
            }]);
        }

        $user->match_score = $this->matchService->scoreProfile($viewer, $user);
        $user->interest_sent = $viewer->sentInterests()
            ->where('to_user_id', $user->id)
            ->where('action', 'interest')
            ->exists();
        $user->interest_received = $viewer->receivedInterests()
            ->where('from_user_id', $user->id)
            ->where('action', 'interest')
            ->exists();
        $user->mutual_match = $user->interest_sent && $user->interest_received;
        $this->matchService->attachDistances($viewer, collect([$user]));

        return $user;
    }

    private function parseFilters(Request $request): array
    {
        return [
            'search' => $request->get('search'),
            'name' => $request->get('name'),
            'city' => $request->get('city'),
            'cities' => $request->get('cities'),
            'age_min' => $request->get('age_min'),
            'age_max' => $request->get('age_max'),
            'qualification' => $request->get('qualification'),
            'profession' => $request->get('profession'),
            'religion' => $request->get('religion'),
            'marital_status' => $request->get('marital_status'),
            'monthly_income' => $request->get('monthly_income'),
            'near_me' => $request->boolean('near_me'),
            'new_profiles' => $request->boolean('new_profiles'),
            'verified' => $request->boolean('verified'),
        ];
    }

    private function greeting(string $name): string
    {
        $hour = (int) now()->format('H');
        $timeGreeting = match (true) {
            $hour < 12 => 'Good Morning',
            $hour < 17 => 'Good Afternoon',
            default => 'Good Evening',
        };

        return "{$timeGreeting}, {$name}. Here are your best matches today.";
    }
}
