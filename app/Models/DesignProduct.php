<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'design_id',
        'product_id',
        'designer_margin',
        'position_x',
        'position_y',
        'scale',
        'rotation',
        'is_active',
    ];

    protected $casts = [
        'designer_margin' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ========== العلاقات ==========

    public function design()
    {
        return $this->belongsTo(Design::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
