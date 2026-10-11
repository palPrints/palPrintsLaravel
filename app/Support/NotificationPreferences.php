<?php

namespace App\Support;

/**
 * The admin's "Notifications" switches (Settings > Notifications) decide which notices the platform sends to users.
 * Only notices to a user about an event are switched here; the ones a shop or an admin has to act on are never held back.
 */
class NotificationPreferences
{
    /** Preference key => notification types it controls (exact type, or prefix when it ends with a dot). */
    private const GROUPS = [
        'order_updates' => [
            'order.confirmed', 'order.processing', 'order.in_production', 'order.ready', 'order.rejected',
            'order.shipped', 'order.delivered', 'order.completed', 'order.cancelled',
        ],
        'approval_results' => [
            'approval.approved', 'approval.rejected', 'approval.changes_requested', 'approval.under_review', 'design_review',
        ],
        'payments_withdrawals' => ['order.payment_approved', 'order.payment_rejected', 'withdrawal_review'],
    ];

    public static function allows(?string $type): bool
    {
        foreach (self::GROUPS as $preference => $types) {
            if (in_array((string) $type, $types, true)) {
                return (bool) PlatformSettings::get('notifications', $preference, true);
            }
        }

        return true;
    }
}
