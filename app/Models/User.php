<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'phone_otp',
        'reset_password_token',
        'reset_otp',
        'forget_password_token',
        'verification_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_otp_expires_at' => 'datetime',
        'phone_otp_expires_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
        'otp_resend_available_at' => 'datetime',
        'phone_otp_resend_available_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'birthday' => 'date',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'physical_disability' => 'boolean',
        'profile_completed' => 'boolean',
        'reset_code_verified' => 'boolean',
        'other_languages' => 'array',
        'interests' => 'array',
        'photos' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'profile_photo_visible' => 'boolean',
        'additional_photos_visible' => 'boolean',
        'can_login' => 'boolean',
        'profile_boost_until' => 'datetime',
        'reactivation_requested_at' => 'datetime',
        'package_likes_total' => 'integer',
        'package_likes_granted_on' => 'date',
    ];

    public function hasPendingReactivationRequest(): bool
    {
        return $this->status !== 'active' && $this->reactivation_requested_at !== null;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthday ? $this->birthday->age : null;
    }

    public function getProfilePhotoAttribute(): ?string
    {
        $photos = $this->photos ?? [];

        if (empty($photos)) {
            return $this->image
                ? media_url($this->image)
                : $this->defaultProfilePhotoUrl();
        }

        $main = collect($photos)->firstWhere('is_main', true) ?? $photos[0];

        return media_url($main['path'] ?? null) ?? $this->defaultProfilePhotoUrl();
    }

    private function defaultProfilePhotoUrl(): string
    {
        $fileName = strtolower((string) $this->gender) === 'female'
            ? 'default-female.png'
            : 'default-male.png';

        return app_base_url() . '/assets/img/' . $fileName;
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(ProfileInterest::class, 'from_user_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(ProfileInterest::class, 'to_user_id');
    }

    public function likesCount(): int
    {
        if (array_key_exists('inbound_likes_count', $this->getAttributes())) {
            $real = (int) $this->inbound_likes_count;
        } elseif ($this->relationLoaded('receivedInterests')) {
            $real = $this->receivedInterests
                ->whereIn('action', ['interest', 'super_like'])
                ->count();
        } else {
            $real = (int) $this->receivedInterests()
                ->whereIn('action', ['interest', 'super_like'])
                ->count();
        }

        return $real + (int) ($this->package_likes_total ?? 0);
    }

    public function interactedUserIds(): Collection
    {
        return ProfileInterest::query()
            ->where('from_user_id', $this->id)
            ->pluck('to_user_id');
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                $user->referral_code = self::generateUniqueReferralCode();
            }
        });
    }

    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    public function ensureReferralCode(): string
    {
        if (!empty($this->referral_code)) {
            return $this->referral_code;
        }

        $code = self::generateUniqueReferralCode();
        $this->forceFill(['referral_code' => $code])->save();

        return $code;
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function pendingVerificationSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class)
            ->whereIn('status', ['paid', 'pending'])
            ->whereNull('cancelled_at')
            ->latest();
    }

    public function pendingVerificationSubscription(): ?UserSubscription
    {
        if ($this->relationLoaded('pendingVerificationSubscriptions')) {
            return $this->pendingVerificationSubscriptions->first();
        }

        return $this->pendingVerificationSubscriptions()->with('plan')->first();
    }

    public static function pendingSubscriptionVerificationCount(): int
    {
        return (int) static::query()
            ->whereHas('pendingVerificationSubscriptions')
            ->count();
    }

    public static function pendingSubscriptionVerificationLabel(): string
    {
        $count = static::pendingSubscriptionVerificationCount();

        return $count > 10 ? '10+' : (string) $count;
    }

    public function scopeWithActivePlan($query)
    {
        return $query->with(['subscriptions' => function ($q) {
            $q->with('plan')
                ->whereIn('status', ['verified', 'free'])
                ->whereNull('cancelled_at')
                ->where(function ($sub) {
                    $sub->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest();
        }]);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function marriageBureau(): BelongsTo
    {
        return $this->belongsTo(MarriageBureau::class);
    }

    public function activeSubscription(): ?UserSubscription
    {
        if ($this->relationLoaded('subscriptions')) {
            return $this->subscriptions
                ->sortByDesc('id')
                ->first(fn ($subscription) => $subscription->isActive());
        }

        return $this->subscriptions()
            ->with('plan')
            ->whereIn('status', ['verified', 'free'])
            ->whereNull('cancelled_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    /**
     * Package badge for UI (Free users show "Basic"; paid plans use plan badge/type).
     */
    public function packageBadge(): string
    {
        $subscription = $this->activeSubscription();
        $plan = $subscription?->plan;

        if (!$plan || $plan->type === 'Free' || (float) $plan->price <= 0) {
            return 'Basic';
        }

        $badge = $plan->badge ?: $plan->type;

        return in_array($badge, ['Basic', 'VIP', 'VVIP'], true) ? $badge : (string) $plan->type;
    }
}
