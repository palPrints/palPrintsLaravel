<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;

class AdminNotifier
{
    /** Notification type prefix => [filter key, Arabic label, icon]. First match wins, so keep the specific prefixes first. */
    public const CATEGORIES = [
        'payment' => ['label' => 'الدفع', 'icon' => 'bi-credit-card', 'prefixes' => ['order.payment', 'withdrawal']],
        'orders' => ['label' => 'الطلبات', 'icon' => 'bi-cart3', 'prefixes' => ['order.']],
        'users' => ['label' => 'المستخدمون والمطابع', 'icon' => 'bi-people', 'prefixes' => ['user.', 'approval.']],
        'designs' => ['label' => 'التصاميم', 'icon' => 'bi-palette', 'prefixes' => ['design.']],
        'support' => ['label' => 'الدعم', 'icon' => 'bi-chat-dots', 'prefixes' => ['support.']],
    ];

    public static function toAdmins(string $type, string $title, string $message, ?string $link = null): void
    {
        User::role('admin')->where('is_active', true)->get()->each(fn (User $admin) => Notification::create([
            'user_id' => $admin->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]));
    }

    /**
     * Where a notification should take the admin: the very item it is about (one payment notice, one order, one withdrawal,
     * one design, one support request, one account), not just the page that lists them. The pages open an item from
     * `?open=<id>` (payment notices from `?order=<number>`). Notifications saved before their link carried the id are
     * matched from the text of the message, so they keep working.
     */
    public static function linkFor(Notification $notification): ?string
    {
        $link = $notification->link;
        $message = (string) $notification->message;
        $title = (string) $notification->title;
        $page = fn (string $route) => $link === route($route);
        $orderNumber = preg_match('/رقم\s+([A-Za-z0-9_\-]+)/u', $message, $match) ? $match[1] : null;

        switch ($notification->type) {
            case 'order.payment_review':
                if ($page('admin.payment-notices') && $orderNumber) {
                    return route('admin.payment-notices', ['order' => $orderNumber]);
                }
                break;

            case 'order.payment_cancelled':
            case 'order.refund_needed':
            case 'order.rejected_by_provider':
                if ($page('admin.orders') && $orderNumber) {
                    return route('admin.orders', ['open' => $orderNumber]);
                }
                break;

            case 'withdrawal.requested':
                if ($page('admin.payments') && preg_match('/WDR-[A-Z0-9]+/', $message, $match)
                    && ($id = \App\Models\WithdrawalRequest::where('reference_id', $match[0])->value('id'))) {
                    return route('admin.payments', ['open' => $id]);
                }
                break;

            case 'approval.pending':
                if ($page('admin.users') && preg_match('/رقم الطلب:\s*(\S+)/u', $message, $match)
                    && ($userId = \App\Models\ApprovalRequest::where('request_number', $match[1])->value('user_id'))) {
                    return route('admin.users', ['open' => $userId]);
                }
                break;

            case 'design.submitted':
                if ($page('admin.designs') && preg_match('/«(.+?)»/u', $message, $match)
                    && ($id = \App\Models\Design::where('title', $match[1])->latest('id')->value('id'))) {
                    return route('admin.designs', ['open' => $id]);
                }
                break;

            case 'support.new':
                if ($page('admin.support') && preg_match('/دعم:\s*(.+)$/u', $message, $match)
                    && ($id = \App\Models\SupportTicket::where('subject', trim($match[1]))->latest('id')->value('id'))) {
                    return route('admin.support.show', $id);
                }
                break;
        }

        return $link;
    }

    public static function categoryOf(?string $type): string
    {
        foreach (self::CATEGORIES as $key => $category) {
            foreach ($category['prefixes'] as $prefix) {
                if (str_starts_with((string) $type, $prefix)) {
                    return $key;
                }
            }
        }

        return 'other';
    }

    /** SQL "type LIKE …" conditions for one category, as a closure for ->where(). */
    public static function scope(string $key): \Closure
    {
        return function ($query) use ($key) {
            $query->where(function ($any) use ($key) {
                foreach (self::CATEGORIES[$key]['prefixes'] as $prefix) {
                    $any->orWhere('type', 'like', $prefix.'%');
                }
            });

            if ($key === 'orders') {
                $query->where('type', 'not like', 'order.payment%');
            }
        };
    }
}
