<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeValue extends Model
{
    public $timestamps = false;
    protected $fillable = ['attribute_id', 'value', 'code', 'sort_order'];
    protected function casts(): array { return ['sort_order' => 'integer']; }
    public function attribute(): BelongsTo { return $this->belongsTo(Attribute::class); }
    public function productAttributeValues(): HasMany { return $this->hasMany(ProductAttributeValue::class); }
}
