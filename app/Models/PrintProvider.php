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
        'profile_completed_at',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'admin_notes',
        'total_orders',
        'total_earnings',
        'is_active',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'profile_completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
