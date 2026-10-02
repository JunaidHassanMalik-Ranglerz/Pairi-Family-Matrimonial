<?php

namespace App\Http\Resources;

use App\Models\PhotoAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $accessRequest = $viewer ? PhotoAccessRequest::query()
            ->where('requester_id', $viewer->id)
            ->where('owner_id', $this->id)
            ->first() : null;
        $accessGranted = $viewer
            && ($viewer->id === $this->id || $accessRequest?->status === 'approved');
        $profilePhotoVisible = (bool) ($this->profile_photo_visible ?? true) || $accessGranted;
        $additionalPhotosVisible = (bool) ($this->additional_photos_visible ?? true) || $accessGranted;
        $photos = $additionalPhotosVisible
            ? collect($this->photos ?? [])
                ->reject(fn ($photo) => !$profilePhotoVisible && (bool) ($photo['is_main'] ?? false))
                ->map(fn ($photo) => [
                    'url' => media_url($photo['path'] ?? null),
                    'is_main' => (bool) ($photo['is_main'] ?? false),
                ])->values()->all()
            : [];
        $hasHiddenPhotos = !(bool) ($this->profile_photo_visible ?? true)
            || !(bool) ($this->additional_photos_visible ?? true);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age,
            'city' => $this->city,
            'country' => $this->country,
            'location' => trim(implode(', ', array_filter([$this->city, $this->country]))),
            'distance_km' => $this->distance_km ?? null,
            'profile_photo' => $this->profile_photo,
            'photos' => $photos,
            'visibility' => [
                'profile_photo_visible' => $profilePhotoVisible,
                'additional_photos_visible' => $additionalPhotosVisible,
            ],
            'photo_access' => [
                'request_id' => $accessRequest?->id,
                'status' => $accessRequest?->status,
                'granted' => $accessGranted,
                'can_request' => $hasHiddenPhotos
                    && (bool) ($this->mutual_match ?? false)
                    && (!$accessRequest || $accessRequest->status === 'rejected'),
            ],
            'is_verified' => (bool) $this->is_verified,
            'phone_verified' => (bool) $this->phone_verified,
            'bio' => $this->bio,
            'qualification' => $this->qualification,
            'education' => $this->qualification,
            'profession' => $this->job_title,
            'job_title' => $this->job_title,
            'company' => $this->company,
            'religion' => $this->religion,
            'community' => $this->community,
            'sect' => $this->sect,
            'marital_status' => $this->marital_status,
            'height' => $this->height,
            'weight' => $this->weight,
            'body_type' => $this->body_type,
            'complexion' => $this->complexion,
            'mother_tongue' => $this->mother_tongue,
            'other_languages' => $this->other_languages ?? [],
            'interests' => $this->interests ?? [],
            'monthly_income' => $this->monthly_income,
            'residential_status' => $this->residential_status,
            'employment_type' => $this->employment_type,
            'field_of_study' => $this->field_of_study,
            'university' => $this->university,
            'graduation_year' => $this->graduation_year,
            'physical_disability' => (bool) $this->physical_disability,
            'gender' => $this->gender,
            'match_score' => (int) ($this->match_score ?? 0),
            'is_new' => $this->created_at?->gte(now()->subDays(config('pairi_family.new_profile_days', 3))) ?? false,
            'interest_sent' => (bool) ($this->interest_sent ?? false),
            'interest_received' => (bool) ($this->interest_received ?? false),
            'mutual_match' => (bool) ($this->mutual_match ?? false),
            'membership_badge' => $this->membershipBadge(),
        ];
    }

    private function membershipBadge(): ?string
    {
        try {
            if (!$this->resource instanceof \App\Models\User) {
                return null;
            }

            return app(\App\Services\SubscriptionAccessService::class)->membershipBadge($this->resource);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
