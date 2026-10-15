<?php

namespace App\Services\WhatsApp;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintProvider;

/**
 * A wa.me "click to chat" link the admin opens to send the shop its new order from their own WhatsApp. No API and no
 * Meta setup: WhatsApp opens on the shop's number with the message written, and the admin presses send. The shop then
 * accepts or rejects on the dashboard. The message carries no customer data (name, phone, address).
 */
class ShopChatLink
{
    /** @return array{url: string|null, shop: string}|null null when the order is not routed to a shop yet */
    public static function forOrder(Order $order): ?array
    {
        $order->loadMissing('items.product', 'items.printProviderBranch.printProvider');

        $provider = $order->items->map(fn (OrderItem $item) => $item->printProviderBranch?->printProvider)->filter()->first();
        if (! $provider instanceof PrintProvider) {
            return null;
        }

        $items = $order->items->filter(fn (OrderItem $item) => $item->printProviderBranch?->print_provider_id === $provider->id);
        $number = PhoneNumber::normalize($provider->whatsapp_number);

        return [
            'url' => $number ? 'https://wa.me/'.$number.'?text='.rawurlencode(self::message($order, $provider, $items)) : null,
            'shop' => (string) $provider->company_name,
        ];
    }

    /** @param  \Illuminate\Support\Collection<int, OrderItem>  $items */
    private static function message(Order $order, PrintProvider $provider, $items): string
    {
        $products = $items->map(fn (OrderItem $item) => '• '.($item->product?->name ?? 'منتج').' × '.(int) $item->quantity)->implode("\n");

        return implode("\n", [
            'مرحبًا '.trim((string) $provider->company_name).'،',
            'وصلكم طلب جديد من PALPRINTS رقم '.$order->order_number.'.',
            '',
            'المنتجات:',
            $products,
            '',
            'مستحقاتكم: '.number_format((float) $items->sum('provider_cost'), 2).' ₪',
            '',
            'لقبول الطلب أو رفضه وتنزيل الملفات:',
            route('print-provider.requests'),
        ]);
    }
}
