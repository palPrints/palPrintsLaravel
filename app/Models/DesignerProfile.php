<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'bio',
        'skills',
        'portfolio_url',
        'profile_image',
        'approval_status',
        'approved_at',
        'rejection_reason',
        'total_sales',
        'total_earnings',
    ];

    protected $casts = [
        'skills' => 'array',
        'approved_at' => 'datetime',
        'total_earnings' => 'decimal:2',
    ];

    // ========== العلاقات ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function designs()
    {
        return $this->hasMany(Design::class, 'designer_id');
    }
}
