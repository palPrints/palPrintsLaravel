<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintFile extends Model
{
    use HasFactory;

    public const STATUS_TEMPORARY = 'temporary';
    public const STATUS_ATTACHED_TO_CART = 'attached_to_cart';
    public const STATUS_ATTACHED_TO_ORDER = 'attached_to_order';
    public const STATUS_SENT_TO_PROVIDER = 'sent_to_provider';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'user_id',
        'product_id',
        'cart_item_id',
        'order_item_id',
        'original_name',
        'stored_path',
        'disk',
        'mime_type',
        'extension',
        'file_size',
        'page_count',
        'status',
        'uploaded_at',
        'attached_to_order_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'page_count' => 'integer',
            'uploaded_at' => 'datetime',
            'attached_to_order_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}