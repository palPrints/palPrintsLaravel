<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $fillable = [
        'order_id',
        'print_provider_id',
        'print_file_id',
        'purpose',
        'chat_id',
        'telegram_message_id',
        'action_token',
        'status',
        'error',
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
