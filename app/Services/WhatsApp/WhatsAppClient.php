<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * The few Cloud API calls PALPRINTS makes. Each one returns the wamid Meta assigns, which later shows up in the status
 * webhooks and as context.id when the shop taps a button. Errors throw RequestException (4xx = our request is wrong,
 * retrying will not help; 5xx / 429 = try again later).
 */
class WhatsAppClient
{
    /**
     * @param  array<int, string>  $bodyParameters  {{1}}, {{2}}, … of the template body
     * @param  array<int, string>  $quickReplyPayloads  payload of each quick-reply button, by button index
     * @return array{id: string, request: array<string, mixed>}
     */
    public function sendTemplate(string $to, string $name, string $language, array $bodyParameters, array $quickReplyPayloads = []): array
    {
        $components = [[
            'type' => 'body',
            'parameters' => array_map(fn (string $text) => ['type' => 'text', 'text' => self::templateText($text)], array_values($bodyParameters)),
        ]];

        foreach (array_values($quickReplyPayloads) as $index => $payload) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'quick_reply',
                'index' => (string) $index,
                'parameters' => [['type' => 'payload', 'payload' => $payload]],
            ];
        }

        return $this->send($to, [
            'type' => 'template',
            'template' => ['name' => $name, 'language' => ['code' => $language], 'components' => $components],
        ]);
    }

    /**
     * Free-form text. Only delivered while the shop's 24-hour window is open (it messaged us or tapped a button).
     *
     * @return array{id: string, request: array<string, mixed>}
     */
    public function sendText(string $to, string $body): array
    {
        return $this->send($to, ['type' => 'text', 'text' => ['preview_url' => false, 'body' => $body]]);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{id: string, request: array<string, mixed>}
     *
     * @throws RequestException
     */
    private function send(string $to, array $message): array
    {
        $request = ['messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $to] + $message;

        $url = sprintf('%s/%s/%s/messages', rtrim(config('whatsapp.graph_url'), '/'), config('whatsapp.graph_version'), config('whatsapp.phone_number_id'));

        $response = Http::withToken((string) config('whatsapp.access_token'))
            ->acceptJson()
            ->timeout(15)
            ->post($url, $request)
            ->throw();

        return ['id' => (string) $response->json('messages.0.id'), 'request' => $request];
    }

    /** Meta rejects template parameters with new lines, tabs or more than four spaces in a row. */
    public static function templateText(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return mb_strimwidth($text === '' ? '-' : $text, 0, 200, '…');
    }
}
