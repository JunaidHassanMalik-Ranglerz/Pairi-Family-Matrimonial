<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Schema;

class ProfileCompletionService
{
    public function optionalFields(): array
    {
        return config('profile_completion.optional_fields', []);
    }

    public function requiredFields(): array
    {
        return config('profile_completion.required_fields', [
            'name',
            'email',
            'phone',
            'country',
            'gender',
            'birthday',
            'photos',
        ]);
    }

    public function missingRequiredFields(User $user): array
    {
        $missing = [];

        foreach ($this->requiredFields() as $field) {
            if (!$this->isFilled($user->{$field} ?? null)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function hasAllRequiredFieldsCompleted(User $user): bool
    {
        return empty($this->missingRequiredFields($user));
    }

    public function isFullyCompleted(User $user): bool
    {
        return $this->hasAllRequiredFieldsCompleted($user)
            && $this->hasAllOptionalFieldsCompleted($user);
    }

    public function isSeriousMember(User $user): bool
    {
        return $this->isFullyCompleted($user);
    }

    public function seriousMemberBadge(User $user): ?string
    {
        return $this->isSeriousMember($user)
            ? (string) config('profile_completion.serious_member_badge', 'Serious Member')
            : null;
    }

    public function missingOptionalFields(User $user): array
    {
        $missing = [];

        foreach ($this->optionalFields() as $field) {
            if (!$this->isFilled($user->{$field} ?? null)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function hasAllOptionalFieldsCompleted(User $user): bool
    {
        return empty($this->missingOptionalFields($user));
    }

    public function hasUsedProfileCompletionDiscount(User $user): bool
    {
        if (!Schema::hasColumn('user_subscriptions', 'discount_percent')) {
            return false;
        }

        return UserSubscription::query()
            ->where('user_id', $user->id)
            ->where('discount_percent', '>', 0)
            ->exists();
    }

    public function isEligibleForDiscount(User $user): bool
    {
        return $this->isFullyCompleted($user)
            && !$this->hasUsedProfileCompletionDiscount($user);
    }

    public function summary(User $user): array
    {
        $fields = $this->optionalFields();
        $missing = $this->missingOptionalFields($user);
        $total = count($fields);
        $completed = $total - count($missing);
        $discountUsed = $this->hasUsedProfileCompletionDiscount($user);
        $eligible = $this->isEligibleForDiscount($user);

        return [
            'optional_profile_completed' => empty($missing),
            'profile_fully_completed' => $this->isFullyCompleted($user),
            'discount_used' => $discountUsed,
            'discount_eligible' => $eligible,
            'discount_percent' => $eligible ? (int) config('profile_completion.discount_percent', 50) : 0,
            'completed_count' => $completed,
            'total_count' => $total,
            'missing_fields' => $missing,
        ];
    }

    public function pricingFor(User $user, Subscription $plan): array
    {
        $original = round((float) $plan->price, 2);
        $discountableTypes = config('profile_completion.discountable_plan_types', ['Basic', 'VIP', 'VVIP']);
        $eligible = $original > 0
            && in_array($plan->type, $discountableTypes, true)
            && $this->isEligibleForDiscount($user);
        $percent = $eligible ? (int) config('profile_completion.discount_percent', 50) : 0;
        $payable = $eligible ? round($original * (1 - ($percent / 100)), 2) : $original;

        return [
            'original_price' => $original,
            'payable_price' => $payable,
            'discount_percent' => $percent,
            'discount_applied' => $percent > 0,
            'discount_eligible' => $this->isEligibleForDiscount($user),
            'discount_reason' => $percent > 0 ? (string) config('profile_completion.discount_reason', 'profile_completion') : null,
        ];
    }

    private function isFilled(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->filter(function ($item) {
                return $item !== null && $item !== '';
            })->isNotEmpty();
        }

        if (is_bool($value) || is_numeric($value)) {
            return true;
        }

        return $value !== null && trim((string) $value) !== '';
    }
}
