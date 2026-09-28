<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'status',
        'is_featured',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_featured' => 'boolean',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}