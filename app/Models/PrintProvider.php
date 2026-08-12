<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'address',
        'phone',
        'whatsapp_number',
        'working_hours',
        'license_document',
        'verification_document',
        'approval_status',
        'approved_at',
        'rejection_reason',
        'total_orders',
        'total_earnings',
        'is_active',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'total_earnings' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ========== العلاقات ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function printProviderProducts()
    {
        return $this->hasMany(PrintProviderProduct::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
