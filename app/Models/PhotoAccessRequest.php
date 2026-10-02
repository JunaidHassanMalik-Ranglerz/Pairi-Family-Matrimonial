<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoAccessRequest extends Model
{
    protected $fillable = [
        'requester_id',
        'owner_id',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    private static array $approvedOwnerIds = [];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public static function hasApprovedAccess(int $requesterId, int $ownerId): bool
    {
        if (!array_key_exists($requesterId, self::$approvedOwnerIds)) {
            self::$approvedOwnerIds[$requesterId] = self::query()
                ->where('requester_id', $requesterId)
                ->where('status', 'approved')
                ->pluck('owner_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return in_array($ownerId, self::$approvedOwnerIds[$requesterId], true);
    }

    public static function clearApprovedAccessCache(int $requesterId): void
    {
        unset(self::$approvedOwnerIds[$requesterId]);
    }
}
