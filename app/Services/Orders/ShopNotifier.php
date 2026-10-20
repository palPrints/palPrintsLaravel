<?php

namespace App\Services\Orders;

use App\Jobs\SendOrderOfferToShop;
use App\Jobs\SendOrderToTelegram;
use App\Models\Order;
use App\Services\WhatsApp\ShopChatLink;

/**
 * Tells a shop about an order the admin just routed to it, on the channels the shop chose in its profile:
 * Telegram (sent automatically, with the files and Accept / Reject buttons) and WhatsApp (automatically through the
 * Cloud API when it is on, otherwise a ready wa.me message for the admin). The in-app notification is sent separately.
 */
class ShopNotifier
{
    /** @return array{url: string|null, shop: string, telegram: bool}|null what the admin is shown (see admin.partials.shop-notice) */
    public function newOrder(Order $order, int $printProviderId): ?array
    {
        if ($order->status === 'processing-pdf') {
            return null;
        }
        SendOrderOfferToShop::dispatch($order->id, $printProviderId);
        SendOrderToTelegram::dispatch($order->id, $printProviderId);
        \App\Jobs\SendStickerFilesToWhatsApp::dispatch($order->id, $printProviderId);

        return ShopChatLink::forOrder($order->fresh());
    }
}
