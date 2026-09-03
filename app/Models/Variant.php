<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variant extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id', 'sku', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function values(): HasMany { return $this->hasMany(VariantValue::class); }
    public function providerOfferingVariants(): HasMany { return $this->hasMany(ProviderOfferingVariant::class); }
}
