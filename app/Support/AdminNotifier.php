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
