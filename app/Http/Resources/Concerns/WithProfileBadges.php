<?php

namespace App\Http\Resources\Concerns;

use App\Models\User;
use App\Services\ProfileCompletionService;
use App\Services\SubscriptionAccessService;

trait WithProfileBadges
{
    /**
     * @return array{
     *     membership_badge: ?string,
     *     serious_member_badge: ?string,
     *     is_serious_member: bool,
     *     badges: list<string>
     * }
     */
    protected function profileBadgePayload(): array
    {
        if (!$this->resource instanceof User) {
            return [
                'membership_badge' => null,
                'serious_member_badge' => null,
                'is_serious_member' => false,
                'badges' => [],
            ];
        }

        try {
            $user = $this->resource;
            $membershipBadge = app(SubscriptionAccessService::class)->membershipBadge($user);
            $seriousMemberBadge = app(ProfileCompletionService::class)->seriousMemberBadge($user);
            $badges = array_values(array_filter([
                $membershipBadge,
                $seriousMemberBadge,
            ]));

            return [
                'membership_badge' => $membershipBadge,
                'serious_member_badge' => $seriousMemberBadge,
                'is_serious_member' => $seriousMemberBadge !== null,
                'badges' => $badges,
            ];
        } catch (\Throwable $e) {
            return [
                'membership_badge' => null,
                'serious_member_badge' => null,
                'is_serious_member' => false,
                'badges' => [],
            ];
        }
    }
}
