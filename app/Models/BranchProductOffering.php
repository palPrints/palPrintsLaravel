<?php

namespace App\Models;

use App\Support\CatalogProductData;
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

    /**
     * Gives the offering a row per print area its product has, switched off until the shop ticks it. Rows that exist
     * (ticked or not) are left alone, so this is safe to call again.
     */
    public function ensurePrintAreas(): void
    {
        $product = $this->product ?? Product::find($this->product_id);
        if (! $product) {
            return;
        }

        foreach (CatalogProductData::areaDefinitions($product->code) as $area) {
            $this->branchPrintAreas()->firstOrCreate(
                ['code' => $area['code']],
                ['name' => $area['name'], 'max_width_mm' => $area['width'], 'max_height_mm' => $area['height'], 'is_active' => false],
            );
        }
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