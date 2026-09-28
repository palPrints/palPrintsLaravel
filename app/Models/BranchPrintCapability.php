<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchPrintCapability extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_print_area_id',
        'printing_method_id',
        'applies_to_all_variants',
        'max_width_mm',
        'max_height_mm',
        'is_active',
    ];

    protected $casts = [
        'applies_to_all_variants' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function branchPrintArea()
    {
        return $this->belongsTo(BranchPrintArea::class);
    }

    public function printingMethod()
    {
        return $this->belongsTo(PrintingMethod::class);
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