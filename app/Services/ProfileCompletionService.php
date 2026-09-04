<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;

class ProfileCompletionService
{
    public function optionalFields(): array
    {
        return config('profile_completion.optional_fields', []);
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

    public function isEligibleForDiscount(User $user): bool
    {
        return $this->hasAllOptionalFieldsCompleted($user);
    }

    public function summary(User $user): array
    {
        $fields = $this->optionalFields();
        $missing = $this->missingOptionalFields($user);
        $total = count($fields);
        $completed = $total - count($missing);

        return [
            'optional_profile_completed' => empty($missing),
            'discount_eligible' => empty($missing),
            'discount_percent' => empty($missing) ? (int) config('profile_completion.discount_percent', 50) : 0,
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
