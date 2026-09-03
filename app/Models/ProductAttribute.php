<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id', 'attribute_id', 'is_variant_axis', 'is_required', 'sort_order'];
    protected function casts(): array { return ['is_variant_axis' => 'boolean', 'is_required' => 'boolean', 'sort_order' => 'integer']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function attribute(): BelongsTo { return $this->belongsTo(Attribute::class); }
    public function values(): HasMany { return $this->hasMany(ProductAttributeValue::class); }
}
