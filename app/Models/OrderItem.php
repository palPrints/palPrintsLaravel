<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    public const TYPE_CATALOG_DESIGN = 'catalog_design';
    public const TYPE_CUSTOMER_UPLOAD = 'customer_upload';

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'design_id',
        'designer_id',
        'item_type',
        'print_provider_branch_id',
        'branch_product_offering_id',
        'quantity',
        'unit_price',
        'total_price',
        'provider_cost',
        'designer_profit',
        'platform_commission',
        'selected_options',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'provider_cost' => 'decimal:2',
        'designer_profit' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'selected_options' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function design()
    {
        return $this->belongsTo(Design::class);
    }

    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function printProviderBranch()
    {
        return $this->belongsTo(PrintProviderBranch::class);
    }

    public function branchProductOffering()
    {
        return $this->belongsTo(BranchProductOffering::class);
    }

    public function printFiles()
    {
        return $this->hasMany(PrintFile::class);
    }
}
