<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city',
        'state',
        'street',
        'building',
        'apartment',
        'phone',
        'postal_code',
        'is_default',
        'is_deleted',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    // ========== العلاقات ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }
}
