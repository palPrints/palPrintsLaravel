<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderOffering extends Model
{
    public $timestamps = false;

    protected $fillable = ['provider_id', 'product_id', 'base_price', 'currency', 'production_time_min', 'production_time_max', 'daily_capacity', 'is_active'];

    protected function casts(): array
    {
        return ['base_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProviderOfferingVariant::class);
    }

    public function printAreas(): HasMany
    {
        return $this->hasMany(PrintArea::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }
}
