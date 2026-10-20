<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintProvider;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SendStickerFilesToWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $orderId, public int $printProviderId)
    {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(WhatsAppClient $client): void
    {
        if (! config('whatsapp.enabled')) {
            return;
        }
        $order = Order::with('items.printFiles')->find($this->orderId);
        $provider = PrintProvider::find($this->printProviderId);
        $to = $provider?->whatsappRecipient();
        if (! $to || ! $order || $order->payment_status !== 'paid' || $order->status !== 'processing') {
            return;
        }
        $branches = $provider->branches()->pluck('id');
        foreach ($order->items as $item) {
            if ($item->item_type !== OrderItem::TYPE_STICKER_BUNDLE || ! $branches->contains($item->print_provider_branch_id)) {
                continue;
            }
            foreach ($item->printFiles as $file) {
                if ($file->status !== 'attached_to_order' || ! str_ends_with($file->stored_path, '-final.pdf')) {
                    continue;
                }
                $row = WhatsAppMessage::firstOrCreate([
                    'order_id' => $order->id, 'print_provider_id' => $provider->id,
                    'direction' => 'out', 'purpose' => 'sticker-file-'.$file->id,
                ], ['wa_id' => $to, 'type' => 'template', 'status' => 'queued']);
                if ($row->wa_message_id) {
                    continue;
                }
                try {
                    $disk = Storage::disk($file->disk ?: 'local');
                    if (! $disk->exists($file->stored_path)) {
                        throw new \RuntimeException('Missing final sticker PDF.');
                    }
                    $sent = $client->sendPrintFile($to, $disk->get($file->stored_path), $file->original_name, $order->order_number);
                    if (! $sent['id']) {
                        throw new \RuntimeException('WhatsApp did not acknowledge the document.');
                    }
                    $row->update(['wa_message_id' => $sent['id'], 'status' => 'accepted', 'payload' => $sent['request'], 'error' => null]);
                } catch (\Throwable $error) {
                    $row->update(['status' => 'failed', 'error' => 'Sticker document delivery failed. Check channel configuration and retry.']);
                    throw $error;
                }
            }
        }
    }
}
