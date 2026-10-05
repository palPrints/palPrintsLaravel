<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The code that verifies the account's email address. Sent right away (not queued): the user is waiting for it. */
class EmailVerificationCodeNotification extends Notification
{
    public function __construct(
        private readonly string $name,
        private readonly string $code,
        private readonly int $minutes,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('رمز توثيق بريدك الإلكتروني في PalPrints')
            ->greeting('مرحبًا '.$this->name)
            ->line('أدخلي الرمز التالي في صفحة التوثيق لتأكيد بريدك الإلكتروني:')
            ->line('**'.$this->code.'**')
            ->line('الرمز صالح لمدة '.$this->minutes.' دقائق ويُستخدم مرة واحدة.')
            ->line('إذا لم تطلبي التوثيق فتجاهلي هذه الرسالة.');
    }
}
