<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'shipping_address_id',
        'shipping_address_snapshot',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'shipping_address_snapshot' => 'array',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * When the order reached the print shop: the admin's approval of the payment, not the moment the customer placed it
     * (the notice can wait days for the admin, and the shop must not look late for that). Load `payments` to avoid a query.
     */
    public function reachedShopAt(): \Illuminate\Support\Carbon
    {
        $paidAt = $this->payments->where('status', 'paid')->pluck('paid_at')->filter()->max();

        return $paidAt ? \Illuminate\Support\Carbon::parse($paidAt) : $this->created_at;
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}