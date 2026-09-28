<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchPrintArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_product_offering_id',
        'code',
        'name',
        'max_width_mm',
        'max_height_mm',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branchProductOffering()
    {
        return $this->belongsTo(BranchProductOffering::class);
    }

    public function branchPrintCapabilities()
    {
        return $this->hasMany(BranchPrintCapability::class);
    }
}