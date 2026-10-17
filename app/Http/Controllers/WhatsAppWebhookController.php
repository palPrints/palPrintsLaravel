<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Meta's webhook for the WhatsApp Cloud API. Public and outside CSRF; every POST must carry Meta's signature. */
class WhatsAppWebhookController extends Controller
{
    /** The handshake Meta does when the webhook URL is saved in the App Dashboard. */
    public function verify(Request $request): Response
    {
        $expected = (string) config('whatsapp.verify_token');

        // PHP turns the dots of hub.mode / hub.verify_token / hub.challenge into underscores.
        if ($expected === '' || $request->query('hub_mode') !== 'subscribe' || ! hash_equals($expected, (string) $request->query('hub_verify_token'))) {
            return response('Forbidden', 403);
        }

        return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request, WebhookHandler $handler): Response
    {
        $secret = (string) config('whatsapp.app_secret');
        $raw = $request->getContent();
        $expected = 'sha256='.hash_hmac('sha256', $raw, $secret);

        // Checked on the raw body before decoding anything; without a configured secret nothing is accepted.
        if ($secret === '' || ! hash_equals($expected, (string) $request->header('X-Hub-Signature-256'))) {
            return response('Invalid signature', 401);
        }

        $payload = json_decode($raw, true);
        if (is_array($payload)) {
            $handler->handle($payload);
        }

        return response('OK', 200);
    }
}
