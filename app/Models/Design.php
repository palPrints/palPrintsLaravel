<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Design extends Model
{
    protected $fillable = [
        'designer_id',
        'product_id',
        'title',
        'description',
        'image',
        'base_price',
        'selling_price',
        'designer_profit',
        'selected_options',
        'design_payload',
        'status',
        'submitted_at',
        'reviewed_at',
        'published_at',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'designer_profit' => 'decimal:2',
        'selected_options' => 'array',
        'design_payload' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
