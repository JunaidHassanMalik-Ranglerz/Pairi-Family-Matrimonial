<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\WithProfileBadges;
use App\Models\PhotoAccessRequest;
use App\Support\PhoneVerification;
use App\Models\ProfileInterest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileCardResource extends JsonResource
{
    use WithProfileBadges;

    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $accessGranted = $viewer
            && ($viewer->id === $this->id
                || PhotoAccessRequest::hasApprovedAccess((int) $viewer->id, (int) $this->id));
        $profilePhotoVisible = (bool) ($this->profile_photo_visible ?? true) || $accessGranted;

        $isLiked = $this->viewerHasLiked($viewer);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age,
            'city' => $this->city,
            'country' => $this->country,
            'location' => trim(implode(', ', array_filter([$this->city, $this->country]))),
            'distance_km' => $this->distance_km ?? null,
            'profession' => $this->job_title,
            'qualification' => $this->qualification,
            'religion' => $this->religion,
            'marital_status' => $this->marital_status,
            'photos' => collect($this->photos ?? [])
            ->map(function ($photo) {
                if (empty($photo['path'])) {
                    return null;
                }

                return [
                    'url' => media_url($photo['path']),
                    'is_main' => (bool) ($photo['is_main'] ?? false),
                ];
            })
            ->filter()
            ->values()
            ->toArray(),
            'is_verified' => (bool) $this->is_verified,
            'phone_verified' => (bool) $this->phone_verified,
            'verified_badge' => PhoneVerification::badgeLabel($this->resource),
            'show_verified_badge' => PhoneVerification::showBadge($this->resource),
            'is_new' => $this->created_at?->gte(now()->subDays(config('pairi_family.new_profile_days', 3))) ?? false,
            'interest_sent' => $isLiked,
            'is_liked' => $isLiked,
            'shortlisted' => $isLiked,
            'like_status' => $isLiked ? 'liked' : 'unliked',
            'like_message' => $isLiked
                ? 'You have liked this profile.'
                : 'You have not liked this profile yet.',
            'match_score' => (int) ($this->match_score ?? 0),
            'interests' => $this->interests ?? [],
            ...$this->profileBadgePayload(),
        ];
    }

    private function viewerHasLiked($viewer): bool
    {
        if (!$viewer || (int) $viewer->id === (int) $this->id) {
            return false;
        }

        if (isset($this->interest_sent)) {
            return (bool) $this->interest_sent;
        }

        return ProfileInterest::query()
            ->where('from_user_id', $viewer->id)
            ->where('to_user_id', $this->id)
            ->whereIn('action', ['interest', 'super_like'])
            ->exists();
    }

}
