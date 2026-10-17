<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    /** Outbound statuses in delivery order: a webhook never moves a message backwards (Meta may send "read" before "delivered"). */
    public const STATUS_RANK = ['queued' => 0, 'accepted' => 1, 'sent' => 2, 'delivered' => 3, 'read' => 4];

    protected $fillable = [
        'direction',
        'order_id',
        'print_provider_id',
        'purpose',
        'wa_id',
        'wa_message_id',
        'context_wa_message_id',
        'action_token',
        'type',
        'payload',
        'status',
        'error',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function printProvider()
    {
        return $this->belongsTo(PrintProvider::class);
    }
}
