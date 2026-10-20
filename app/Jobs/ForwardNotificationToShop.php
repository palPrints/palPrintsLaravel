<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\PrintProvider;
use App\Models\WhatsAppMessage;
use App\Services\Telegram\TelegramClient;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Sends a print shop's in-app notification to the chats it chose: Telegram when it connected the bot, WhatsApp when the
 * Cloud API is on and it opted in (an approved template, since the shop has not written to us). The bell stays the
 * record; a chat that fails changes nothing on the platform.
 */
class ForwardNotificationToShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Notices that already reach the shop's chats in a fuller form: a new order arrives there with its files and the
     * Accept / Reject buttons (SendOrderToTelegram, SendOrderOfferToShop), so its bell notice is not sent again.
     */
    public const SENT_ELSEWHERE = ['order.new_for_provider'];

    public int $tries = 3;

    public function __construct(public int $notificationId)
    {
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    /** Queues the forward only for a print shop with a chat to send to, so other accounts cost nothing. */
    public static function forward(Notification $notification): void
    {
        if (in_array($notification->type, self::SENT_ELSEWHERE, true) || ! (config('telegram.enabled') || config('whatsapp.enabled'))) {
            return;
        }

        $provider = PrintProvider::query()->where('user_id', $notification->user_id)->first();
        if ($provider && ($provider->telegramRecipient() || $provider->whatsappRecipient())) {
            self::dispatch($notification->id);
        }
    }

    public function handle(TelegramClient $telegram, WhatsAppClient $whatsapp): void
    {
        $notification = Notification::query()->find($this->notificationId);
        $provider = $notification ? PrintProvider::query()->where('user_id', $notification->user_id)->first() : null;
        if (! $notification || ! $provider) {
            return;
        }

        $link = $notification->link && str_starts_with($notification->link, url('/')) ? $notification->link : null;

        if (config('telegram.enabled') && ($chatId = $provider->telegramRecipient())) {
            $this->quietlyOn4xx(fn () => $telegram->sendMessage($chatId, implode("\n", array_filter([
                '🔔 <b>'.TelegramClient::e($notification->title).'</b>',
                TelegramClient::e($notification->message),
                $link ? TelegramClient::e($link) : null,
            ]))));
        }

        $template = config('whatsapp.notification_template');
        if (config('whatsapp.enabled') && filled($template['name'] ?? null) && ($to = $provider->whatsappRecipient())) {
            $this->quietlyOn4xx(function () use ($whatsapp, $to, $template, $notification, $provider) {
                $sent = $whatsapp->sendTemplate($to, $template['name'], $template['language'], [$notification->title, $notification->message]);

                WhatsAppMessage::create([
                    'direction' => 'out', 'print_provider_id' => $provider->id, 'purpose' => 'notification', 'wa_id' => $to,
                    'wa_message_id' => $sent['id'], 'type' => 'template', 'payload' => $sent['request'], 'status' => 'accepted',
                ]);
            });
        }
    }

    /** A 4xx (shop blocked the bot, number not on WhatsApp, template missing) will fail the same way again: log, move on. */
    private function quietlyOn4xx(callable $send): void
    {
        try {
            $send();
        } catch (RequestException $exception) {
            if ($exception->response->clientError() && $exception->response->status() !== 429) {
                report($exception);

                return;
            }

            throw $exception;
        }
    }
}
