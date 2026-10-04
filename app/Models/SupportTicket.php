<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    public const CATEGORY_ORDER = 'order';
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_WITHDRAWAL = 'withdrawal';
    public const CATEGORY_DESIGN = 'design';
    public const CATEGORY_PRINTING = 'printing';
    public const CATEGORY_DELIVERY = 'delivery';
    public const CATEGORY_ACCOUNT = 'account';
    public const CATEGORY_OTHER = 'other';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING_ADMIN = 'pending_admin';
    public const STATUS_PENDING_USER = 'pending_user';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'ticket_number',
        'subject',
        'category',
        'priority',
        'status',
        'last_reply_at',
        'closed_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket): void {
            $ticket->ticket_number ??= self::nextTicketNumber();
            $ticket->priority ??= self::priorityForCategory($ticket->category);
            $ticket->status ??= self::STATUS_PENDING_ADMIN;
        });
    }

    public static function priorityForCategory(?string $category): string
    {
        return match ($category) {
            self::CATEGORY_WITHDRAWAL,
            self::CATEGORY_PAYMENT => self::PRIORITY_HIGH,
            self::CATEGORY_OTHER => self::PRIORITY_LOW,
            default => self::PRIORITY_NORMAL,
        };
    }

    public static function nextTicketNumber(): string
    {
        $nextId = ((int) self::query()->max('id')) + 1;

        return 'SUP-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }
}