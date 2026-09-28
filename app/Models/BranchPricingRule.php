<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_product_offering_id',
        'branch_offering_variant_id',
        'branch_print_capability_id',
        'min_quantity',
        'max_quantity',
        'pricing_type',
        'value_type',
        'amount',
        'priority',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function branchProductOffering()
    {
        return $this->belongsTo(BranchProductOffering::class);
    }

    public function branchOfferingVariant()
    {
        return $this->belongsTo(BranchOfferingVariant::class);
    }

    public function branchPrintCapability()
    {
        return $this->belongsTo(BranchPrintCapability::class);
    }
}