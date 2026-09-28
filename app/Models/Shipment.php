<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_partner_id',
        'shipment_number',
        'tracking_number',
        'status',
        'shipping_cost',
        'currency',
        'delivery_address_snapshot',
        'tracking_url',
        'assigned_at',
        'shipped_at',
        'estimated_delivery_at',
        'delivered_at',
        'cancelled_at',
        'notes',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'delivery_address_snapshot' => 'array',
        'assigned_at' => 'datetime',
        'shipped_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class);
    }
}