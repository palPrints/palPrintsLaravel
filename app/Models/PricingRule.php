<?php

namespace App\Models;

use App\Enums\PricingValueType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    public $timestamps = false;

    protected $fillable = ['provider_offering_id', 'provider_offering_variant_id', 'print_capability_id', 'min_quantity', 'max_quantity', 'pricing_type', 'value_type', 'amount', 'priority', 'is_active', 'valid_from', 'valid_until'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_active' => 'boolean', 'valid_from' => 'datetime', 'valid_until' => 'datetime', 'value_type' => PricingValueType::class];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(ProviderOffering::class, 'provider_offering_id');
    }

    public function offeringVariant(): BelongsTo
    {
        return $this->belongsTo(ProviderOfferingVariant::class, 'provider_offering_variant_id');
    }

    public function printCapability(): BelongsTo
    {
        return $this->belongsTo(PrintCapability::class);
    }
}
