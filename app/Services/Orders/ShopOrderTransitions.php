<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Support\OrderNotifier;

/**
 * A print shop's decisions on an order (accept, reject, ready). The dashboard and WhatsApp both go through here, so a
 * tap on WhatsApp and a click on the dashboard follow the same rules and cannot move the same order twice.
 */
class ShopOrderTransitions
{
    /** The admin approving the payment leaves the order "processing": it is new to the shop until the shop accepts it. */
    public const NEW_STATUSES = ['pending', 'processing'];

    public const PROGRESS_STATUSES = ['confirmed', 'in_production'];

    public function accept(Order $order, User $shopUser, string $via = 'dashboard'): bool
    {
        return $this->move($order, $shopUser, self::NEW_STATUSES, 'confirmed', null, $via);
    }

    public function reject(Order $order, User $shopUser, string $reason, string $via = 'dashboard'): bool
    {
        return $this->move($order, $shopUser, self::NEW_STATUSES, 'rejected', $reason, $via);
    }

    public function ready(Order $order, User $shopUser, string $via = 'dashboard'): bool
    {
        return $this->move($order, $shopUser, self::PROGRESS_STATUSES, 'ready', null, $via);
    }

    /** False when the order is no longer in one of the $from statuses (already decided, cancelled, …). */
    private function move(Order $order, User $shopUser, array $from, string $to, ?string $note, string $via): bool
    {
        $previous = Order::query()->whereKey($order->id)->value('status');

        // One conditional update, so a double click (or a tap racing a click) cannot move the same order twice.
        $moved = Order::query()->whereKey($order->id)->whereIn('status', $from)->update(['status' => $to, 'updated_at' => now()]);

        if ($moved === 0) {
            return false;
        }

        $order->status = $to;

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'changed_by' => $shopUser->id,
            'from_status' => $previous,
            'to_status' => $to,
            'note' => $note,
            'metadata' => $via === 'dashboard' ? null : ['via' => $via],
        ]);

        if ($order->user_id) {
            OrderNotifier::statusChanged((int) $order->user_id, $order->order_number, $to, $note);
        }

        if ($to === 'rejected') {
            OrderNotifier::rejectedByShop($order->order_number, (string) $shopUser->printProvider?->company_name, (string) $note);
        }

        return true;
    }
}
