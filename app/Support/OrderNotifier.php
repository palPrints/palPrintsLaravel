<?php

namespace App\Support;

use App\Models\Notification;

class OrderNotifier
{
    /** Order status => [title, message template]. "pending" is the initial state, so it never notifies. */
    private const MESSAGES = [
        'confirmed' => ['تم تأكيد طلبك', 'تم تأكيد طلبك رقم %s وسيبدأ تجهيزه قريبًا.'],
        'processing' => ['طلبك قيد التنفيذ', 'بدأ تنفيذ طلبك رقم %s.'],
        'in_production' => ['طلبك قيد التنفيذ', 'بدأ تنفيذ طلبك رقم %s.'],
        'shipped' => ['تم شحن طلبك', 'طلبك رقم %s في الطريق إليك.'],
        'delivered' => ['تم تسليم طلبك', 'تم تسليم طلبك رقم %s. نتمنى أن ينال إعجابك!'],
        'completed' => ['تم تنفيذ طلبك', 'تم الانتهاء من طلبك رقم %s.'],
        'cancelled' => ['تم إلغاء طلبك', 'تم إلغاء طلبك رقم %s.'],
    ];

    public static function statusChanged(int $userId, string $orderNumber, string $status): void
    {
        if (! isset(self::MESSAGES[$status])) {
            return;
        }

        [$title, $message] = self::MESSAGES[$status];

        Notification::create([
            'user_id' => $userId,
            'type' => 'order.'.$status,
            'title' => $title,
            'message' => sprintf($message, $orderNumber),
            'link' => route('customer.orders'),
        ]);
    }
}
