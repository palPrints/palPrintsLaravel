<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintProviderBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'print_provider_id',
        'name',
        'city',
        'region',
        'address',
        'phone',
        'working_hours',
        'is_active',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'is_active' => 'boolean',
    ];

    public function printProvider()
    {
        return $this->belongsTo(PrintProvider::class);
    }

    public function branchProductOfferings()
    {
        return $this->hasMany(BranchProductOffering::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
