<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeatureUsage extends Model
{
    protected $fillable = [
        'user_id',
        'feature',
        'period_key',
        'used_count',
    ];

    protected $casts = [
        'used_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
