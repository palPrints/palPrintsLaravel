<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintCapabilityVariant extends Model
{
    public $timestamps = false;

    protected $fillable = ['print_capability_id', 'provider_offering_variant_id'];

    public function capability(): BelongsTo
    {
        return $this->belongsTo(PrintCapability::class, 'print_capability_id');
    }

    public function offeringVariant(): BelongsTo
    {
        return $this->belongsTo(ProviderOfferingVariant::class, 'provider_offering_variant_id');
    }
}
