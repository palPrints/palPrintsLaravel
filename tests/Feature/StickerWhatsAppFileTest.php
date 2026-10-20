<?php

use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Support\Facades\Http;

test('print document is uploaded then sent using the configured document template', function () {
    config(['whatsapp.graph_url' => 'https://graph.facebook.com', 'whatsapp.graph_version' => 'v23.0',
        'whatsapp.phone_number_id' => 'test-phone', 'whatsapp.access_token' => 'test-token',
        'whatsapp.print_file_template' => 'print_file_ready', 'whatsapp.print_file_template_language' => 'ar']);
    Http::preventStrayRequests();
    Http::fake([
        '*/media' => Http::response(['id' => 'media-123']),
        '*/messages' => Http::response(['messages' => [['id' => 'message-123']]]),
    ]);
    $result = app(WhatsAppClient::class)->sendPrintFile('970590000000', '%PDF-1.4 test', 'stickers.pdf', 'ORDER-1');
    expect($result['id'])->toBe('message-123');
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/messages')
        && $request['template']['components'][0]['parameters'][0]['document']['id'] === 'media-123');
    Http::assertSentCount(2);
});
