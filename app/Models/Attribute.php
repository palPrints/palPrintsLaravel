<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'code', 'data_type', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function values(): HasMany { return $this->hasMany(AttributeValue::class); }
    public function productAttributes(): HasMany { return $this->hasMany(ProductAttribute::class); }
}
