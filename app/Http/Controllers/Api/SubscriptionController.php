<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\ProfileCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

            $upgrade = $this->upgradePayload($user);

            if (!$subscription) {
                $freePlan = Subscription::where('type', 'Free')->where('status', 'active')->first();

                return response()->json([
                    'success' => 200,
                    'has_subscription' => false,
                    'plan' => $freePlan ? $this->formatPlan($freePlan, $user) : null,
                    'package_badge' => $user->packageBadge(),
                    'status' => 'free',
                    'is_active' => true,
                    'current_plan' => $this->currentPlanCard($user),
                    ...$upgrade,
                    'billing_cycle' => $this->billingCyclePayload($user),
                    'access' => $access,
                    'profile_completion' => app(ProfileCompletionService::class)->summary($user),
                    'message' => 'You are on the Free plan.',
                ], 200);
            }

            $packageBadge = $subscription->isActive()
                ? ($subscription->plan?->badge
                    ?? ($subscription->plan?->type === 'Free' ? 'Basic' : $subscription->plan?->type)
                    ?? $user->packageBadge())
                : $user->packageBadge();

            return response()->json([
                'success' => 200,
                'has_subscription' => true,
                'package_badge' => $packageBadge,
                'current_plan' => $this->currentPlanCard($user),
                ...$upgrade,
                'billing_cycle' => $this->billingCyclePayload($user),
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'is_active' => $subscription->isActive(),
                    'billing_cycle' => $subscription->billing_cycle ?? 'monthly',
                    'payment_method' => $subscription->payment_method,
                    'starts_at' => $subscription->starts_at?->toIso8601String(),
                    'expires_at' => $subscription->expires_at?->toIso8601String(),
                    'next_billing' => $subscription->expires_at?->format('d M Y'),
                    'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                    'plan' => $subscription->plan ? $this->formatPlan($subscription->plan, $user) : null,
                    'original_price' => $subscription->original_price !== null ? (float) $subscription->original_price : null,
                    'amount_payable' => $subscription->amount_payable !== null ? (float) $subscription->amount_payable : null,
                    'amount_paid' => $subscription->amount_payable !== null ? (float) $subscription->amount_payable : null,
                    'discount_percent' => (int) ($subscription->discount_percent ?? 0),
                ],
                'access' => $access,
                'profile_completion' => app(ProfileCompletionService::class)->summary($user),
            ], 200);
        } catch (\Exception $e) {
            Log::error('myPlan API failed', [
                'user_id' => optional($request->user())->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function current(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $card = $this->currentPlanCard($user);
            $upgrade = $this->upgradePayload($user);

            return response()->json([
                'success' => 200,
                'current_plan' => $card,
                'package_badge' => $card['package_badge'],
                'membership_badge' => $card['membership_badge'],
                'current_plan_type' => $card['plan_type'],
                'status_label' => $card['status_label'],
                'is_active' => $card['is_active'],
                'billing_cycle' => $this->billingCyclePayload($user),
                ...$upgrade,
            ], 200);
        } catch (\Exception $e) {
            Log::error('current plan API failed', [
                'user_id' => optional($request->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load current plan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function billingCycle(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => 200,
                ...$this->billingCyclePayload($request->user()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('billing cycle API failed', [
                'user_id' => optional($request->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load billing cycle.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function switchBillingCycle(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'payment_method' => 'nullable|string|in:easypaisa,jazzcash,bank,card,google_pay,apple_pay',
            ]);

            $user = $request->user();
            $payload = $this->billingCyclePayload($user);

            if (!$payload['can_switch_to_annual']) {
                $message = $payload['billing_cycle_pending']
                    ? 'You already have an annual billing request awaiting verification.'
                    : ($payload['current_cycle'] === 'annual'
                        ? 'You are already on annual billing.'
                        : 'Only active VIP or VVIP members on a monthly plan can switch to annual billing.');

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    ...$payload,
                ], 422);
            }

            $active = $user->activeSubscription();
            $plan = $active?->plan;
            if (!$active || !$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active VIP or VVIP members on a monthly plan can switch to annual billing.',
                    ...$payload,
                ], 422);
            }

            $pricing = $this->annualPricing($plan);

            $subscriptionData = [
                'user_id' => $user->id,
                'subscription_id' => $plan->id,
                'status' => 'paid',
                'billing_cycle' => 'annual',
                'payment_method' => $data['payment_method'] ?? null,
                'starts_at' => null,
                'expires_at' => null,
            ];

            if (Schema::hasColumn('user_subscriptions', 'original_price')) {
                $subscriptionData['original_price'] = $pricing['annual_original_price'];
                $subscriptionData['amount_payable'] = $pricing['annual_payable'];
                $subscriptionData['discount_percent'] = $pricing['discount_percent'];
                $subscriptionData['discount_reason'] = 'annual_billing';
            }

            $subscription = UserSubscription::create($subscriptionData);

            return response()->json([
                'success' => 200,
                'message' => 'Annual billing request submitted. Please complete payment.',
                'requires_payment_upload' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'billing_cycle' => 'annual',
                    'plan' => $this->formatPlan($plan, $user),
                    'requires_payment_upload' => true,
                    'pricing' => $pricing,
                ],
                ...$this->billingCyclePayload($user),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('switch billing cycle API failed', [
                'user_id' => optional($request->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to switch billing cycle.',
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

    public function upgrade(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'payment_method' => 'nullable|string|in:easypaisa,jazzcash,bank,card,google_pay,apple_pay',
            ]);

            $user = $request->user();
            $active = $user->activeSubscription();
            $currentType = $active?->plan?->type ?? 'Free';
            if (!$active || $currentType === 'Free' || (float) ($active->plan?->price ?? 0) <= 0) {
                $currentType = 'Free';
            }

            if ($currentType === 'VVIP') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already on the VVIP plan.',
                    'show_upgrade_option' => false,
                    'can_upgrade_to_vvip' => false,
                    'current_plan_type' => 'VVIP',
                ], 422);
            }

            if (!in_array($currentType, ['Free', 'VIP'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Free or VIP members can upgrade to VVIP.',
                    'show_upgrade_option' => false,
                    'can_upgrade_to_vvip' => false,
                    'current_plan_type' => $currentType,
                ], 422);
            }

            $pending = UserSubscription::where('user_id', $user->id)
                ->whereIn('status', ['paid', 'pending'])
                ->whereNull('cancelled_at')
                ->exists();

            if ($pending) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending subscription awaiting verification.',
                    'upgrade_pending' => true,
                    'show_upgrade_option' => false,
                    'can_upgrade_to_vvip' => false,
                    'current_plan_type' => $currentType,
                ], 422);
            }

            $plan = Subscription::where('type', 'VVIP')->where('status', 'active')->first();
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'VVIP plan is not available.',
                ], 404);
            }

            $pricing = app(ProfileCompletionService::class)->pricingFor($user, $plan);
            $subscriptionData = [
                'user_id' => $user->id,
                'subscription_id' => $plan->id,
                'status' => 'paid',
                'payment_method' => $data['payment_method'] ?? null,
                'starts_at' => null,
                'expires_at' => null,
            ];

            if (Schema::hasColumn('user_subscriptions', 'original_price')) {
                $subscriptionData['original_price'] = $pricing['original_price'];
                $subscriptionData['amount_payable'] = $pricing['payable_price'];
                $subscriptionData['discount_percent'] = $pricing['discount_percent'];
                $subscriptionData['discount_reason'] = $pricing['discount_reason'];
            }

            $subscription = UserSubscription::create($subscriptionData);
            $access = app(\App\Services\SubscriptionAccessService::class)->access($user);

            return response()->json([
                'success' => 200,
                'message' => $pricing['discount_applied']
                    ? 'VVIP upgrade submitted with profile-completion discount. Please complete payment.'
                    : 'VVIP upgrade submitted. Please complete payment of the full VVIP price.',
                'current_plan_type' => $currentType,
                'show_upgrade_option' => false,
                'can_upgrade_to_vvip' => false,
                'upgrade_pending' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'from_plan' => $currentType,
                    'to_plan' => 'VVIP',
                    'plan' => $this->formatPlan($plan, $user),
                    'requires_payment_upload' => true,
                    'pricing' => [
                        'original_price' => $pricing['original_price'],
                        'payable_price' => $pricing['payable_price'],
                        'discount_percent' => $pricing['discount_percent'],
                        'discount_applied' => $pricing['discount_applied'],
                        'discount_reason' => $pricing['discount_reason'],
                    ],
                ],
                'access' => $access,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('upgrade API failed', [
                'user_id' => optional($request->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upgrade subscription.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function payWithCard(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'subscription_id' => 'required|exists:subscriptions,id',
                'card_holder_name' => 'required|string|min:2|max:100',
                'card_number' => ['required', 'string', 'regex:/^[0-9\s]{13,23}$/'],
                'expiry_date' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\s*\/\s*([0-9]{2})$/'],
                'cvv' => ['required', 'string', 'regex:/^[0-9]{3,4}$/'],
            ]);

            $user = $request->user();
            $plan = Subscription::where('id', $data['subscription_id'])
                ->where('status', 'active')
                ->firstOrFail();

            if ((float) $plan->price <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Card payment is not required for the Free plan.',
                ], 422);
            }

            $cardNumber = preg_replace('/\s+/', '', $data['card_number']);
            if (!$this->isValidCardNumber($cardNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid card number.',
                    'errors' => ['card_number' => ['The card number is invalid.']],
                ], 422);
            }

            $expiry = $this->parseCardExpiry($data['expiry_date']);
            if (!$expiry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired card expiry date. Use MM/YY.',
                    'errors' => ['expiry_date' => ['The expiry date is invalid or has passed.']],
                ], 422);
            }

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

            if ($active) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription.',
                ], 422);
            }

            $pricing = app(ProfileCompletionService::class)->pricingFor($user, $plan);
            $brand = $this->detectCardBrand($cardNumber);
            $paymentRef = 'CARD-' . strtoupper(substr(uniqid('', true), -10));

            $subscriptionData = [
                'user_id' => $user->id,
                'subscription_id' => $plan->id,
                'status' => 'paid',
                'payment_method' => 'card',
                'card_holder_name' => trim($data['card_holder_name']),
                'card_last_four' => substr($cardNumber, -4),
                'card_brand' => $brand,
                'card_expiry' => $expiry['label'],
                'card_payment_ref' => $paymentRef,
                'starts_at' => null,
                'expires_at' => null,
            ];

            if (Schema::hasColumn('user_subscriptions', 'original_price')) {
                $subscriptionData['original_price'] = $pricing['original_price'];
                $subscriptionData['amount_payable'] = $pricing['payable_price'];
                $subscriptionData['discount_percent'] = $pricing['discount_percent'];
                $subscriptionData['discount_reason'] = $pricing['discount_reason'];
            }

            $subscription = UserSubscription::create($subscriptionData);

            // CVV and full card number are validated only — never persisted (PCI).
            return response()->json([
                'success' => 200,
                'message' => 'Card details submitted successfully. Please wait for payment verification.',
                'requires_admin_verification' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'payment_method' => 'card',
                    'payment_ref' => $paymentRef,
                    'card' => [
                        'holder_name' => $subscription->card_holder_name,
                        'brand' => $subscription->card_brand,
                        'last_four' => $subscription->card_last_four,
                        'expiry' => $subscription->card_expiry,
                        'masked_number' => '**** **** **** ' . $subscription->card_last_four,
                    ],
                    'plan' => $this->formatPlan($plan, $user),
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('payWithCard API failed', [
                'user_id' => optional($request->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process card payment.',
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

    private function currentPlanCard(User $user): array
    {
        $subscription = $user->activeSubscription();
        $plan = $subscription?->plan
            ?? Subscription::where('type', 'Free')->where('status', 'active')->first();
        $planType = $plan?->type ?? 'Free';
        $isPaid = $plan && $planType !== 'Free' && (float) $plan->price > 0;
        $isActive = $subscription?->isActive() ?? true;
        $statusLabel = $isActive ? 'Active' : 'Inactive';
        $price = (float) ($plan?->price ?? 0);
        $cycle = $subscription?->billing_cycle ?? 'monthly';
        $isAnnual = $isPaid && $cycle === 'annual';
        $durationUnit = strtolower((string) ($plan?->duration_unit ?? 'months'));
        $pricePeriod = $isAnnual ? '/year' : ($durationUnit === 'months' ? '/month' : '/'.$durationUnit);
        $displayPrice = $isAnnual && $subscription?->amount_payable !== null
            ? (float) $subscription->amount_payable
            : $price;
        $renewsAt = $subscription?->expires_at;

        return [
            'title' => ($plan?->name ?? 'Free Plan').' — '.$statusLabel,
            'plan_name' => $plan?->name ?? 'Free',
            'plan_type' => $isPaid ? $planType : 'Free',
            'package_badge' => $user->packageBadge(),
            'membership_badge' => app(\App\Services\SubscriptionAccessService::class)->membershipBadge($user),
            'status' => $subscription?->status ?? 'free',
            'status_label' => $statusLabel,
            'is_active' => $isActive,
            'billing_cycle' => $isPaid ? $cycle : null,
            'price' => $displayPrice,
            'price_label' => $isPaid
                ? 'PKR '.number_format($displayPrice, 0).$pricePeriod
                : 'Free',
            'renews_at' => $renewsAt?->toIso8601String(),
            'renews_label' => $renewsAt ? 'Renews '.$renewsAt->format('d M Y') : null,
            'starts_at' => $subscription?->starts_at?->toIso8601String(),
            'subscription_id' => $subscription?->id,
        ];
    }

    private function annualDiscountPercent(): int
    {
        return max(0, min(100, (int) SystemSetting::getVal('annual_billing_discount_percent', 40)));
    }

    private function annualPricing(Subscription $plan): array
    {
        $discount = $this->annualDiscountPercent();
        $monthly = round((float) $plan->price, 2);
        $original = round($monthly * 12, 2);
        $payable = round($original * (100 - $discount) / 100, 2);
        $savings = round($original - $payable, 2);

        return [
            'discount_percent' => $discount,
            'monthly_price' => $monthly,
            'annual_original_price' => $original,
            'annual_payable' => $payable,
            'savings' => $savings,
        ];
    }

    private function billingCyclePayload(User $user): array
    {
        $discount = $this->annualDiscountPercent();
        $active = $user->activeSubscription();
        $plan = $active?->plan;
        $currentCycle = $active?->billing_cycle ?? 'monthly';
        $isPaidTier = $active
            && $active->isActive()
            && in_array($plan?->type, ['VIP', 'VVIP'], true)
            && (float) ($plan?->price ?? 0) > 0;

        $pending = UserSubscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['paid', 'pending'])
            ->whereNull('cancelled_at')
            ->latest()
            ->first();

        $billingCyclePending = (bool) ($pending && ($pending->billing_cycle ?? 'monthly') === 'annual');
        $canSwitch = $isPaidTier && $currentCycle !== 'annual' && !$pending;

        $pricing = ($plan && $isPaidTier)
            ? $this->annualPricing($plan)
            : [
                'discount_percent' => $discount,
                'monthly_price' => 0.0,
                'annual_original_price' => 0.0,
                'annual_payable' => 0.0,
                'savings' => 0.0,
            ];

        return [
            'title' => 'Change Billing Cycle',
            'subtitle' => 'Switch to annual & save '.$discount.'%',
            'current_cycle' => $isPaidTier ? $currentCycle : 'monthly',
            'can_switch_to_annual' => $canSwitch,
            'billing_cycle_pending' => $billingCyclePending,
            'discount_percent' => $pricing['discount_percent'],
            'monthly_price' => $pricing['monthly_price'],
            'annual_original_price' => $pricing['annual_original_price'],
            'annual_payable' => $pricing['annual_payable'],
            'savings' => $pricing['savings'],
            'plan' => ($plan && $isPaidTier) ? $this->formatPlan($plan, $user) : null,
        ];
    }

    private function upgradePayload(User $user): array
    {
        $active = $user->activeSubscription();
        $planType = $active?->plan?->type ?? 'Free';
        if (!$active || $planType === 'Free' || (float) ($active->plan?->price ?? 0) <= 0) {
            $planType = 'Free';
        }

        $pending = UserSubscription::with('plan')
            ->where('user_id', $user->id)
            ->whereIn('status', ['paid', 'pending'])
            ->whereNull('cancelled_at')
            ->latest()
            ->first();

        $upgradePending = (bool) ($pending && $pending->plan?->type === 'VVIP');
        $showUpgrade = in_array($planType, ['Free', 'VIP'], true) && !$pending;
        $vvipPlan = $showUpgrade
            ? Subscription::where('type', 'VVIP')->where('status', 'active')->first()
            : null;

        return [
            'current_plan_type' => $planType,
            'show_upgrade_option' => $showUpgrade,
            'can_upgrade_to_vvip' => $showUpgrade,
            'upgrade_pending' => $upgradePending,
            'upgrade_option' => ($showUpgrade && $vvipPlan)
                ? [
                    'from' => $planType,
                    'to' => 'VVIP',
                    'label' => 'Upgrade to VVIP',
                    'plan' => $this->formatPlan($vvipPlan, $user),
                    'message' => $planType === 'Free'
                        ? 'Upgrade from Free to VVIP for unlimited chats, boosts, and super likes.'
                        : 'Upgrade to VVIP for unlimited boosts and super likes.',
                ]
                : null,
        ];
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

    private function isValidCardNumber(string $number): bool
    {
        if (!preg_match('/^[0-9]{13,19}$/', $number)) {
            return false;
        }

        $sum = 0;
        $alt = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];
            if ($alt) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $alt = !$alt;
        }

        return $sum % 10 === 0;
    }

    private function parseCardExpiry(string $expiry): ?array
    {
        if (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*([0-9]{2})$/', trim($expiry), $matches)) {
            return null;
        }

        $month = (int) $matches[1];
        $year = 2000 + (int) $matches[2];
        $expiresAt = now()->setDate($year, $month, 1)->endOfMonth();

        if ($expiresAt->isPast()) {
            return null;
        }

        return [
            'month' => sprintf('%02d', $month),
            'year' => (string) $year,
            'label' => sprintf('%02d/%02d', $month, $year % 100),
        ];
    }

    private function detectCardBrand(string $number): string
    {
        return match (true) {
            (bool) preg_match('/^4/', $number) => 'Visa',
            (bool) preg_match('/^(5[1-5]|2[2-7])/', $number) => 'Mastercard',
            (bool) preg_match('/^6/', $number) => 'RuPay',
            (bool) preg_match('/^3[47]/', $number) => 'Amex',
            default => 'Card',
        };
    }
}
