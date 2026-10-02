<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MatchService
{
    public function __construct(private GoogleDistanceService $distanceService)
    {
    }

    public function newProfileDays(): int
    {
        return (int) config('pairi_family.new_profile_days', 3);
    }

    public function isNewProfile(User $user): bool
    {
        return $user->created_at?->gte(now()->subDays($this->newProfileDays())) ?? false;
    }

    public function oppositeGender(?string $gender): ?string
    {
        return match ($gender) {
            'male' => 'female',
            'female' => 'male',
            default => null,
        };
    }

    public function baseQuery(User $viewer): Builder
    {
        $excludedIds = $viewer->interactedUserIds();

        return $this->fallbackQuery($viewer)
            ->when($excludedIds->isNotEmpty(), fn (Builder $q) => $q->whereNotIn('id', $excludedIds));
    }

    public function fallbackQuery(User $viewer): Builder
    {
        $opposite = $this->oppositeGender($viewer->gender);

        return User::query()
            ->where('id', '!=', $viewer->id)
            ->where('status', 'active')
            ->where('profile_completed', true)
            ->when($opposite, fn (Builder $q) => $q->where('gender', $opposite))
            ->when($excludedIds->isNotEmpty(), fn (Builder $q) => $q->whereNotIn('id', $excludedIds))
            ->withActivePlan();
    }

    public function fallbackQuery(User $viewer): Builder
    {
        $opposite = $this->oppositeGender($viewer->gender);

        return User::query()
             ->with(['subscriptions' => function ($q) {
                $q->with('plan')
                    ->whereIn('status', ['verified', 'free'])
                    ->whereNull('cancelled_at')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->latest();
            }])
            ->where('id', '!=', $viewer->id)
            ->where('status', 'active')
            ->where('profile_completed', true)
            ->when($opposite, fn (Builder $q) => $q->where('gender', $opposite));
    }

    public function applyFilters(Builder $query, User $viewer, array $filters): Builder
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('qualification', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['cities']) && is_array($filters['cities'])) {
            $query->where(function (Builder $q) use ($filters) {
                foreach ($filters['cities'] as $city) {
                    $q->orWhere('city', 'like', '%' . $city . '%');
                }
            });
        }

        if (!empty($filters['age_min'])) {
            $maxBirthday = now()->subYears((int) $filters['age_min'])->format('Y-m-d');
            $query->where('birthday', '<=', $maxBirthday);
        }

        if (!empty($filters['age_max'])) {
            $minBirthday = now()->subYears(((int) $filters['age_max']) + 1)->addDay()->format('Y-m-d');
            $query->where('birthday', '>=', $minBirthday);
        }

        if (!empty($filters['qualification']) && $filters['qualification'] !== 'Any') {
            $query->where('qualification', 'like', '%' . $filters['qualification'] . '%');
        }

        if (!empty($filters['profession']) && $filters['profession'] !== 'Any') {
            $query->where('job_title', 'like', '%' . $filters['profession'] . '%');
        }

        if (!empty($filters['religion']) && $filters['religion'] !== 'Any') {
            $religion = strtolower(trim($filters['religion']));
            if (in_array($religion, ['islam', 'muslim'], true)) {
                $query->whereRaw('LOWER(religion) IN (?, ?)', ['islam', 'muslim']);
            } else {
                $query->whereRaw('LOWER(religion) = ?', [$religion]);
            }
        }

        if (!empty($filters['marital_status'])) {
            $query->whereRaw('LOWER(marital_status) = ?', [strtolower(trim($filters['marital_status']))]);
        }

        if (!empty($filters['monthly_income'])) {
            $query->where('monthly_income', 'like', '%' . $filters['monthly_income'] . '%');
        }

        if (!empty($filters['near_me'])) {
            if ($viewer->latitude && $viewer->longitude) {
                $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->whereRaw(
                        '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?',
                        [$viewer->latitude, $viewer->longitude, $viewer->latitude, 50]
                    );
            } elseif ($viewer->city) {
                $query->where('city', $viewer->city);
            }
        }

        if (!empty($filters['new_profiles'])) {
            $query->where('created_at', '>=', now()->subDays($this->newProfileDays()));
        }

        if (!empty($filters['verified'])) {
            $query->where('phone_verified', true);
        }

        return $query;
    }

    public function scoreProfile(User $viewer, User $candidate): int
    {
        $score = 0;

        $viewerInterests = collect($viewer->interests ?? [])->map(fn ($i) => strtolower(trim($i)))->filter();
        $candidateInterests = collect($candidate->interests ?? [])->map(fn ($i) => strtolower(trim($i)))->filter();

        if ($viewerInterests->isNotEmpty() && $candidateInterests->isNotEmpty()) {
            $common = $viewerInterests->intersect($candidateInterests)->count();
            $score += (int) round(($common / max($viewerInterests->count(), 1)) * 50);
            $score += min($common * 10, 30);
        }

        if ($viewer->city && $candidate->city && strcasecmp($viewer->city, $candidate->city) === 0) {
            $score += 20;
        }

        if ($viewer->qualification && $candidate->qualification && strcasecmp($viewer->qualification, $candidate->qualification) === 0) {
            $score += 20;
        }

        if ($viewer->religion && $candidate->religion && strcasecmp($viewer->religion, $candidate->religion) === 0) {
            $score += 10;
        }

        if ($viewer->age && $candidate->age) {
            $ageDiff = abs($viewer->age - $candidate->age);
            if ($ageDiff <= 3) {
                $score += 20;
            } elseif ($ageDiff <= 5) {
                $score += 10;
            }
        }

        if ($candidate->phone_verified) {
            $score += 5;
        }

        if ($this->isNewProfile($candidate)) {
            $score += 10;
        }

        return min($score, 100);
    }

    public function rankProfiles(User $viewer, Collection $candidates): Collection
    {
        return $candidates->map(function (User $candidate) use ($viewer) {
            $candidate->match_score = $this->scoreProfile($viewer, $candidate);

            return $candidate;
        })->sortByDesc('match_score')->values();
    }

    public function rankProfilesForHome(User $viewer, Collection $candidates): Collection
    {
        return $candidates->map(function (User $candidate) use ($viewer) {
            $candidate->match_score = $this->scoreProfile($viewer, $candidate);

            return $candidate;
        })->sort(function (User $a, User $b) {
            $aNew = $this->isNewProfile($a) ? 1 : 0;
            $bNew = $this->isNewProfile($b) ? 1 : 0;

            if ($aNew !== $bNew) {
                return $bNew <=> $aNew;
            }

            return $b->match_score <=> $a->match_score;
        })->values();
    }

    public function attachDistances(User $viewer, Collection $profiles): Collection
    {
        foreach ($profiles as $profile) {
            $profile->distance_km = null;
        }

        if (!$this->distanceService->isValidCoordinate($viewer->latitude, $viewer->longitude)) {
            return $profiles;
        }

        $destinations = $profiles->map(fn (User $profile) => [
            'lat' => $profile->latitude,
            'lng' => $profile->longitude,
        ])->all();

        $distances = $this->distanceService->distancesFromOrigin(
            (float) $viewer->latitude,
            (float) $viewer->longitude,
            $destinations
        );

        foreach ($profiles as $index => $profile) {
            $profile->distance_km = $distances[$index] ?? null;
        }

        return $profiles;
    }
}
