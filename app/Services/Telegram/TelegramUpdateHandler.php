<?php

namespace App\Services\Telegram;

use App\Models\Order;
use App\Models\PrintProvider;
use App\Models\TelegramMessage;
use App\Services\Orders\ShopOrderTransitions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Handles one update from the bot, whether it came by webhook or by polling:
 *  - "/start <token>": the shop opened the connect link from its profile, so its chat is saved.
 *  - a tap on Accept / Reject under an order. Accept is one tap; Reject asks for the reason first (the dashboard requires
 *    one too) and the shop's next message becomes the reason ("إلغاء" cancels).
 * Telegram resends an update it thinks was not received, so each update id is handled once.
 */
class TelegramUpdateHandler
{
    private const CANCEL_WORDS = ['الغاء', 'إلغاء', 'تراجع', 'cancel', '/cancel'];

    public function __construct(private TelegramClient $client, private ShopOrderTransitions $transitions)
    {
    }

    /** Remembers a connect token for the shop; the t.me link carries it to the bot. */
    public static function issueLinkToken(PrintProvider $provider): string
    {
        $token = Str::random(32);
        Cache::put('telegram:link:'.$token, $provider->id, now()->addMinutes((int) config('telegram.link_minutes', 60)));

        return $token;
    }

    /** @param  array<string, mixed>  $update */
    public function handle(array $update): void
    {
        if (isset($update['update_id']) && ! Cache::add('telegram:update:'.$update['update_id'], true, now()->addDay())) {
            return; // already handled
        }

        try {
            if (isset($update['callback_query'])) {
                $this->buttonTapped($update['callback_query']);
            } elseif (isset($update['message']['text'])) {
                $this->textReceived($update['message']);
            }
        } catch (Throwable $exception) {
            // One bad update must not stop the others (nor make Telegram resend it forever).
            report($exception);
        }
    }

    /** @param  array<string, mixed>  $message */
    private function textReceived(array $message): void
    {
        $chatId = (string) Arr::get($message, 'chat.id');
        $text = trim((string) $message['text']);

        if (Arr::get($message, 'chat.type', 'private') !== 'private') {
            return;
        }

        if (str_starts_with($text, '/start')) {
            $this->connect($chatId, trim(Str::after($text, '/start')), Arr::get($message, 'from.username'));

            return;
        }

        $offerId = Cache::get($this->reasonKey($chatId));
        $offer = $offerId ? TelegramMessage::query()->with(['order', 'printProvider.user'])->find($offerId) : null;
        if (! $offer || ! $offer->order || ! $offer->printProvider?->user) {
            $this->say($chatId, 'تصلك هنا طلبات PALPRINTS الجديدة. للقبول أو الرفض استخدمي الأزرار تحت كل طلب.');

            return;
        }

        $order = $offer->order;

        if (in_array(mb_strtolower($text), self::CANCEL_WORDS, true)) {
            Cache::forget($this->reasonKey($chatId));
            $this->say($chatId, sprintf('تم إلغاء الرفض. الطلب رقم %s ما زال بانتظار ردك.', $order->order_number), $offer->telegram_message_id);

            return;
        }

        if (mb_strlen($text) < 3) {
            $this->say($chatId, 'السبب قصير جدًا، اكتبي سبب الرفض بوضوح.');

            return;
        }

        Cache::forget($this->reasonKey($chatId));

        if ($this->transitions->reject($order, $offer->printProvider->user, Str::limit($text, 500, ''), 'telegram')) {
            $this->dropButtons($offer);
            $this->say($chatId, sprintf('تم رفض الطلب رقم %s وإبلاغ فريق PALPRINTS. شكرًا لك.', $order->order_number), $offer->telegram_message_id);
        } else {
            $this->say($chatId, $this->alreadyDecided($order), $offer->telegram_message_id);
        }
    }

    private function connect(string $chatId, string $token, ?string $username): void
    {
        $providerId = $token === '' ? null : Cache::pull('telegram:link:'.$token);
        $provider = $providerId ? PrintProvider::query()->find($providerId) : null;

        if (! $provider) {
            $this->say($chatId, 'لربط مطبعتك افتحي ملف المطبعة على PALPRINTS واضغطي "ربط تيليجرام". إذا مرّ وقت طويل على الرابط اطلبي رابطًا جديدًا.');

            return;
        }

        $provider->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username ? Str::limit($username, 64, '') : null,
            'telegram_linked_at' => now(),
        ]);

        $this->say($chatId, sprintf('✅ تم ربط %s. ستصلك هنا الطلبات الجديدة مع ملفات الطباعة وأزرار القبول والرفض.', TelegramClient::e($provider->company_name)));
    }

    /** @param  array<string, mixed>  $query */
    private function buttonTapped(array $query): void
    {
        $queryId = (string) ($query['id'] ?? '');
        [$action, $token] = array_pad(explode(':', (string) ($query['data'] ?? ''), 2), 2, '');
        $chatId = (string) Arr::get($query, 'message.chat.id');
        $messageId = (int) Arr::get($query, 'message.message_id');

        $offer = $token === '' ? null : TelegramMessage::query()
            ->where(['purpose' => 'offer', 'action_token' => $token])
            ->with(['order', 'printProvider.user'])
            ->first();

        // The button data says which order; it only counts in the chat we sent it to, on that very message.
        if (! $offer || $offer->chat_id !== $chatId || (int) $offer->telegram_message_id !== $messageId || ! $offer->order || ! $offer->printProvider?->user) {
            $this->answer($queryId, 'هذا الزر لم يعد صالحًا.');

            return;
        }

        $order = $offer->order;

        if ($action === 'acc') {
            if ($this->transitions->accept($order, $offer->printProvider->user, 'telegram')) {
                $this->answer($queryId, 'تم قبول الطلب ✅');
                $this->dropButtons($offer);
                $this->say($chatId, sprintf('✅ تم قبول الطلب رقم %s. بالتوفيق في التنفيذ!', $order->order_number), $messageId);
            } else {
                $this->answer($queryId, 'الطلب لم يعد بانتظار ردك.');
                $this->dropButtons($offer);
            }

            return;
        }

        if ($action === 'rej') {
            if (! in_array($order->fresh()->status, ShopOrderTransitions::NEW_STATUSES, true)) {
                $this->answer($queryId, 'الطلب لم يعد بانتظار ردك.');
                $this->dropButtons($offer);

                return;
            }

            Cache::put($this->reasonKey($chatId), $offer->id, now()->addMinutes((int) config('telegram.reject_reason_minutes', 30)));
            $this->answer($queryId);
            $this->say($chatId, sprintf('لرفض الطلب رقم %s اكتبي سبب الرفض في رسالة واحدة. لإلغاء الرفض أرسلي: إلغاء', $order->order_number), $messageId, true);
        }
    }

    private function alreadyDecided(Order $order): string
    {
        return sprintf('لا يمكن تنفيذ هذا الإجراء، الطلب رقم %s لم يعد بانتظار ردك.', $order->order_number);
    }

    private function dropButtons(TelegramMessage $offer): void
    {
        $this->quietly(fn () => $this->client->removeButtons($offer->chat_id, (int) $offer->telegram_message_id));
    }

    private function answer(string $queryId, ?string $text = null): void
    {
        if ($queryId !== '') {
            $this->quietly(fn () => $this->client->answerCallbackQuery($queryId, $text));
        }
    }

    private function say(string $chatId, string $html, ?int $replyTo = null, bool $forceReply = false): void
    {
        $this->quietly(fn () => $this->client->sendMessage($chatId, $html, null, $replyTo, $forceReply));
    }

    /** A reply that fails (network, shop blocked the bot) must not undo the decision already saved. */
    private function quietly(callable $call): void
    {
        try {
            $call();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function reasonKey(string $chatId): string
    {
        return 'telegram:reject-reason:'.$chatId;
    }
}
