<?php

use App\Jobs\SendOrderOfferToShop;
use App\Jobs\SendWhatsAppReply;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\ShopChatLink;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'whatsapp.enabled' => true,
        'whatsapp.phone_number_id' => '111222333',
        'whatsapp.access_token' => 'test-token',
        'whatsapp.app_secret' => 'test-app-secret',
        'whatsapp.verify_token' => 'test-verify-token',
    ]);

    $this->seed(RoleAndPermissionSeeder::class);

    $category = Category::create(['name' => 'ورق', 'slug' => 'paper']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);
    $this->variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'PP-A4', 'is_active' => true]);

    $this->owner = User::factory()->create(['is_active' => true]);
    $this->owner->assignRole('print_provider');
    $this->provider = $this->owner->printProvider()->create([
        'company_name' => 'مطبعة النور', 'approval_status' => 'approved', 'is_active' => true,
        'whatsapp_number' => '970599123456', 'whatsapp_notifications_enabled' => true, 'whatsapp_opted_in_at' => now(),
    ]);
    $this->branch = $this->provider->primaryBranch();
    $this->offering = $this->branch->branchProductOfferings()->create([
        'product_id' => $this->product->id, 'base_price' => 20, 'currency' => 'ILS',
        'production_time_min' => 1, 'production_time_max' => 1, 'daily_capacity' => 5, 'is_active' => true,
    ]);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');

    $this->order = Order::create([
        'user_id' => $this->customer->id, 'order_number' => 'PLP-WA-1', 'status' => 'processing',
        'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 50, 'total_amount' => 50,
    ]);
    OrderItem::create([
        'order_id' => $this->order->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id,
        'item_type' => 'customer_upload', 'print_provider_branch_id' => $this->branch->id,
        'branch_product_offering_id' => $this->offering->id,
        'quantity' => 3, 'unit_price' => 25, 'total_price' => 75, 'provider_cost' => 60,
    ]);

    // Sends the offer through a faked Meta API and returns the stored offer row.
    $this->sendOffer = function (): WhatsAppMessage {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.OFFER1']]])]);
        SendOrderOfferToShop::dispatchSync($this->order->id, $this->provider->id);

        return WhatsAppMessage::where('purpose', 'offer')->firstOrFail();
    };

    // Posts a webhook body signed the way Meta signs it.
    $this->webhook = function (array $payload, ?string $secret = 'test-app-secret') {
        $body = json_encode($payload);

        return $this->call('POST', route('webhooks.whatsapp'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, (string) $secret),
        ], $body);
    };

    $this->inbound = fn (array $message) => ['object' => 'whatsapp_business_account', 'entry' => [[
        'id' => 'WABA', 'changes' => [['field' => 'messages', 'value' => ['messaging_product' => 'whatsapp', 'messages' => [$message + ['from' => '970599123456', 'timestamp' => (string) time()]]]]],
    ]]];
});

test('phone numbers are normalised to international digits', function (?string $typed, ?string $expected) {
    expect(PhoneNumber::normalize($typed))->toBe($expected);
})->with([
    ['0599123456', '970599123456'],
    ['0599 123 456', '970599123456'],
    ['+970-599-123456', '970599123456'],
    ['00972 59 912 3456', '972599123456'],
    ['٠٥٩٩١٢٣٤٥٦', '970599123456'],
    ['+972594045626', '972594045626'],
    // Copied from WhatsApp / contacts: invisible direction marks and a no-break space around the number.
    ["\u{202A}+972 59-404-5626\u{202C}", '972594045626'],
    ["\u{2066}+972594045626\u{2069}", '972594045626'],
    ["\u{200F}0599\u{00A0}123\u{00A0}456", '970599123456'],
    ['12345', null],
    ['not a number', null],
    ['', null],
    [null, null],
]);

test('a palestinian number is the same shop under 970 and 972', function (string $a, string $b, bool $same) {
    expect(PhoneNumber::sameSubscriber($a, $b))->toBe($same);
})->with([
    ['970599123456', '972599123456', true],
    ['972599123456', '0599123456', true],
    ['970599123456', '970599123456', true],
    ['970599123456', '972591111111', false],
    ['970599123456', '962599123456', false],
]);

test('a tap reported from the 972 form of the shop number is accepted', function () {
    $offer = ($this->sendOffer)();
    Queue::fake();

    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN1', 'from' => '972599123456', 'type' => 'button', 'button' => ['payload' => 'accept:'.$offer->action_token]]))->assertOk();

    expect($this->order->fresh()->status)->toBe('confirmed');
});

test('the offer keeps the number whatsapp reports for the shop, so replies reach it', function () {
    Http::fake(['graph.facebook.com/*' => Http::response([
        'contacts' => [['input' => '970599123456', 'wa_id' => '972599123456']],
        'messages' => [['id' => 'wamid.OFFER1']],
    ])]);

    SendOrderOfferToShop::dispatchSync($this->order->id, $this->provider->id);

    expect(WhatsAppMessage::where('purpose', 'offer')->value('wa_id'))->toBe('972599123456');
});

test('meta can verify the webhook only with the right token', function () {
    $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=12345')
        ->assertOk()->assertSeeText('12345');

    $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345')->assertForbidden();
});

test('a webhook without a valid signature is refused and changes nothing', function () {
    $offer = ($this->sendOffer)();

    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN1', 'type' => 'button', 'button' => ['payload' => 'accept:'.$offer->action_token, 'text' => 'قبول الطلب']]), 'wrong-secret')
        ->assertStatus(401);

    expect($this->order->fresh()->status)->toBe('processing');
});

test('approving the payment queues the WhatsApp offer for the chosen shop', function () {
    Queue::fake();
    $this->order->update(['status' => 'awaiting_payment_review', 'payment_status' => 'pending_review']);
    $payment = Payment::create([
        'order_id' => $this->order->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-WA-1',
        'status' => 'pending_review', 'method' => 'bank', 'amount' => 50, 'gateway_payload' => ['mode' => 'manual_receipt_review'],
    ]);

    $this->actingAs($this->admin)->post(route('admin.payment-notices.approve', $payment), ['branch_id' => $this->branch->id])->assertRedirect();

    Queue::assertPushed(SendOrderOfferToShop::class, fn ($job) => $job->orderId === $this->order->id && $job->printProviderId === $this->provider->id);
});

test('after approving, the admin gets a wa.me link with the order written for the shop', function () {
    Queue::fake();
    $this->customer->update(['name' => 'سارة العميلة']);
    $this->order->update(['status' => 'awaiting_payment_review', 'payment_status' => 'pending_review']);
    $payment = Payment::create([
        'order_id' => $this->order->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-WA-2',
        'status' => 'pending_review', 'method' => 'bank', 'amount' => 50, 'gateway_payload' => ['mode' => 'manual_receipt_review'],
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.payment-notices.approve', $payment), ['branch_id' => $this->branch->id]);

    $chat = $response->getSession()->get('notice_whatsapp');
    expect($chat['shop'])->toBe('مطبعة النور')
        ->and($chat['url'])->toStartWith('https://wa.me/970599123456?text=');

    $text = rawurldecode(explode('?text=', $chat['url'])[1]);
    expect($text)->toContain('PLP-WA-1')
        ->toContain("• طباعة الأوراق\u{200F} — الكمية: 3")
        ->toContain('رقم الطلب: PLP-WA-1')
        ->toContain('60.00 ₪')
        ->toContain(route('print-provider.requests'))
        ->not->toContain('سارة العميلة');

    // The reviewed list keeps the button for later.
    $this->actingAs($this->admin)->get(route('admin.payment-notices'))
        ->assertOk()
        ->assertSee('https://wa.me/970599123456', false);
});

test('the wa.me message links each print file, and the signed link downloads it without logging in', function () {
    Storage::fake('local');
    Storage::disk('local')->put('customer-print-files/wa/a.pdf', '%PDF-1.4 test');
    $file = PrintFile::create([
        'user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $this->order->items()->value('id'),
        'original_name' => 'عقد.pdf', 'stored_path' => 'customer-print-files/wa/a.pdf', 'disk' => 'local',
        'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 13, 'status' => 'attached_to_order',
    ]);

    $text = rawurldecode(explode('?text=', ShopChatLink::forOrder($this->order->fresh())['url'])[1]);
    expect($text)->toContain('📄 عقد.pdf');

    preg_match('#https?://\S+/shared/print-files/'.$file->id.'\?\S+#', $text, $match);
    expect($match)->not->toBeEmpty();
    $link = $match[0];

    // Works for a guest, with the original file name.
    $this->get($link)->assertOk()
        ->assertHeader('content-disposition', "attachment; filename=aakd.pdf; filename*=utf-8''".rawurlencode('عقد.pdf'));

    // A changed signature, or the plain URL, is refused.
    $this->get($link.'x')->assertForbidden();
    $this->get(route('print-files.shared', $file))->assertForbidden();

    // After the link expires.
    $this->travel(8)->days();
    $this->get($link)->assertForbidden();
    $this->travelBack();

    // A file the customer deleted is gone even with a valid link.
    $file->update(['status' => PrintFile::STATUS_DELETED]);
    $this->get($link)->assertNotFound();
});

test('without a whatsapp number the admin is told the shop only sees the dashboard', function () {
    $this->provider->update(['whatsapp_number' => null]);

    expect(\App\Services\WhatsApp\ShopChatLink::forOrder($this->order))->toBe(['url' => null, 'shop' => 'مطبعة النور', 'telegram' => false]);
});

test('the offer is the approved template with the order details and accept / reject buttons', function () {
    $offer = ($this->sendOffer)();

    Http::assertSent(function (HttpRequest $request) use ($offer) {
        $components = collect($request['template']['components']);

        return $request->url() === 'https://graph.facebook.com/v23.0/111222333/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '970599123456'
            && $request['template']['name'] === 'new_print_order'
            && collect($components->firstWhere('type', 'body')['parameters'])->pluck('text')->all() === ['PLP-WA-1', 'طباعة الأوراق', '3', '60.00']
            && $components->where('type', 'button')->pluck('parameters.0.payload')->values()->all() === ['accept:'.$offer->action_token, 'reject:'.$offer->action_token];
    });

    expect($offer->wa_message_id)->toBe('wamid.OFFER1')
        ->and($offer->status)->toBe('accepted')
        ->and(strlen($offer->action_token))->toBe(40);

    // Running the job again (a retry) does not message the shop twice.
    SendOrderOfferToShop::dispatchSync($this->order->id, $this->provider->id);
    Http::assertSentCount(1);
});

test('nothing is sent when WhatsApp is off or the shop did not opt in', function () {
    Http::fake();

    config(['whatsapp.enabled' => false]);
    SendOrderOfferToShop::dispatchSync($this->order->id, $this->provider->id);

    config(['whatsapp.enabled' => true]);
    $this->provider->update(['whatsapp_notifications_enabled' => false]);
    SendOrderOfferToShop::dispatchSync($this->order->id, $this->provider->id);

    Http::assertNothingSent();
    expect(WhatsAppMessage::count())->toBe(0);
});

test('a rejected request from meta marks the offer failed without retrying', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template name does not exist', 'code' => 132001]], 400)]);

    SendOrderOfferToShop::dispatchSync($this->order->id, $this->provider->id);

    $offer = WhatsAppMessage::where('purpose', 'offer')->first();
    expect($offer->status)->toBe('failed')->and($offer->error)->toBe('Template name does not exist');
    Http::assertSentCount(1);
});

test('tapping accept on WhatsApp confirms the order once, even when meta repeats the event', function () {
    $offer = ($this->sendOffer)();
    Queue::fake();

    $tap = ($this->inbound)(['id' => 'wamid.IN1', 'type' => 'button', 'context' => ['id' => 'wamid.OFFER1'], 'button' => ['payload' => 'accept:'.$offer->action_token, 'text' => 'قبول الطلب']]);
    ($this->webhook)($tap)->assertOk();
    ($this->webhook)($tap)->assertOk();

    expect($this->order->fresh()->status)->toBe('confirmed')
        ->and($this->order->statusHistory()->count())->toBe(1)
        ->and($this->order->statusHistory()->first()->metadata)->toBe(['via' => 'whatsapp'])
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.confirmed')->count())->toBe(1);

    Queue::assertPushed(SendWhatsAppReply::class, 1);
    Queue::assertPushed(SendWhatsAppReply::class, fn ($job) => str_contains($job->body, 'تم قبول الطلب رقم PLP-WA-1'));
});

test('a tap from another number or with an unknown token does nothing', function () {
    $offer = ($this->sendOffer)();
    Queue::fake();

    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN1', 'from' => '970591111111', 'type' => 'button', 'button' => ['payload' => 'accept:'.$offer->action_token]]))->assertOk();
    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN2', 'type' => 'button', 'button' => ['payload' => 'accept:not-a-real-token']]))->assertOk();

    expect($this->order->fresh()->status)->toBe('processing');
    Queue::assertNothingPushed();
});

test('rejecting on WhatsApp asks for the reason and the next text message rejects the order', function () {
    $offer = ($this->sendOffer)();
    Queue::fake();

    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN1', 'type' => 'button', 'button' => ['payload' => 'reject:'.$offer->action_token]]))->assertOk();
    expect($this->order->fresh()->status)->toBe('processing');
    Queue::assertPushed(SendWhatsAppReply::class, fn ($job) => str_contains($job->body, 'اكتبي سبب الرفض'));

    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN2', 'type' => 'text', 'text' => ['body' => 'الورق المطلوب غير متوفر']]))->assertOk();

    $order = $this->order->fresh();
    expect($order->status)->toBe('rejected')
        ->and($order->statusHistory()->first()->note)->toBe('الورق المطلوب غير متوفر')
        ->and(Notification::where('user_id', $this->admin->id)->where('type', 'order.rejected_by_provider')->exists())->toBeTrue();
});

test('the shop can cancel a WhatsApp rejection before giving the reason', function () {
    $offer = ($this->sendOffer)();
    Queue::fake();

    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN1', 'type' => 'button', 'button' => ['payload' => 'reject:'.$offer->action_token]]));
    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN2', 'type' => 'text', 'text' => ['body' => 'إلغاء']]));
    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN3', 'type' => 'text', 'text' => ['body' => 'رسالة عادية بعد الإلغاء']]));

    expect($this->order->fresh()->status)->toBe('processing');
});

test('accepting on WhatsApp after the dashboard already decided only explains it', function () {
    $offer = ($this->sendOffer)();
    Queue::fake();

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.reject', $this->order), ['reason' => 'لا يوجد ورق'])->assertOk();
    ($this->webhook)(($this->inbound)(['id' => 'wamid.IN1', 'type' => 'button', 'button' => ['payload' => 'accept:'.$offer->action_token]]))->assertOk();

    expect($this->order->fresh()->status)->toBe('rejected');
    Queue::assertPushed(SendWhatsAppReply::class, fn ($job) => str_contains($job->body, 'لم يعد بانتظار ردك'));
});

test('delivery statuses move forward only', function () {
    $offer = ($this->sendOffer)();
    $status = fn (string $value) => ['object' => 'whatsapp_business_account', 'entry' => [['id' => 'WABA', 'changes' => [['field' => 'messages', 'value' => [
        'statuses' => [['id' => 'wamid.OFFER1', 'status' => $value, 'timestamp' => (string) time(), 'recipient_id' => '970599123456']],
    ]]]]]];

    ($this->webhook)($status('read'))->assertOk();
    ($this->webhook)($status('delivered'))->assertOk();
    ($this->webhook)($status('failed'))->assertOk();

    $offer->refresh();
    expect($offer->status)->toBe('read')->and($offer->read_at)->not->toBeNull()->and($offer->delivered_at)->not->toBeNull();
});

test('the shop opts in from its profile and the number is stored normalised', function () {
    $this->provider->update(['whatsapp_number' => null, 'whatsapp_notifications_enabled' => false, 'whatsapp_opted_in_at' => null]);

    $form = [
        'company_name' => 'مطبعة النور', 'contact_name' => 'صاحب المطبعة', 'email' => $this->owner->email,
        'phone' => '022222222', 'owner_phone' => '0599000000', 'address' => 'رام الله',
    ];

    $this->actingAs($this->owner)->patch(route('print-provider.profile.update'), $form + ['whatsapp_notifications' => '1'])
        ->assertSessionHasErrors('whatsapp_number');

    $this->actingAs($this->owner)->patch(route('print-provider.profile.update'), $form + ['whatsapp_number' => '123', 'whatsapp_notifications' => '1'])
        ->assertSessionHasErrors('whatsapp_number');

    $this->actingAs($this->owner)->patch(route('print-provider.profile.update'), $form + ['whatsapp_number' => '0599 123 456', 'whatsapp_notifications' => '1'])
        ->assertSessionHasNoErrors();

    $provider = $this->provider->fresh();
    expect($provider->whatsapp_number)->toBe('970599123456')
        ->and($provider->whatsapp_notifications_enabled)->toBeTrue()
        ->and($provider->whatsapp_opted_in_at)->not->toBeNull();
});
