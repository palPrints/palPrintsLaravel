<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintCapability extends Model
{
    public $timestamps = false;

    protected $fillable = ['print_area_id', 'printing_method_id', 'applies_to_all_variants', 'max_width_mm', 'max_height_mm', 'is_active'];

    protected function casts(): array
    {
        return ['applies_to_all_variants' => 'boolean', 'max_width_mm' => 'decimal:2', 'max_height_mm' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function printArea(): BelongsTo
    {
        return $this->belongsTo(PrintArea::class);
    }

    public function printingMethod(): BelongsTo
    {
        return $this->belongsTo(PrintingMethod::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(PrintCapabilityVariant::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }
}
