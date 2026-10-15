<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/** A short text answer to something the shop did on WhatsApp (its tap opened the 24-hour window, so free text is allowed). */
class SendWhatsAppReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $to, public string $body, public ?int $orderId = null, public ?int $printProviderId = null)
    {
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(WhatsAppClient $client): void
    {
        if (! config('whatsapp.enabled')) {
            return;
        }

        $sent = $client->sendText($this->to, $this->body);

        WhatsAppMessage::create([
            'direction' => 'out',
            'order_id' => $this->orderId,
            'print_provider_id' => $this->printProviderId,
            'purpose' => 'reply',
            'wa_id' => $this->to,
            'wa_message_id' => $sent['id'],
            'type' => 'text',
            'payload' => $sent['request'],
            'status' => 'accepted',
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        WhatsAppMessage::create([
            'direction' => 'out',
            'order_id' => $this->orderId,
            'print_provider_id' => $this->printProviderId,
            'purpose' => 'reply',
            'wa_id' => $this->to,
            'type' => 'text',
            'payload' => ['text' => ['body' => $this->body]],
            'status' => 'failed',
            'failed_at' => now(),
            'error' => Str::limit((string) $exception?->getMessage(), 500),
        ]);
    }
}
