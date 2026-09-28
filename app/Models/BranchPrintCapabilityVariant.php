<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchPrintCapabilityVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_print_capability_id',
        'branch_offering_variant_id',
    ];

    public function branchPrintCapability()
    {
        return $this->belongsTo(BranchPrintCapability::class);
    }

    public function branchOfferingVariant()
    {
        return $this->belongsTo(BranchOfferingVariant::class);
    }
}