<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintProvider;
use App\Models\WhatsAppMessage;
use App\Services\Orders\ShopOrderTransitions;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends the shop the approved "new order" template with Accept / Reject quick-reply buttons. The in-app notification
 * the shop already gets stays the fallback: when WhatsApp is off, the shop did not opt in, or Meta refuses the message,
 * the order simply waits on the dashboard as before.
 */
class SendOrderOfferToShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $orderId, public int $printProviderId)
    {
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(WhatsAppClient $client): void
    {
        if (! config('whatsapp.enabled')) {
            return;
        }

        $order = Order::query()->with('items.product')->find($this->orderId);
        $provider = PrintProvider::query()->find($this->printProviderId);
        $to = $provider?->whatsappRecipient();

        if (! $order || ! $to || ! in_array($order->status, ShopOrderTransitions::NEW_STATUSES, true)) {
            return;
        }

        $branchIds = $provider->branches()->pluck('id');
        $items = $order->items->filter(fn (OrderItem $item) => $branchIds->contains($item->print_provider_branch_id));
        if ($items->isEmpty()) {
            return;
        }

        // One offer per order and shop: a retried job reuses the row it created instead of messaging the shop twice.
        $message = WhatsAppMessage::query()->firstOrCreate(
            ['order_id' => $order->id, 'print_provider_id' => $provider->id, 'direction' => 'out', 'purpose' => 'offer'],
            ['wa_id' => $to, 'type' => 'template', 'action_token' => Str::random(40), 'status' => 'queued'],
        );

        if ($message->wa_message_id !== null) {
            return;
        }

        $template = config('whatsapp.new_order_template');

        try {
            $sent = $client->sendTemplate($to, $template['name'], $template['language'], [
                $order->order_number,
                $items->map(fn (OrderItem $item) => $item->product?->name ?? 'منتج')->unique()->implode('، '),
                (string) (int) $items->sum('quantity'),
                number_format((float) $items->sum('provider_cost'), 2),
            ], [
                'accept:'.$message->action_token,
                'reject:'.$message->action_token,
            ]);
        } catch (RequestException $exception) {
            // 4xx: the number, the template or the token is wrong. Retrying would send the same broken request.
            if ($exception->response->clientError() && $exception->response->status() !== 429) {
                $this->markFailed($message, $exception);
                $this->fail($exception);

                return;
            }

            throw $exception;
        }

        $message->update(['wa_message_id' => $sent['id'], 'payload' => $sent['request'], 'status' => 'accepted', 'error' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        $message = WhatsAppMessage::query()
            ->where(['order_id' => $this->orderId, 'print_provider_id' => $this->printProviderId, 'direction' => 'out', 'purpose' => 'offer'])
            ->whereNull('wa_message_id')
            ->first();

        if ($message && $message->status !== 'failed') {
            $this->markFailed($message, $exception);
        }
    }

    private function markFailed(WhatsAppMessage $message, ?Throwable $exception): void
    {
        $error = $exception instanceof RequestException
            ? (string) ($exception->response->json('error.message') ?? $exception->response->status())
            : (string) $exception?->getMessage();

        $message->update(['status' => 'failed', 'failed_at' => now(), 'error' => Str::limit($error, 500)]);
    }
}
