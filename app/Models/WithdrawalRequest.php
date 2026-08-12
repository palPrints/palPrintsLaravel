<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount',
        'method',
        'account_details',
        'status',
        'rejection_reason',
        'approved_at',
        'completed_at',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'account_details' => 'array',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ========== العلاقات ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
