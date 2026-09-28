<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchOfferingVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_product_offering_id',
        'variant_id',
        'branch_sku',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function branchProductOffering()
    {
        return $this->belongsTo(BranchProductOffering::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function branchPrintCapabilityVariants()
    {
        return $this->hasMany(BranchPrintCapabilityVariant::class);
    }

    public function branchPricingRules()
    {
        return $this->hasMany(BranchPricingRule::class);
    }
}