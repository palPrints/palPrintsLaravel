<?php

use App\Jobs\SendOrderOfferToShop;
use App\Jobs\SendOrderToTelegram;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\TelegramMessage;
use App\Models\User;
use App\Models\Variant;
use App\Services\Telegram\TelegramUpdateHandler;
use App\Services\WhatsApp\ShopChatLink;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'telegram.enabled' => true,
        'telegram.bot_token' => 'TESTTOKEN',
        'telegram.bot_username' => 'palprints_test_bot',
        'telegram.webhook_secret' => 'tg-secret',
        'whatsapp.enabled' => false,
    ]);

    Storage::fake('local');
    $this->seed(RoleAndPermissionSeeder::class);

    $category = Category::create(['name' => 'ورق', 'slug' => 'paper']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);
    $variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'PP-A4', 'is_active' => true]);

    $this->owner = User::factory()->create(['is_active' => true]);
    $this->owner->assignRole('print_provider');
    $this->provider = $this->owner->printProvider()->create([
        'company_name' => 'مطبعة النور', 'approval_status' => 'approved', 'is_active' => true,
        'telegram_chat_id' => '555', 'telegram_username' => 'alnoor', 'telegram_linked_at' => now(),
    ]);
    $this->branch = $this->provider->primaryBranch();
    $offering = $this->branch->branchProductOfferings()->create([
        'product_id' => $this->product->id, 'base_price' => 20, 'currency' => 'ILS',
        'production_time_min' => 1, 'production_time_max' => 1, 'daily_capacity' => 5, 'is_active' => true,
    ]);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');

    $this->order = Order::create([
        'user_id' => $this->customer->id, 'order_number' => 'PLP-TG-1', 'status' => 'processing',
        'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 50, 'total_amount' => 50,
    ]);
    $this->item = OrderItem::create([
        'order_id' => $this->order->id, 'product_id' => $this->product->id, 'variant_id' => $variant->id,
        'item_type' => 'customer_upload', 'print_provider_branch_id' => $this->branch->id, 'branch_product_offering_id' => $offering->id,
        'quantity' => 2, 'unit_price' => 25, 'total_price' => 50, 'provider_cost' => 30,
    ]);

    Storage::disk('local')->put('customer-print-files/tg/a.pdf', '%PDF-1.4 test');
    $this->file = PrintFile::create([
        'user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $this->item->id,
        'original_name' => 'عقد.pdf', 'stored_path' => 'customer-print-files/tg/a.pdf', 'disk' => 'local',
        'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 13, 'status' => 'attached_to_order',
    ]);

    // Telegram answers every call: a document gets message 101, a text message 102.
    $this->fakeTelegram = fn () => Http::fake([
        'api.telegram.org/botTESTTOKEN/sendDocument' => Http::response(['ok' => true, 'result' => ['message_id' => 101]]),
        'api.telegram.org/botTESTTOKEN/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 102]]),
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
    ]);

    $this->sendOrder = function (): TelegramMessage {
        ($this->fakeTelegram)();
        SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

        return TelegramMessage::where('purpose', 'offer')->firstOrFail();
    };

    $this->update = fn (array $update) => $this->postJson(route('webhooks.telegram'), $update + ['update_id' => random_int(1, 1_000_000_000)], ['X-Telegram-Bot-Api-Secret-Token' => 'tg-secret']);

    $this->tap = fn (string $data, string $chat = '555', int $messageId = 102) => ($this->update)([
        'callback_query' => ['id' => 'cb-'.random_int(1, 99999), 'from' => ['id' => (int) $chat], 'data' => $data, 'message' => ['message_id' => $messageId, 'chat' => ['id' => (int) $chat, 'type' => 'private']]],
    ]);

    $this->text = fn (string $text, string $chat = '555') => ($this->update)([
        'message' => ['message_id' => random_int(200, 9999), 'from' => ['id' => (int) $chat, 'username' => 'alnoor'], 'chat' => ['id' => (int) $chat, 'type' => 'private'], 'text' => $text],
    ]);
});

test('the order goes out with the file attached, then the details with accept / reject buttons', function () {
    $offer = ($this->sendOrder)();

    Http::assertSentInOrder([
        fn (HttpRequest $request) => str_ends_with($request->url(), '/sendDocument') && $request->isMultipart()
            && collect($request->data())->firstWhere('name', 'document')['filename'] === 'عقد.pdf'
            && collect($request->data())->firstWhere('name', 'chat_id')['contents'] === '555',
        fn (HttpRequest $request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === '555'
            && str_contains($request['text'], 'PLP-TG-1')
            && str_contains($request['text'], 'طباعة الأوراق — الكمية: 2')
            && str_contains($request['text'], '30.00 ₪')
            && $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === 'acc:'.$offer->action_token
            && $request['reply_markup']['inline_keyboard'][0][1]['callback_data'] === 'rej:'.$offer->action_token,
    ]);

    expect($offer->telegram_message_id)->toBe(102)
        ->and(TelegramMessage::where('purpose', 'file')->value('telegram_message_id'))->toBe(101);

    // A retried job sends nothing twice.
    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);
    Http::assertSentCount(2);
});

test('a file too big for a bot is sent as a signed download link', function () {
    config(['telegram.max_upload_bytes' => 5]);

    ($this->sendOrder)();

    Http::assertNotSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/sendDocument'));
    Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/sendMessage') && str_contains($request['text'], '/shared/print-files/'.$this->file->id.'?expires='));
});

test('nothing is sent when Telegram is off or the shop did not connect it', function () {
    Http::fake();

    config(['telegram.enabled' => false]);
    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    config(['telegram.enabled' => true]);
    $this->provider->update(['telegram_chat_id' => null]);
    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    Http::assertNothingSent();
});

test('a shop that blocked the bot is recorded as failed without retrying', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was blocked by the user'], 403)]);

    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    expect(TelegramMessage::where('purpose', 'file')->value('status'))->toBe('failed')
        ->and(TelegramMessage::where('purpose', 'file')->value('error'))->toBe('Forbidden: bot was blocked by the user');
    Http::assertSentCount(1);
});

test('the webhook refuses calls without the secret token', function () {
    $this->postJson(route('webhooks.telegram'), ['update_id' => 1])->assertForbidden();
    $this->postJson(route('webhooks.telegram'), ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertForbidden();
    $this->postJson(route('webhooks.telegram'), ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => 'tg-secret'])->assertOk();
});

test('the shop connects Telegram from its profile with a one-time link', function () {
    ($this->fakeTelegram)();
    $this->provider->update(['telegram_chat_id' => null, 'telegram_username' => null]);

    $this->actingAs($this->owner)->get(route('print-provider.profile'))->assertOk()->assertSee('ربط تيليجرام');

    $redirect = $this->actingAs($this->owner)->post(route('print-provider.telegram.link'))->headers->get('Location');
    expect($redirect)->toStartWith('https://t.me/palprints_test_bot?start=');
    $token = explode('?start=', $redirect)[1];

    ($this->text)('/start '.$token, '777')->assertOk();

    $provider = $this->provider->fresh();
    expect($provider->telegram_chat_id)->toBe('777')
        ->and($provider->telegram_username)->toBe('alnoor')
        ->and($provider->telegram_linked_at)->not->toBeNull();
    Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/sendMessage') && $request['chat_id'] === '777' && str_contains($request['text'], 'تم ربط'));

    // The link works once: someone else using it later connects nothing.
    ($this->text)('/start '.$token, '888');
    expect($this->provider->fresh()->telegram_chat_id)->toBe('777');
});

test('the shop can disconnect Telegram', function () {
    $this->actingAs($this->owner)->delete(route('print-provider.telegram.unlink'))->assertRedirect(route('print-provider.profile'));

    expect($this->provider->fresh()->telegram_chat_id)->toBeNull();
});

test('tapping accept confirms the order once and takes the buttons off', function () {
    $offer = ($this->sendOrder)();

    $update = ['update_id' => 42, 'callback_query' => ['id' => 'cb-1', 'from' => ['id' => 555], 'data' => 'acc:'.$offer->action_token, 'message' => ['message_id' => 102, 'chat' => ['id' => 555, 'type' => 'private']]]];
    $this->postJson(route('webhooks.telegram'), $update, ['X-Telegram-Bot-Api-Secret-Token' => 'tg-secret'])->assertOk();
    $this->postJson(route('webhooks.telegram'), $update, ['X-Telegram-Bot-Api-Secret-Token' => 'tg-secret'])->assertOk();

    expect($this->order->fresh()->status)->toBe('confirmed')
        ->and(OrderStatusHistory::where('order_id', $this->order->id)->count())->toBe(1)
        ->and(OrderStatusHistory::where('order_id', $this->order->id)->first()->metadata)->toBe(['via' => 'telegram'])
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.confirmed')->count())->toBe(1);

    Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/answerCallbackQuery') && $request['callback_query_id'] === 'cb-1');
    Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/editMessageReplyMarkup') && $request['message_id'] === 102 && $request['reply_markup']['inline_keyboard'] === []);
});

test('a tap from another chat or on another message changes nothing', function () {
    $offer = ($this->sendOrder)();

    ($this->tap)('acc:'.$offer->action_token, '999')->assertOk();
    ($this->tap)('acc:'.$offer->action_token, '555', 77)->assertOk();
    ($this->tap)('acc:not-a-token')->assertOk();

    expect($this->order->fresh()->status)->toBe('processing');
});

test('rejecting asks for the reason, and the next message rejects the order', function () {
    $offer = ($this->sendOrder)();

    ($this->tap)('rej:'.$offer->action_token)->assertOk();
    expect($this->order->fresh()->status)->toBe('processing');
    Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/sendMessage') && str_contains($request['text'], 'اكتبي سبب الرفض') && $request['reply_markup'] === ['force_reply' => true]);

    ($this->text)('الورق المطلوب غير متوفر')->assertOk();

    $history = OrderStatusHistory::where('order_id', $this->order->id)->first();
    expect($this->order->fresh()->status)->toBe('rejected')
        ->and($history->note)->toBe('الورق المطلوب غير متوفر')
        ->and($history->metadata['via'])->toBe('telegram')
        ->and($history->metadata['rejected_branch_ids'])->toBe([$this->branch->id])
        ->and(Notification::where('user_id', $this->admin->id)->where('type', 'order.rejected_by_provider')->exists())->toBeTrue();
});

test('the shop can cancel a rejection before giving the reason', function () {
    $offer = ($this->sendOrder)();

    ($this->tap)('rej:'.$offer->action_token);
    ($this->text)('إلغاء');
    ($this->text)('رسالة عادية بعد الإلغاء');

    expect($this->order->fresh()->status)->toBe('processing');
});

test('approving the payment sends the order to the shop on Telegram and tells the admin', function () {
    Queue::fake();
    $this->order->update(['status' => 'awaiting_payment_review', 'payment_status' => 'pending_review']);
    $payment = Payment::create([
        'order_id' => $this->order->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-TG-1',
        'status' => 'pending_review', 'method' => 'bank', 'amount' => 50, 'gateway_payload' => ['mode' => 'manual_receipt_review'],
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.payment-notices.approve', $payment), ['branch_id' => $this->branch->id]);

    Queue::assertPushed(SendOrderToTelegram::class, fn ($job) => $job->orderId === $this->order->id && $job->printProviderId === $this->provider->id);
    Queue::assertPushed(SendOrderOfferToShop::class);
    expect($response->getSession()->get('notice_whatsapp'))->toMatchArray(['shop' => 'مطبعة النور', 'telegram' => true, 'url' => null]);

    $this->actingAs($this->admin)->get(route('admin.payment-notices'))->assertSee('على تيليجرام مع ملفات الطباعة', false);
});

test('the wa.me message is offered only to a shop that chose WhatsApp', function () {
    $this->provider->update(['whatsapp_number' => '970599123456', 'whatsapp_notifications_enabled' => false]);
    expect(ShopChatLink::forOrder($this->order)['url'])->toBeNull();

    $this->provider->update(['whatsapp_notifications_enabled' => true]);
    expect(ShopChatLink::forOrder($this->order->fresh())['url'])->toStartWith('https://wa.me/970599123456');
});

test('a bot token never reaches an error message', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28 for https://api.telegram.org/botTESTTOKEN/sendMessage'));

    try {
        app(\App\Services\Telegram\TelegramClient::class)->sendMessage('555', 'hi');
        $this->fail('expected a connection error');
    } catch (\Illuminate\Http\Client\ConnectionException $exception) {
        expect($exception->getMessage())->not->toContain('TESTTOKEN')->toContain('bot***');
    }
});

test('a link token can be issued for the shop', function () {
    $token = TelegramUpdateHandler::issueLinkToken($this->provider);

    expect($token)->toHaveLength(32)->toMatch('/^[A-Za-z0-9]+$/');
});
