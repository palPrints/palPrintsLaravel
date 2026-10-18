<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * The Telegram Bot API calls PALPRINTS makes. Each returns Telegram's "result". Errors throw RequestException:
 * a 4xx means the request itself is wrong (bad chat, the shop blocked the bot) and retrying will not help; a 429 or 5xx
 * is worth retrying.
 */
class TelegramClient
{
    /**
     * @param  array<int, array<int, array<string, string>>>|null  $buttons  inline keyboard rows
     * @return array<string, mixed>
     */
    public function sendMessage(string|int $chatId, string $html, ?array $buttons = null, ?int $replyTo = null, bool $forceReply = false): array
    {
        $markup = $buttons !== null ? ['inline_keyboard' => $buttons] : ($forceReply ? ['force_reply' => true] : null);

        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
            'reply_markup' => $markup,
            'reply_parameters' => $replyTo ? ['message_id' => $replyTo, 'allow_sending_without_reply' => true] : null,
        ], fn ($value) => $value !== null));
    }

    /**
     * Uploads a file from our storage (no public URL needed, so it also works on a local machine).
     *
     * @return array<string, mixed>
     */
    public function sendDocument(string|int $chatId, string $contents, string $fileName, ?string $captionHtml = null): array
    {
        return $this->guarded(fn () => (array) $this->http(120)
            ->attach('document', $contents, $fileName)
            ->post($this->url('sendDocument'), array_filter([
                'chat_id' => (string) $chatId,
                'caption' => $captionHtml,
                'parse_mode' => $captionHtml ? 'HTML' : null,
            ]))
            ->throw()
            ->json('result'));
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        $this->call('answerCallbackQuery', array_filter(['callback_query_id' => $callbackQueryId, 'text' => $text]));
    }

    /** Takes the Accept / Reject buttons off a message once the shop has decided. */
    public function removeButtons(string|int $chatId, int $messageId): void
    {
        $this->call('editMessageReplyMarkup', ['chat_id' => $chatId, 'message_id' => $messageId, 'reply_markup' => ['inline_keyboard' => []]]);
    }

    /** @return array<int, array<string, mixed>> */
    public function getUpdates(int $offset, int $timeout = 25): array
    {
        return $this->guarded(fn () => (array) $this->http($timeout + 10)
            ->post($this->url('getUpdates'), ['offset' => $offset, 'timeout' => $timeout, 'allowed_updates' => ['message', 'callback_query']])
            ->throw()
            ->json('result'));
    }

    public function setWebhook(string $url, string $secret): void
    {
        $this->call('setWebhook', ['url' => $url, 'secret_token' => $secret, 'allowed_updates' => ['message', 'callback_query']]);
    }

    public function deleteWebhook(): void
    {
        $this->call('deleteWebhook', ['drop_pending_updates' => false]);
    }

    /** @return array<string, mixed> */
    public function getMe(): array
    {
        return $this->call('getMe', []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    private function call(string $method, array $payload): array
    {
        return $this->guarded(fn () => (array) $this->http(20)->post($this->url($method), $payload)->throw()->json('result'));
    }

    /**
     * A connection error's message holds the request URL, and the bot token is part of that URL: it is masked so the
     * token never reaches the logs.
     */
    private function guarded(callable $request): mixed
    {
        try {
            return $request();
        } catch (ConnectionException $exception) {
            $token = (string) config('telegram.bot_token');

            throw new ConnectionException($token === '' ? $exception->getMessage() : str_replace($token, '***', $exception->getMessage()), 0);
        }
    }

    private function http(int $timeout): PendingRequest
    {
        return Http::acceptJson()->timeout($timeout);
    }

    private function url(string $method): string
    {
        return rtrim((string) config('telegram.api_url'), '/').'/bot'.config('telegram.bot_token').'/'.$method;
    }

    /** Escapes text for Telegram's HTML parse mode. */
    public static function e(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
