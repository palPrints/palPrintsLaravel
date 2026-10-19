<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintFile;
use App\Models\PrintProvider;
use App\Models\TelegramMessage;
use App\Services\Orders\OrderLineDocuments;
use App\Services\Orders\ShopOrderTransitions;
use App\Services\Telegram\TelegramClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends a shop that connected the bot its new order on Telegram: each print file as an attached document, then the
 * order details with Accept / Reject buttons as the last message, so the buttons sit at the bottom of the chat.
 * Every message is recorded before it is sent, so a retried job only sends what is still missing.
 */
class SendOrderToTelegram implements ShouldQueue
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

    public function handle(TelegramClient $client, OrderLineDocuments $documents): void
    {
        if (! config('telegram.enabled')) {
            return;
        }

        $order = Order::query()->with(['items.product', 'items.printFiles'])->find($this->orderId);
        $provider = PrintProvider::query()->find($this->printProviderId);
        $chatId = $provider?->telegramRecipient();

        if (! $order || ! $chatId || ! in_array($order->status, ShopOrderTransitions::NEW_STATUSES, true)) {
            return;
        }

        $branchIds = $provider->branches()->pluck('id');
        $items = $order->items->filter(fn (OrderItem $item) => $branchIds->contains($item->print_provider_branch_id));
        if ($items->isEmpty()) {
            return;
        }

        try {
            foreach ($items as $item) {
                $this->sendLine($client, $documents, $order, $provider, $chatId, $item);
            }

            $this->sendOffer($client, $order, $provider, $chatId, $items);
        } catch (RequestException $exception) {
            // 4xx: the chat is wrong or the shop blocked the bot. Retrying would send the same failing request.
            if ($exception->response->clientError() && $exception->response->status() !== 429) {
                $this->markFailed($exception);
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception);
    }

    /**
     * One order line: its details PDF, then its files, each under the name it downloads as — a design's print-ready
     * picture per area ("أمامي.png"), or a paper job's own PDFs. If the details PDF cannot be made, the files still go.
     */
    private function sendLine(TelegramClient $client, OrderLineDocuments $documents, Order $order, PrintProvider $provider, string $chatId, OrderItem $item): void
    {
        try {
            $this->sendDetails($client, $documents, $order, $provider, $chatId, $item);
        } catch (RequestException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // The PDF could not be made (the PDF writer missing on the server…): the files still carry the order.
            report($exception);
        }

        foreach ($documents->filesToSend($item) as $file) {
            $this->sendFile($client, $order, $provider, $chatId, $file);
        }
    }

    /** The line's details PDF, sent once. */
    private function sendDetails(TelegramClient $client, OrderLineDocuments $documents, Order $order, PrintProvider $provider, string $chatId, OrderItem $item): void
    {
        $row = TelegramMessage::query()->firstOrCreate(
            ['order_id' => $order->id, 'print_provider_id' => $provider->id, 'purpose' => 'details:'.$item->id],
            ['chat_id' => $chatId, 'status' => 'queued'],
        );
        if ($row->telegram_message_id) {
            return;
        }

        $caption = '📋 تفاصيل الطلب — '.TelegramClient::e($item->product?->name ?? 'منتج').' — طلب <code>'.TelegramClient::e($order->order_number).'</code>';
        $sent = $client->sendDocument($chatId, $documents->detailsPdf($order, $item), $documents->fileName($order, $item), $caption);
        $row->update(['telegram_message_id' => $sent['message_id'] ?? null, 'status' => 'sent', 'error' => null]);
    }

    private function sendFile(TelegramClient $client, Order $order, PrintProvider $provider, string $chatId, PrintFile $file): void
    {
        $row = TelegramMessage::query()->firstOrCreate(
            ['order_id' => $order->id, 'print_provider_id' => $provider->id, 'purpose' => 'file', 'print_file_id' => $file->id],
            ['chat_id' => $chatId, 'status' => 'queued'],
        );
        if ($row->telegram_message_id) {
            return;
        }

        $disk = Storage::disk($file->disk ?: 'local');
        $caption = '🖼️ '.TelegramClient::e($file->downloadName()).' — طلب <code>'.TelegramClient::e($order->order_number).'</code>';

        if (! $disk->exists($file->stored_path)) {
            $row->update(['status' => 'failed', 'error' => 'الملف غير موجود في التخزين.']);

            return;
        }

        // Too big to upload from a bot: a signed download link instead.
        if ($disk->size($file->stored_path) > (int) config('telegram.max_upload_bytes')) {
            $link = URL::temporarySignedRoute('print-files.shared', now()->addDays(7), $file);
            $sent = $client->sendMessage($chatId, $caption."\nالملف كبير، نزّليه من الرابط (صالح 7 أيام):\n".TelegramClient::e($link));
        } else {
            $sent = $client->sendDocument($chatId, $disk->get($file->stored_path), $file->downloadName(), $caption);
        }

        $row->update(['telegram_message_id' => $sent['message_id'] ?? null, 'status' => 'sent', 'error' => null]);
    }

    /** @param  \Illuminate\Support\Collection<int, OrderItem>  $items */
    private function sendOffer(TelegramClient $client, Order $order, PrintProvider $provider, string $chatId, $items): void
    {
        $offer = TelegramMessage::query()->firstOrCreate(
            ['order_id' => $order->id, 'print_provider_id' => $provider->id, 'purpose' => 'offer'],
            ['chat_id' => $chatId, 'action_token' => Str::random(40), 'status' => 'queued'],
        );
        if ($offer->telegram_message_id) {
            return;
        }

        // Each product with the few options the shop needs at a glance (size, colour, print areas); the rest is in the PDFs.
        $documents = app(OrderLineDocuments::class);
        $products = $items->map(function (OrderItem $item) use ($documents, $order) {
            $specs = $documents->keyDetails($order, $item);

            return '• '.TelegramClient::e($item->product?->name ?? 'منتج').' — الكمية: '.(int) $item->quantity
                .($specs ? "\n   ".TelegramClient::e($specs) : '');
        })->implode("\n");

        $text = implode("\n", array_filter([
            '📦 <b>طلب جديد من PALPRINTS</b>',
            'رقم الطلب: <code>'.TelegramClient::e($order->order_number).'</code>',
            '',
            '<b>المنتجات:</b>',
            $products,
            '',
            'مستحقاتك: <b>'.number_format((float) $items->sum('provider_cost'), 2).' ₪</b>',
            'ملف تفاصيل الطلب وملفات الطباعة مرفقة فوق هذه الرسالة.',
            '',
            'صفحة الطلبات: '.TelegramClient::e(route('print-provider.requests')),
        ], fn ($line) => $line !== null));

        $sent = $client->sendMessage($chatId, $text, [[
            ['text' => '✅ قبول الطلب', 'callback_data' => 'acc:'.$offer->action_token],
            ['text' => '❌ رفض الطلب', 'callback_data' => 'rej:'.$offer->action_token],
        ]]);

        $offer->update(['telegram_message_id' => $sent['message_id'] ?? null, 'status' => 'sent', 'error' => null]);
    }

    private function markFailed(?Throwable $exception): void
    {
        $error = $exception instanceof RequestException
            ? (string) ($exception->response->json('description') ?? $exception->response->status())
            : (string) $exception?->getMessage();

        TelegramMessage::query()
            ->where(['order_id' => $this->orderId, 'print_provider_id' => $this->printProviderId])
            ->whereNull('telegram_message_id')
            ->where('status', '!=', 'failed')
            ->update(['status' => 'failed', 'error' => Str::limit($error, 500), 'updated_at' => now()]);
    }
}
