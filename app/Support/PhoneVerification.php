<?php

namespace App\Support;

use App\Models\User;

class PhoneVerification
{
    public static function badgeLabel(?User $user): ?string
    {
        if (!$user || !$user->phone_verified) {
            return null;
        }

        return (string) config('pairi_family.phone_verified_badge', 'Verified');
    }

    public static function showBadge(?User $user): bool
    {
        return (bool) ($user?->phone_verified);
    }

    public static function shouldExposeOtpInResponse(): bool
    {
        if (config('pairi_family.expose_phone_otp_in_response') !== null) {
            return (bool) config('pairi_family.expose_phone_otp_in_response');
        }

        return (bool) config('app.debug');
    }

    /**
     * @return array<string, mixed>
     */
    public static function statusPayload(User $user): array
    {
        return [
            'phone' => $user->phone,
            'phone_verified' => (bool) $user->phone_verified,
            'verified_badge' => self::badgeLabel($user),
            'show_verified_badge' => self::showBadge($user),
            'can_verify' => ! $user->phone_verified,
            'otp_pending' => ! $user->phone_verified
                && $user->phone_otp
                && $user->phone_otp_expires_at?->isFuture(),
            'otp_expires_in_seconds' => ($user->phone_otp_expires_at && $user->phone_otp_expires_at->isFuture())
                ? now()->diffInSeconds($user->phone_otp_expires_at)
                : 0,
        ];
    }
}
