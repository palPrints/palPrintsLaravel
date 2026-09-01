<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderOfferingVariant extends Model
{
    public $timestamps = false;

    protected $fillable = ['provider_offering_id', 'variant_id', 'provider_sku', 'is_available'];

    protected function casts(): array
    {
        return ['is_available' => 'boolean'];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(ProviderOffering::class, 'provider_offering_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function capabilityVariants(): HasMany
    {
        return $this->hasMany(PrintCapabilityVariant::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }
}
