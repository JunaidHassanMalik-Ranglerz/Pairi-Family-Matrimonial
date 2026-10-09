<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'original_price' => 'decimal:2',
        'amount_payable' => 'decimal:2',
        'discount_percent' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        if ($this->cancelled_at) {
            return false;
        }

        if (!in_array($this->status, ['verified', 'free'], true)) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isAnnual(): bool
    {
        return ($this->billing_cycle ?? 'monthly') === 'annual';
    }
}
