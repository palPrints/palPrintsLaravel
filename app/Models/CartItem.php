<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    public const TYPE_CATALOG_DESIGN = 'catalog_design';
    public const TYPE_CUSTOMER_UPLOAD = 'customer_upload';

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'design_id',
        'item_type',
        'quantity',
        'unit_price',
        'selected_options',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'selected_options' => 'array',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function design()
    {
        return $this->belongsTo(Design::class);
    }

    public function printFiles()
    {
        return $this->hasMany(PrintFile::class);
    }
}
