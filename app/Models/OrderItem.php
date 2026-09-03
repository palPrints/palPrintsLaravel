<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'design_product_id',
        'quantity',
        'selected_color',
        'selected_size',
        'unit_price',
        'total_price',
        'designer_earnings',
        'print_provider_earnings',
        'platform_commission',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'designer_earnings' => 'decimal:2',
        'print_provider_earnings' => 'decimal:2',
        'platform_commission' => 'decimal:2',
    ];

    // ========== العلاقات ==========

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function designProduct()
    {
        return $this->belongsTo(DesignProduct::class);
    }
}
