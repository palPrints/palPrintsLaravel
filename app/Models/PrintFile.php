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
        'preview_path',
        'preview_disk',
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

    /** Where the studio's print-ready pictures live (one PNG per print area); the customer's own uploads are elsewhere. */
    public const EXPORT_DIR = 'customer-print-files/exports/';

    /** A picture the design studio drew for the print shop, not a file the customer uploaded. */
    public function isPrintReady(): bool
    {
        return str_starts_with((string) $this->stored_path, self::EXPORT_DIR);
    }

    /** A "print-ready-*" picture left in the temp folder by an earlier export attempt: the real one is in the export folder. */
    public function isDraftExport(): bool
    {
        return ! $this->isPrintReady() && str_starts_with((string) $this->original_name, 'print-ready-');
    }

    /** "print-ready-<area name>.png" => the area's name, for people to read. */
    public function areaLabel(): string
    {
        return (string) str($this->original_name)->after('print-ready-')->beforeLast('.png');
    }

    /** The area id kept in the stored name ("front--<uuid>.png"), safe for a file name inside the package. */
    public function areaSlug(): ?string
    {
        $slug = explode('--', basename((string) $this->stored_path), 2)[0];

        return preg_match('/^[a-z0-9_-]{1,30}$/i', $slug) && str_contains(basename((string) $this->stored_path), '--') ? strtolower($slug) : null;
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