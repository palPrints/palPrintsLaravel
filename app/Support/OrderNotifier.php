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

    /** The admin's decision on the customer's payment notice. */
    public static function paymentReviewed(int $userId, string $orderNumber, bool $approved, ?string $reason = null): void
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $approved ? 'order.payment_approved' : 'order.payment_rejected',
            'title' => $approved ? 'تمت الموافقة على دفعتك' : 'تم رفض إشعار الدفع',
            'message' => $approved
                ? sprintf('تمت الموافقة على إشعار دفع طلبك رقم %s وتم توجيهه للتنفيذ.', $orderNumber)
                : sprintf('تم رفض إشعار دفع طلبك رقم %s وإلغاء الطلب. السبب: %s', $orderNumber, $reason),
            'link' => route('customer.orders'),
        ]);
    }

    /** A paid order was routed to this shop (the admin approved the payment): tells the shop's owner. */
    public static function newOrderForShop(int $providerUserId, string $orderNumber, int $itemsCount): void
    {
        Notification::create([
            'user_id' => $providerUserId,
            'type' => 'order.new_for_provider',
            'title' => 'طلب جديد لمطبعتك',
            'message' => sprintf('وصلك طلب جديد رقم %s (%d منتج). راجعيه من صفحة الطلبات.', $orderNumber, $itemsCount),
            'link' => route('print-provider.requests'),
        ]);
    }

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
