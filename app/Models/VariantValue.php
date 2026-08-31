<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantValue extends Model
{
    public $timestamps = false;
    protected $fillable = ['variant_id', 'product_attribute_value_id'];
    public function variant(): BelongsTo { return $this->belongsTo(Variant::class); }
    public function productAttributeValue(): BelongsTo { return $this->belongsTo(ProductAttributeValue::class); }
}
