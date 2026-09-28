<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchProductOffering extends Model
{
    use HasFactory;

    protected $fillable = [
        'print_provider_branch_id',
        'product_id',
        'base_price',
        'currency',
        'production_time_min',
        'production_time_max',
        'daily_capacity',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function printProviderBranch()
    {
        return $this->belongsTo(PrintProviderBranch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branchOfferingVariants()
    {
        return $this->hasMany(BranchOfferingVariant::class);
    }

    public function branchPrintAreas()
    {
        return $this->hasMany(BranchPrintArea::class);
    }

    public function branchPricingRules()
    {
        return $this->hasMany(BranchPricingRule::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}