<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The code that lets a signed-in user confirm a password change. Sent right away (not queued): the user is waiting for it. */
class PasswordChangeCodeNotification extends Notification
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
            ->subject('رمز التحقق لتغيير كلمة المرور في PalPrints')
            ->greeting('مرحبًا '.$this->name)
            ->line('طلبتِ تغيير كلمة مرور حسابك في PalPrints. أدخلي الرمز التالي لتأكيد التغيير:')
            ->line('**'.$this->code.'**')
            ->line('الرمز صالح لمدة '.$this->minutes.' دقائق ويُستخدم مرة واحدة.')
            ->line('إذا لم تطلبي هذا التغيير فتجاهلي الرسالة، وكلمة مرورك لن تتغير.');
    }
}
