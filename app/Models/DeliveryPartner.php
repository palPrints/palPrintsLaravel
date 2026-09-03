<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'contact_person',
        'phone',
        'email',
        'service_areas',
        'delivery_fee',
        'estimated_delivery_days',
        'api_key',
        'approval_status',
        'profile_completed_at',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'admin_notes',
        'is_active',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'service_areas' => 'array',
        'delivery_fee' => 'decimal:2',
        'profile_completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ========== العلاقات ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'delivery_partner_id', 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
