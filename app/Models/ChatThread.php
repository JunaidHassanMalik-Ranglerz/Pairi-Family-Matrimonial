<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatThread extends Model
{
    protected $fillable = [
        'starter_id',
        'recipient_id',
    ];

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'starter_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
