<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppReply;
use App\Models\Order;
use App\Models\WhatsAppMessage;
use App\Services\Orders\ShopOrderTransitions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Handles one verified webhook POST from Meta: delivery statuses of our messages and the shop's replies.
 *
 * Accept is a single tap. Reject asks for the reason first (the dashboard requires one too): the tap is remembered for a
 * while and the shop's next text message becomes the reason. Meta can deliver the same event more than once, so each
 * inbound message is stored under its unique wamid and a repeat is ignored.
 */
class WebhookHandler
{
    private const CANCEL_WORDS = ['الغاء', 'إلغاء', 'تراجع', 'cancel'];

    public function __construct(private ShopOrderTransitions $transitions)
    {
    }

    /** @param  array<string, mixed>  $payload */
    public function handle(array $payload): void
    {
        foreach (Arr::get($payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                foreach (Arr::get($change, 'value.statuses', []) as $status) {
                    $this->safely(fn () => $this->updateStatus($status));
                }

                foreach (Arr::get($change, 'value.messages', []) as $message) {
                    $this->safely(fn () => $this->receive($message));
                }
            }
        }
    }

    /** One bad event must not make Meta retry the whole batch for a week. */
    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param  array<string, mixed>  $status */
    private function updateStatus(array $status): void
    {
        $message = WhatsAppMessage::query()->where('direction', 'out')->where('wa_message_id', $status['id'] ?? '')->first();
        $new = (string) ($status['status'] ?? '');
        if (! $message) {
            return;
        }

        $at = isset($status['timestamp']) ? now()->setTimestamp((int) $status['timestamp']) : now();

        if ($new === 'failed') {
            // A late "failed" never overrides a message the shop already received.
            if (! in_array($message->status, ['delivered', 'read'], true)) {
                $message->update([
                    'status' => 'failed',
                    'failed_at' => $at,
                    'error' => Str::limit((string) (Arr::get($status, 'errors.0.title') ?? Arr::get($status, 'errors.0.code')), 500),
                ]);
            }

            return;
        }

        $rank = WhatsAppMessage::STATUS_RANK;
        if (! isset($rank[$new]) || $rank[$new] <= ($rank[$message->status] ?? -1)) {
            return;
        }

        $message->update(['status' => $new, $new.'_at' => $at] + ($new === 'read' && ! $message->delivered_at ? ['delivered_at' => $at] : []));
    }

    /** @param  array<string, mixed>  $message */
    private function receive(array $message): void
    {
        $from = PhoneNumber::normalize((string) ($message['from'] ?? ''));
        $id = (string) ($message['id'] ?? '');
        if (! $from || $id === '') {
            return;
        }

        $stored = WhatsAppMessage::query()->insertOrIgnore([
            'direction' => 'in',
            'wa_id' => $from,
            'wa_message_id' => $id,
            'context_wa_message_id' => Arr::get($message, 'context.id'),
            'type' => Str::limit((string) ($message['type'] ?? 'unknown'), 20, ''),
            'payload' => json_encode($message, JSON_UNESCAPED_UNICODE),
            'status' => 'received',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($stored === 0) {
            return; // already handled
        }

        match ($message['type'] ?? null) {
            'button' => $this->buttonTapped($from, (string) Arr::get($message, 'button.payload'), Arr::get($message, 'context.id')),
            'text' => $this->textReceived($from, (string) Arr::get($message, 'text.body')),
            default => null,
        };
    }

    private function buttonTapped(string $from, string $payload, ?string $contextId): void
    {
        [$action, $token] = array_pad(explode(':', $payload, 2), 2, '');

        $offer = $token === '' ? null : WhatsAppMessage::query()
            ->where(['direction' => 'out', 'purpose' => 'offer', 'action_token' => $token])
            ->with(['order', 'printProvider.user'])
            ->first();

        // The payload token says which order; it only counts from the number we sent it to, and when Meta tells us which
        // message was answered it has to be that offer.
        if (! $offer || ! PhoneNumber::sameSubscriber($offer->wa_id, $from) ||($contextId && $offer->wa_message_id && $contextId !== $offer->wa_message_id)) {
            return;
        }

        $order = $offer->order;
        $shopUser = $offer->printProvider?->user;
        if (! $order || ! $shopUser) {
            return;
        }

        $this->linkInbound($from, $offer);

        if ($action === 'accept') {
            $this->reply($offer, $this->transitions->accept($order, $shopUser, 'whatsapp')
                ? sprintf('✅ تم قبول الطلب رقم %s. تجدين الملفات والتفاصيل في صفحة الطلبات: %s', $order->order_number, route('print-provider.requests'))
                : $this->alreadyDecided($order));

            return;
        }

        if ($action === 'reject') {
            if (! in_array($order->fresh()->status, ShopOrderTransitions::NEW_STATUSES, true)) {
                $this->reply($offer, $this->alreadyDecided($order));

                return;
            }

            Cache::put($this->reasonKey($from), $offer->id, now()->addMinutes((int) config('whatsapp.reject_reason_minutes', 30)));
            $this->reply($offer, sprintf('لرفض الطلب رقم %s اكتبي سبب الرفض في رسالة واحدة. لإلغاء الرفض أرسلي: إلغاء', $order->order_number));
        }
    }

    private function textReceived(string $from, string $body): void
    {
        $offerId = Cache::get($this->reasonKey($from));
        $offer = $offerId ? WhatsAppMessage::query()->with(['order', 'printProvider.user'])->find($offerId) : null;
        if (! $offer || ! $offer->order || ! $offer->printProvider?->user) {
            return;
        }

        $this->linkInbound($from, $offer);
        $reason = trim($body);
        $order = $offer->order;

        if (in_array(mb_strtolower($reason), self::CANCEL_WORDS, true)) {
            Cache::forget($this->reasonKey($from));
            $this->reply($offer, sprintf('تم إلغاء الرفض. الطلب رقم %s ما زال بانتظار ردك.', $order->order_number));

            return;
        }

        if (mb_strlen($reason) < 3) {
            $this->reply($offer, 'السبب قصير جدًا، اكتبي سبب الرفض بوضوح.');

            return;
        }

        Cache::forget($this->reasonKey($from));

        $this->reply($offer, $this->transitions->reject($order, $offer->printProvider->user, Str::limit($reason, 500, ''), 'whatsapp')
            ? sprintf('تم رفض الطلب رقم %s وإبلاغ فريق PALPRINTS. شكرًا لك.', $order->order_number)
            : $this->alreadyDecided($order));
    }

    /** Ties the inbound message we just stored to the order it is about, for the admin's message log. */
    private function linkInbound(string $from, WhatsAppMessage $offer): void
    {
        WhatsAppMessage::query()
            ->where(['direction' => 'in', 'wa_id' => $from])
            ->whereNull('order_id')
            ->latest('id')
            ->limit(1)
            ->update(['order_id' => $offer->order_id, 'print_provider_id' => $offer->print_provider_id]);
    }

    private function alreadyDecided(Order $order): string
    {
        return sprintf('لا يمكن تنفيذ هذا الإجراء، الطلب رقم %s لم يعد بانتظار ردك.', $order->order_number);
    }

    private function reply(WhatsAppMessage $offer, string $body): void
    {
        SendWhatsAppReply::dispatch($offer->wa_id, $body, $offer->order_id, $offer->print_provider_id);
    }

    private function reasonKey(string $waId): string
    {
        return 'whatsapp:reject-reason:'.$waId;
    }
}
