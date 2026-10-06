<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionAccessService
{
    public function activePlan(User $user): ?Subscription
    {
        $subscription = $user->activeSubscription();

        if ($subscription?->plan) {
            return $subscription->plan;
        }

        return Subscription::where('type', 'Free')->where('status', 'active')->first();
    }

    public function features(User $user): array
    {
        $plan = $this->activePlan($user);
        $features = $plan?->features ?? config('subscription_plans.user_plans.Free.features');

        return is_array($features) ? $features : [];
    }

    public function access(User $user): array
    {
        $features = $this->features($user);
        $plan = $this->activePlan($user);

        $chatLimit = $features['chat_limit'] ?? 0;
        $boosts = array_key_exists('boosts_per_month', $features) ? $features['boosts_per_month'] : 0;
        $superLikes = array_key_exists('super_likes_per_day', $features) ? $features['super_likes_per_day'] : 0;

        return [
            'plan_type' => $plan?->type ?? 'Free',
            'plan_name' => $plan?->name ?? 'Free',
            'payment_status' => $plan?->payment_status ?? 'free',
            'basic_search' => (bool) ($features['basic_search'] ?? true),
            'unlimited_chats' => (bool) ($features['unlimited_chats'] ?? ($chatLimit === null)),
            'chat_limit' => $chatLimit,
            'can_chat' => ($chatLimit === null) || (int) $chatLimit > 0,
            'boosts_per_month' => $boosts,
            'can_boost' => ($boosts === null) || (int) $boosts > 0,
            'unlimited_boosts' => $boosts === null,
            'super_likes_per_day' => $superLikes,
            'can_super_like' => ($superLikes === null) || (int) $superLikes > 0,
            'unlimited_super_likes' => $superLikes === null,
            'basic_badge' => (bool) ($features['basic_badge'] ?? false),
            'vip_badge' => (bool) ($features['vip_badge'] ?? false),
            'vvip_badge' => (bool) ($features['vvip_badge'] ?? false),
            'membership_badge' => $this->membershipBadge($user),
            'serious_member_badge' => app(ProfileCompletionService::class)->seriousMemberBadge($user),
            'is_serious_member' => app(ProfileCompletionService::class)->isSeriousMember($user),
            'badges' => array_values(array_filter([
                $this->membershipBadge($user),
                app(ProfileCompletionService::class)->seriousMemberBadge($user),
            ])),
            'see_who_liked' => (bool) ($features['see_who_liked'] ?? false),
            'display_features' => $features['display'] ?? [],
        ];
    }

    public function membershipBadge(User $user): ?string
    {
        $plan = $user->activeSubscription()?->plan;

        if (!$plan || $plan->type === 'Free' || (float) $plan->price <= 0) {
            return null;
        }

        $badge = $plan->badge ?: $plan->type;

        return in_array($badge, ['Basic', 'VIP', 'VVIP'], true) ? $badge : $plan->type;
    }

    public function can(User $user, string $feature): bool
    {
        $access = $this->access($user);

        return match ($feature) {
            'basic_search' => $access['basic_search'],
            'chat', 'chats' => $access['can_chat'],
            'boost', 'boosts' => $access['can_boost'],
            'super_like', 'super_likes' => $access['can_super_like'],
            'see_who_liked' => $access['see_who_liked'],
            'basic_badge' => $access['basic_badge'] ?? false,
            'vip_badge' => $access['vip_badge'],
            'vvip_badge' => $access['vvip_badge'],
            default => false,
        };
    }

    public static function addDuration(Carbon $from, int $value, string $unit = 'days'): Carbon
    {
        $unit = strtolower($unit) === 'months' ? 'months' : 'days';

        return $unit === 'months'
            ? $from->copy()->addMonths($value)
            : $from->copy()->addDays($value);
    }
}
