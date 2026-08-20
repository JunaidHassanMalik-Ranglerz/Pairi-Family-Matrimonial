<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age,
            'city' => $this->city,
            'country' => $this->country,
            'location' => trim(implode(', ', array_filter([$this->city, $this->country]))),
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
            'is_new' => $this->created_at?->gte(now()->subDays(config('pairi_family.new_profile_days', 3))) ?? false,
            'match_score' => (int) ($this->match_score ?? 0),
            'interests' => $this->interests ?? [],
        ];
    }
}
