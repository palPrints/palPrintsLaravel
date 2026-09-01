<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintArea extends Model
{
    public $timestamps = false;

    protected $fillable = ['provider_offering_id', 'code', 'name', 'max_width_mm', 'max_height_mm', 'is_active'];

    protected function casts(): array
    {
        return ['max_width_mm' => 'decimal:2', 'max_height_mm' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(ProviderOffering::class, 'provider_offering_id');
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(PrintCapability::class);
    }
}
