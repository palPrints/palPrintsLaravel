<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StickerBundleLine extends Model
{
    use HasFactory;

    public const SOURCE_LIBRARY = 'library';
    public const SOURCE_UPLOAD = 'upload';

    protected $fillable = [
        'cart_item_id',
        'order_item_id',
        'source',
        'client_id',
        'upload_key',
        'sticker_library_item_id',
        'print_file_id',
        'quantity',
        'size_key',
        'width_mm',
        'height_mm',
        'cut_type',
        'label',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'width_mm' => 'decimal:2',
        'height_mm' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function libraryItem(): BelongsTo
    {
        return $this->belongsTo(StickerLibraryItem::class, 'sticker_library_item_id');
    }

    public function printFile(): BelongsTo
    {
        return $this->belongsTo(PrintFile::class);
    }
}
