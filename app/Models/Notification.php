<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'link',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // A notice the admin switched off in Settings > Notifications is simply not created.
        static::creating(fn (Notification $notification) => \App\Support\NotificationPreferences::allows($notification->type));
    }

    // ========== العلاقات ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
