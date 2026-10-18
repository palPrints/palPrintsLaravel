<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StickerLibraryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sticker_category_id',
        'name',
        'image_path',
        'disk',
        'mime_type',
        'file_size',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StickerCategory::class, 'sticker_category_id');
    }

    public function imageUrl(): string
    {
        return route('stickers.library-image', $this);
    }
}
