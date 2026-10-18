<?php

namespace App\Support;

/** The icon each kind of notification gets in the bell menus of every account type. */
class NotificationStyle
{
    private const ICONS = [
        'design_published' => 'bi-patch-check',
        'design_review' => 'bi-hourglass-split',
        'design_rejected' => 'bi-x-octagon',
        'design.' => 'bi-palette',
        'withdrawal' => 'bi-cash-coin',
        'approval.' => 'bi-send-check',
        'order.payment' => 'bi-credit-card',
        'order' => 'bi-bag-check',
        'user.' => 'bi-people',
        'support.' => 'bi-headset',
    ];

    public static function icon(?string $type): string
    {
        foreach (self::ICONS as $prefix => $icon) {
            if (str_starts_with((string) $type, $prefix)) {
                return $icon;
            }
        }

        return 'bi-bell';
    }
}
