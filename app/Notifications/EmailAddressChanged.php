<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to the OLD address when an account's email is changed, so the real owner notices if someone else changed it.
 * It is sent on demand (Notification::route('mail', $oldEmail)), because the account no longer has that address.
 */
class EmailAddressChanged extends Notification
{
    public function __construct(
        private readonly string $name,
        private readonly string $newEmail,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم تغيير البريد الإلكتروني لحسابك في PalPrints')
            ->greeting('مرحبًا '.$this->name)
            ->line('تم تغيير البريد الإلكتروني المرتبط بحسابك في PalPrints إلى: '.$this->maskedNewEmail().'.')
            ->line('وقت التغيير: '.now('Asia/Gaza')->locale('ar')->translatedFormat('j F Y، H:i').' (بتوقيت فلسطين).')
            ->line('إذا كنتِ أنتِ من أجرى التغيير فلا يلزم أي إجراء.')
            ->line('إذا لم تقومي بهذا التغيير، فقد يكون شخص آخر قد وصل إلى حسابك. تواصلي معنا فورًا من مركز المساعدة، وغيّري كلمة مرور حسابك.');
    }

    /** The new address is only partly shown: this mail goes to a mailbox that may no longer be the account's. */
    private function maskedNewEmail(): string
    {
        [$local, $domain] = array_pad(explode('@', $this->newEmail, 2), 2, '');

        return Str::substr($local, 0, 2).str_repeat('*', max(2, Str::length($local) - 2)).'@'.$domain;
    }
}
