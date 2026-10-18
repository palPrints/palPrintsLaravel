<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The bot's webhook. Public and outside CSRF; Telegram proves itself with the secret token set by telegram:webhook. */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramUpdateHandler $handler): Response
    {
        $secret = (string) config('telegram.webhook_secret');

        // Without a configured secret nothing is accepted.
        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response('Forbidden', 403);
        }

        $update = $request->json()->all();
        if (is_array($update) && $update !== []) {
            $handler->handle($update);
        }

        return response('OK', 200);
    }
}
