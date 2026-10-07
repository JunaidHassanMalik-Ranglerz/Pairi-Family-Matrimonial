<?php

namespace App\Services;

use App\Models\ChatThread;
use App\Models\PlanFeatureUsage;
use App\Models\ProfileInterest;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubscriptionAccessService
{
    public const FEATURE_CHAT = 'chat';
    public const FEATURE_BOOST = 'boost';
    public const FEATURE_SUPER_LIKE = 'super_like';

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

        $chatLimit = array_key_exists('chat_limit', $features) ? $features['chat_limit'] : 0;
        $boosts = array_key_exists('boosts_per_month', $features) ? $features['boosts_per_month'] : 0;
        $superLikes = array_key_exists('super_likes_per_day', $features) ? $features['super_likes_per_day'] : 0;

        $chatsUsed = $this->usedCount($user, self::FEATURE_CHAT);
        $boostsUsed = $this->usedCount($user, self::FEATURE_BOOST);
        $superLikesUsed = $this->usedCount($user, self::FEATURE_SUPER_LIKE);

        $membershipBadge = $this->membershipBadge($user);
        $seriousMemberBadge = app(ProfileCompletionService::class)->seriousMemberBadge($user);

        return [
            'plan_type' => $plan?->type ?? 'Free',
            'plan_name' => $plan?->name ?? 'Free',
            'payment_status' => $plan?->payment_status ?? 'free',
            'basic_search' => (bool) ($features['basic_search'] ?? true),
            'unlimited_chats' => $chatLimit === null || (bool) ($features['unlimited_chats'] ?? false),
            'chat_limit' => $chatLimit,
            'chats_used' => $chatsUsed,
            'chats_remaining' => $this->remaining($chatLimit, $chatsUsed),
            'can_chat' => $this->hasRemaining($chatLimit, $chatsUsed),
            'boosts_per_month' => $boosts,
            'boosts_used' => $boostsUsed,
            'boosts_remaining' => $this->remaining($boosts, $boostsUsed),
            'can_boost' => $this->hasRemaining($boosts, $boostsUsed),
            'unlimited_boosts' => $boosts === null,
            'profile_boost_until' => $user->profile_boost_until?->toIso8601String(),
            'profile_boost_active' => (bool) ($user->profile_boost_until?->isFuture()),
            'super_likes_per_day' => $superLikes,
            'super_likes_used' => $superLikesUsed,
            'super_likes_remaining' => $this->remaining($superLikes, $superLikesUsed),
            'can_super_like' => $this->hasRemaining($superLikes, $superLikesUsed),
            'unlimited_super_likes' => $superLikes === null,
            'basic_badge' => (bool) ($features['basic_badge'] ?? false),
            'vip_badge' => (bool) ($features['vip_badge'] ?? false),
            'vvip_badge' => (bool) ($features['vvip_badge'] ?? false),
            'membership_badge' => $membershipBadge,
            'serious_member_badge' => $seriousMemberBadge,
            'is_serious_member' => $seriousMemberBadge !== null,
            'badges' => array_values(array_filter([
                $membershipBadge,
                $seriousMemberBadge,
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

    public function assertCan(User $user, string $feature, ?string $message = null): void
    {
        if ($this->can($user, $feature)) {
            return;
        }

        $access = $this->access($user);
        $defaultMessage = match ($feature) {
            'chat', 'chats' => 'You have reached your chat limit for the Free plan. Upgrade to VIP or VVIP for unlimited chats.',
            'boost', 'boosts' => 'Boosts are not available on your plan, or you have used all boosts for this month. Upgrade to VIP or VVIP.',
            'super_like', 'super_likes' => 'Super Likes are not available on your plan, or you have used all Super Likes for today. Upgrade to VIP or VVIP.',
            'see_who_liked' => 'Seeing who liked you is available on VIP and VVIP plans only.',
            default => 'This feature is not available on your current plan.',
        };

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $message ?? $defaultMessage,
            'upgrade_required' => true,
            'access' => $access,
        ], 403));
    }

    public function consume(User $user, string $feature, int $amount = 1): int
    {
        $limit = $this->limitFor($user, $feature);
        $periodKey = $this->periodKey($feature);
        $usage = PlanFeatureUsage::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'feature' => $feature,
                'period_key' => $periodKey,
            ],
            ['used_count' => 0]
        );

        if ($limit !== null && ($usage->used_count + $amount) > $limit) {
            $this->assertCan($user, $feature);
        }

        $usage->increment('used_count', $amount);

        return (int) $usage->fresh()->used_count;
    }

    public function usedCount(User $user, string $feature): int
    {
        if ($feature === self::FEATURE_CHAT) {
            return ChatThread::query()->where('starter_id', $user->id)->count();
        }

        if ($feature === self::FEATURE_SUPER_LIKE) {
            $fromUsage = (int) (PlanFeatureUsage::query()
                ->where('user_id', $user->id)
                ->where('feature', $feature)
                ->where('period_key', $this->periodKey($feature))
                ->value('used_count') ?? 0);

            if ($fromUsage > 0) {
                return $fromUsage;
            }

            return ProfileInterest::query()
                ->where('from_user_id', $user->id)
                ->where('action', 'super_like')
                ->whereDate('created_at', now()->toDateString())
                ->count();
        }

        return (int) (PlanFeatureUsage::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature)
            ->where('period_key', $this->periodKey($feature))
            ->value('used_count') ?? 0);
    }

    public function applyBoost(User $user): array
    {
        $this->assertCan($user, 'boost');
        $this->consume($user, self::FEATURE_BOOST);

        $hours = (int) config('pairi_family.boost_duration_hours', 24);
        $until = ($user->profile_boost_until && $user->profile_boost_until->isFuture())
            ? $user->profile_boost_until->copy()->addHours($hours)
            : now()->addHours($hours);

        $user->update(['profile_boost_until' => $until]);

        return [
            'profile_boost_until' => $until->toIso8601String(),
            'boost_duration_hours' => $hours,
            'access' => $this->access($user->fresh()),
        ];
    }

    public function startChat(User $starter, User $recipient): array
    {
        $existing = ChatThread::query()
            ->where('starter_id', $starter->id)
            ->where('recipient_id', $recipient->id)
            ->first();

        if ($existing) {
            return [
                'thread_id' => $existing->id,
                'created' => false,
                'access' => $this->access($starter),
            ];
        }

        $this->assertCan($starter, 'chat');

        $thread = ChatThread::create([
            'starter_id' => $starter->id,
            'recipient_id' => $recipient->id,
        ]);

        // Chat slots are counted from chat_threads; keep usage row in sync for Free plans.
        $limit = $this->limitFor($starter, self::FEATURE_CHAT);
        if ($limit !== null) {
            PlanFeatureUsage::query()->updateOrCreate(
                [
                    'user_id' => $starter->id,
                    'feature' => self::FEATURE_CHAT,
                    'period_key' => 'lifetime',
                ],
                ['used_count' => ChatThread::query()->where('starter_id', $starter->id)->count()]
            );
        }

        return [
            'thread_id' => $thread->id,
            'created' => true,
            'access' => $this->access($starter),
        ];
    }

    private function limitFor(User $user, string $feature): ?int
    {
        $features = $this->features($user);

        return match ($feature) {
            self::FEATURE_CHAT, 'chat', 'chats' => array_key_exists('chat_limit', $features)
                ? ($features['chat_limit'] === null ? null : (int) $features['chat_limit'])
                : 0,
            self::FEATURE_BOOST, 'boost', 'boosts' => array_key_exists('boosts_per_month', $features)
                ? ($features['boosts_per_month'] === null ? null : (int) $features['boosts_per_month'])
                : 0,
            self::FEATURE_SUPER_LIKE, 'super_like', 'super_likes' => array_key_exists('super_likes_per_day', $features)
                ? ($features['super_likes_per_day'] === null ? null : (int) $features['super_likes_per_day'])
                : 0,
            default => 0,
        };
    }

    private function periodKey(string $feature): string
    {
        return match ($feature) {
            self::FEATURE_CHAT, 'chat', 'chats' => 'lifetime',
            self::FEATURE_BOOST, 'boost', 'boosts' => now()->format('Y-m'),
            self::FEATURE_SUPER_LIKE, 'super_like', 'super_likes' => now()->format('Y-m-d'),
            default => now()->format('Y-m-d'),
        };
    }

    private function remaining(mixed $limit, int $used): ?int
    {
        if ($limit === null) {
            return null;
        }

        return max(0, (int) $limit - $used);
    }

    private function hasRemaining(mixed $limit, int $used): bool
    {
        if ($limit === null) {
            return true;
        }

        return $used < (int) $limit;
    }

    public static function addDuration(Carbon $from, int $value, string $unit = 'days'): Carbon
    {
        $unit = strtolower($unit) === 'months' ? 'months' : 'days';

        return $unit === 'months'
            ? $from->copy()->addMonths($value)
            : $from->copy()->addDays($value);
    }
}
