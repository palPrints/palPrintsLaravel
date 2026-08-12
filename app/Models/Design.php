<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    use HasFactory;

    protected $fillable = [
        'designer_id',
        'title',
        'description',
        'image_url',
        'file_url',
        'file_size',
        'file_format',
        'base_price',
        'royalty_percentage',
        'avg_rating',
        'total_reviews',
        'total_sales',
        'status',
        'is_featured',
        'rejection_reason',
        'published_at',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'royalty_percentage' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    // ========== العلاقات ==========

    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function designProducts()
    {
        return $this->hasMany(DesignProduct::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
