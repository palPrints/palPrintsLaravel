<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttributeValue extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_attribute_id', 'attribute_value_id', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function productAttribute(): BelongsTo { return $this->belongsTo(ProductAttribute::class); }
    public function attributeValue(): BelongsTo { return $this->belongsTo(AttributeValue::class); }
    public function variantValues(): HasMany { return $this->hasMany(VariantValue::class); }
}
