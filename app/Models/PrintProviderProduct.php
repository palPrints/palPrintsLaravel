<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintProviderProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'print_provider_id',
        'product_id',
        'is_custom',
        'custom_name',
        'custom_description',
        'price',
        'production_time',
        'available_colors',
        'available_sizes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'available_colors' => 'array',
        'available_sizes' => 'array',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];

    // ========== العلاقات ==========

    public function printProvider()
    {
        return $this->belongsTo(PrintProvider::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
