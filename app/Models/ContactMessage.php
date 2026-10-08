<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function unreadCount(): int
    {
        try {
            return (int) static::query()->where('is_read', false)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function unreadLabel(): string
    {
        $count = static::unreadCount();

        return $count > 10 ? '10+' : (string) $count;
    }

    public static function supportEmail(): string
    {
        return (string) SystemSetting::getVal('support_email', 'support@piyarifamily.com');
    }
}
