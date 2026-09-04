<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\ProfileCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $plans = Subscription::where('status', 'active')->orderBy('price')->get();
            $user = $request->user();

            return response()->json([
                'success' => 200,
                'plans' => $plans->map(fn ($plan) => $this->formatPlan($plan, $user)),
                'comparison' => $this->comparisonMatrix($plans),
                'profile_completion' => $user
                    ? app(ProfileCompletionService::class)->summary($user)
                    : null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscriptions',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function myPlan(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $accessService = app(\App\Services\SubscriptionAccessService::class);
            $access = $accessService->access($user);

            $subscription = UserSubscription::with('plan')
                ->where('user_id', $user->id)
                ->latest()
                ->first();

            if (!$subscription) {
                $freePlan = Subscription::where('type', 'Free')->where('status', 'active')->first();

                return response()->json([
                    'success' => 200,
                    'has_subscription' => false,
                    'plan' => $freePlan ? $this->formatPlan($freePlan, $user) : null,
                    'status' => 'free',
                    'is_active' => true,
                    'access' => $access,
                    'profile_completion' => app(ProfileCompletionService::class)->summary($user),
                    'message' => 'You are on the Free plan.',
                ], 200);
            }

            return response()->json([
                'success' => 200,
                'has_subscription' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'is_active' => $subscription->isActive(),
                    'payment_method' => $subscription->payment_method,
                    'starts_at' => $subscription->starts_at?->toIso8601String(),
                    'expires_at' => $subscription->expires_at?->toIso8601String(),
                    'next_billing' => $subscription->expires_at?->format('d M Y'),
                    'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                    'plan' => $subscription->plan ? $this->formatPlan($subscription->plan, $user) : null,
                    'original_price' => $subscription->original_price !== null ? (float) $subscription->original_price : null,
                    'amount_payable' => $subscription->amount_payable !== null ? (float) $subscription->amount_payable : null,
                    'discount_percent' => (int) ($subscription->discount_percent ?? 0),
                ],
                'access' => $access,
                'profile_completion' => app(ProfileCompletionService::class)->summary($user),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function access(Request $request): JsonResponse
    {
        try {
            $access = app(\App\Services\SubscriptionAccessService::class)->access($request->user());

            return response()->json([
                'success' => 200,
                'access' => $access,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plan access',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function subscribe(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'subscription_id' => 'required|exists:subscriptions,id',
                'payment_method' => 'nullable|string|in:easypaisa,jazzcash,bank,card,google_pay,apple_pay',
            ]);

            $user = $request->user();
            $plan = Subscription::where('id', $data['subscription_id'])->where('status', 'active')->firstOrFail();

            $pending = UserSubscription::where('user_id', $user->id)
                ->whereIn('status', ['paid', 'pending'])
                ->exists();

            if ($pending) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending subscription awaiting verification.',
                ], 422);
            }

            $active = UserSubscription::where('user_id', $user->id)
                ->whereIn('status', ['verified', 'free'])
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->whereNull('cancelled_at')
                ->exists();

            if ($active && (float) $plan->price > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription.',
                ], 422);
            }

            $isFree = (float) $plan->price <= 0;
            $status = $isFree ? 'free' : 'paid';
            $pricing = app(ProfileCompletionService::class)->pricingFor($user, $plan);

            $subscriptionData = [
                'user_id' => $user->id,
                'subscription_id' => $plan->id,
                'status' => $status,
                'payment_method' => $data['payment_method'] ?? null,
                'starts_at' => $isFree ? now() : null,
                'expires_at' => $isFree ? $plan->expiresAtFrom(now()) : null,
            ];

            if (Schema::hasColumn('user_subscriptions', 'original_price')) {
                $subscriptionData['original_price'] = $pricing['original_price'];
                $subscriptionData['amount_payable'] = $pricing['payable_price'];
                $subscriptionData['discount_percent'] = $pricing['discount_percent'];
                $subscriptionData['discount_reason'] = $pricing['discount_reason'];
            }

            $subscription = UserSubscription::create($subscriptionData);

            return response()->json([
                'success' => 200,
                'message' => $isFree
                    ? 'Free plan activated successfully.'
                    : ($pricing['discount_applied']
                        ? 'Subscription request submitted with 50% profile-completion discount. Please upload payment screenshot.'
                        : 'Subscription request submitted. Please upload payment screenshot.'),
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'plan' => $this->formatPlan($plan, $user),
                    'requires_payment_upload' => !$isFree,
                    'pricing' => [
                        'original_price' => $pricing['original_price'],
                        'payable_price' => $pricing['payable_price'],
                        'discount_percent' => $pricing['discount_percent'],
                        'discount_applied' => $pricing['discount_applied'],
                        'discount_reason' => $pricing['discount_reason'],
                    ],
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function uploadPayment(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'user_subscription_id' => 'required|exists:user_subscriptions,id',
                'payment_screenshot' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            ]);

            $user = $request->user();
            $subscription = UserSubscription::where('user_id', $user->id)
                ->where('id', $data['user_subscription_id'])
                ->where('status', 'paid')
                ->firstOrFail();

            $path = $request->file('payment_screenshot')->store('payment_screenshots', 'public');
            $subscription->update(['payment_screenshot' => $path]);

            return response()->json([
                'success' => 200,
                'message' => 'Payment screenshot uploaded. Please wait for admin verification.',
                'payment_screenshot' => media_url($path),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Pending subscription not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload payment',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $subscription = UserSubscription::with('plan')
                ->where('user_id', $user->id)
                ->whereIn('status', ['verified', 'free', 'paid'])
                ->whereNull('cancelled_at')
                ->latest()
                ->first();

            if (!$subscription || !$subscription->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription to cancel.',
                ], 422);
            }

            $subscription->update(['cancelled_at' => now()]);

            return response()->json([
                'success' => 200,
                'message' => 'Subscription cancelled successfully.',
                'cancelled_at' => $subscription->cancelled_at->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function formatPlan(Subscription $plan, ?User $user = null): array
    {
        $features = $plan->features ?? [];
        $original = (float) $plan->price;
        $pricing = $user
            ? app(ProfileCompletionService::class)->pricingFor($user, $plan)
            : [
                'original_price' => $original,
                'payable_price' => $original,
                'discount_percent' => 0,
                'discount_applied' => false,
                'discount_eligible' => false,
                'discount_reason' => null,
            ];

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'price' => $original,
            'price_label' => 'PKR ' . number_format($original, 0),
            'original_price' => $pricing['original_price'],
            'discounted_price' => $pricing['payable_price'],
            'payable_price' => $pricing['payable_price'],
            'payable_price_label' => 'PKR ' . number_format($pricing['payable_price'], 0),
            'discount_percent' => $pricing['discount_percent'],
            'discount_applied' => $pricing['discount_applied'],
            'discount_eligible' => $pricing['discount_eligible'],
            'duration' => (int) $plan->duration_days,
            'duration_unit' => $plan->duration_unit ?? 'days',
            'duration_label' => $plan->durationLabel(),
            'duration_days' => (int) $plan->duration_days,
            'type' => $plan->type,
            'payment_status' => $plan->payment_status ?? (($plan->type === 'Free') ? 'free' : 'paid'),
            'badge' => $plan->badge,
            'features' => $features,
            'feature_list' => $features['display'] ?? [],
            'status' => $plan->status,
        ];
    }

    private function comparisonMatrix($plans): array
    {
        $rows = [
            ['key' => 'basic_search', 'label' => 'Basic Search'],
            ['key' => 'chat_limit', 'label' => 'Chats'],
            ['key' => 'boosts_per_month', 'label' => 'Profile Boosts'],
            ['key' => 'super_likes_per_day', 'label' => 'Super Likes'],
            ['key' => 'basic_badge', 'label' => 'Basic Badge'],
            ['key' => 'vip_badge', 'label' => 'VIP Badge'],
            ['key' => 'vvip_badge', 'label' => 'VVIP Badge'],
        ];

        $matrix = [];
        foreach ($rows as $row) {
            $entry = ['feature' => $row['label'], 'plans' => []];
            foreach ($plans as $plan) {
                $value = $plan->features[$row['key']] ?? null;
                $entry['plans'][$plan->type] = $this->formatFeatureValue($row['key'], $value);
            }
            $matrix[] = $entry;
        }

        return $matrix;
    }

    private function formatFeatureValue(string $key, $value): string
    {
        if ($key === 'chat_limit') {
            return $value === null ? 'Unlimited' : ('Limited (' . $value . ')');
        }
        if ($key === 'boosts_per_month') {
            return $value === null ? 'Unlimited' : ((int) $value . '/month');
        }
        if ($key === 'super_likes_per_day') {
            return $value === null ? 'Unlimited' : ((int) $value . '/day');
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return $value ? (string) $value : 'No';
    }
}
