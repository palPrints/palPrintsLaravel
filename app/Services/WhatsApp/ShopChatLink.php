<?php

namespace App\Services\WhatsApp;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintFile;
use App\Models\PrintProvider;
use Illuminate\Support\Facades\URL;

/**
 * A wa.me "click to chat" link the admin opens to send the shop its new order from their own WhatsApp. No API and no
 * Meta setup: WhatsApp opens on the shop's number with the message written, and the admin presses send. The shop then
 * accepts or rejects on the dashboard. The message carries no customer data (name, phone, address).
 */
class ShopChatLink
{
    /** How long the file links in the message keep working. */
    private const FILE_LINK_DAYS = 7;

    /** Keeps the wa.me URL a sane length; the rest of the files stay on the shop's orders page. */
    private const MAX_FILES_PER_ITEM = 10;

    /**
     * url: the wa.me link, only when the shop chose to get orders on WhatsApp and has a number. telegram: the shop gets
     * its orders from the bot, so it was sent there automatically.
     *
     * @return array{url: string|null, shop: string, telegram: bool}|null null when the order is not routed to a shop yet
     */
    public static function forOrder(Order $order): ?array
    {
        $order->loadMissing('items.product', 'items.printFiles', 'items.printProviderBranch.printProvider');

        $provider = $order->items->map(fn (OrderItem $item) => $item->printProviderBranch?->printProvider)->filter()->first();
        if (! $provider instanceof PrintProvider) {
            return null;
        }

        $items = $order->items->filter(fn (OrderItem $item) => $item->printProviderBranch?->print_provider_id === $provider->id);
        $number = $provider->whatsapp_notifications_enabled ? PhoneNumber::normalize($provider->whatsapp_number) : null;

        return [
            'url' => $number ? 'https://wa.me/'.$number.'?text='.rawurlencode(self::message($order, $provider, $items)) : null,
            'shop' => (string) $provider->company_name,
            'telegram' => config('telegram.enabled') && filled($provider->telegram_chat_id),
        ];
    }

    /** @param  \Illuminate\Support\Collection<int, OrderItem>  $items */
    private static function message(Order $order, PrintProvider $provider, $items): string
    {
        // WhatsApp picks each line's direction from its first letter, so an Arabic line holding a Latin product name or an
        // order number came out scrambled. Every Arabic line starts with a right-to-left mark, and no punctuation is left
        // right after a Latin run (it would jump to the wrong side).
        $rtl = "\u{200F}";
        $line = fn (string $text) => $rtl.$text;

        // Each product, then a download link for each of its print files (signed, no login needed, expires).
        $products = $items->flatMap(fn (OrderItem $item) => [
            $line('• '.($item->product?->name ?? 'منتج').$rtl.' — الكمية: '.(int) $item->quantity),
            ...$item->printFiles
                ->reject(fn (PrintFile $file) => in_array($file->status, [PrintFile::STATUS_DELETED, PrintFile::STATUS_CANCELLED], true))
                ->take(self::MAX_FILES_PER_ITEM)
                ->flatMap(fn (PrintFile $file) => [
                    $line('📄 '.$file->original_name),
                    URL::temporarySignedRoute('print-files.shared', now()->addDays(self::FILE_LINK_DAYS), $file),
                ])->all(),
        ]);

        return implode("\n", [
            $line('مرحبًا '.trim((string) $provider->company_name).$rtl.'،'),
            $line('وصلكم طلب جديد من منصة PALPRINTS'),
            $line('رقم الطلب: '.$order->order_number),
            '',
            $line('المنتجات:'),
            ...$products->all(),
            '',
            $line('مستحقاتكم: '.number_format((float) $items->sum('provider_cost'), 2).' ₪'),
            '',
            $line('روابط الملفات صالحة لمدة '.self::FILE_LINK_DAYS.' أيام.'),
            '',
            $line('لقبول الطلب أو رفضه:'),
            route('print-provider.requests'),
        ]);
    }
}
