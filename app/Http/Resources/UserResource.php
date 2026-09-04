<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static function toPayload(User $user): array
    {
        return (new self($user))->toArray(request());
    }

    public function toArray(Request $request): array
    {
        $photos = collect($this->photos ?? [])->map(fn ($photo) => [
            'url' => media_url($photo['path'] ?? null),
            'path' => $photo['path'] ?? null,
            'is_main' => (bool) ($photo['is_main'] ?? false),
        ])->values()->all();

        $membership = $this->membershipPayload();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_verified' => (bool) $this->is_verified,
            'phone_verified' => (bool) $this->phone_verified,
            'gender' => $this->gender,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'age' => $this->age,
            'country' => $this->country,
            'city' => $this->city,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'bio' => $this->bio,
            'profile_photo' => $this->profile_photo,
            'photos' => $photos,
            'profile_completed' => (bool) $this->profile_completed,
            'profile_step' => (int) $this->profile_step,
            'qualification' => $this->qualification,
            'field_of_study' => $this->field_of_study,
            'university' => $this->university,
            'graduation_year' => $this->graduation_year,
            'employment_type' => $this->employment_type,
            'job_title' => $this->job_title,
            'company' => $this->company,
            'monthly_income' => $this->monthly_income,
            'residential_status' => $this->residential_status,
            'height' => $this->height,
            'weight' => $this->weight,
            'body_type' => $this->body_type,
            'complexion' => $this->complexion,
            'physical_disability' => (bool) $this->physical_disability,
            'religion' => $this->religion,
            'community' => $this->community,
            'sect' => $this->sect,
            'mother_tongue' => $this->mother_tongue,
            'other_languages' => $this->other_languages ?? [],
            'interests' => $this->interests ?? [],
            'marital_status' => $this->marital_status,
            'social_provider' => $this->social_provider,
            'status' => $this->status,
            'location' => trim(implode(', ', array_filter([$this->city, $this->country]))),
            'profession' => $this->job_title,
            'referral_code' => $this->referral_code,
            'reward_points' => (int) ($this->reward_points ?? 0),
            'profile_photo_visible' => (bool) ($this->profile_photo_visible ?? true),
            'additional_photos_visible' => (bool) ($this->additional_photos_visible ?? true),
            'profile_boost_until' => $this->profile_boost_until?->toIso8601String(),
            'membership_badge' => $membership['membership_badge'],
            'plan_type' => $membership['plan_type'],
            'discount_eligible' => $membership['discount_eligible'],
            'optional_profile_completed' => $membership['optional_profile_completed'],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function membershipPayload(): array
    {
        try {
            $user = $this->resource;
            $access = app(\App\Services\SubscriptionAccessService::class);
            $completion = app(\App\Services\ProfileCompletionService::class);

            return [
                'membership_badge' => $access->membershipBadge($user),
                'plan_type' => $access->activePlan($user)?->type ?? 'Free',
                'discount_eligible' => $completion->isEligibleForDiscount($user),
                'optional_profile_completed' => $completion->hasAllOptionalFieldsCompleted($user),
            ];
        } catch (\Throwable $e) {
            return [
                'membership_badge' => null,
                'plan_type' => 'Free',
                'discount_eligible' => false,
                'optional_profile_completed' => false,
            ];
        }
    }
}
