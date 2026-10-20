<?php

// The whole life of a print shop on PALPRINTS, through the real pages: sign-up, profile, admin approval, services and
// prices, connecting Telegram / WhatsApp, a customer's paper order, the admin routing it, the shop's decision (in the
// chat or on the dashboard), the order going out, and a refusal sent on to another shop.

use App\Models\ApprovalRequest;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PrintFile;
use App\Models\PrintProvider;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\TelegramMessage;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantValue;
use App\Support\PaperPrices;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    config([
        'telegram.enabled' => true, 'telegram.bot_token' => 'TESTTOKEN', 'telegram.bot_username' => 'palprints_test_bot', 'telegram.webhook_secret' => 'tg-secret',
        'whatsapp.enabled' => false,
    ]);
    $this->seed(RoleAndPermissionSeeder::class);

    // Telegram answers every call: a document gets message 101, a text message 102.
    Http::fake([
        'api.telegram.org/botTESTTOKEN/sendDocument' => Http::response(['ok' => true, 'result' => ['message_id' => 101]]),
        'api.telegram.org/botTESTTOKEN/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 102]]),
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
    ]);

    // The paper-printing product: white paper in A4 and A5.
    $category = Category::create(['name' => 'ورق', 'slug' => 'paper']);
    $this->paper = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);
    $color = Attribute::create(['name' => 'اللون', 'code' => 'color', 'data_type' => 'text', 'is_active' => true]);
    $size = Attribute::create(['name' => 'المقاس', 'code' => 'size', 'data_type' => 'text', 'is_active' => true]);
    $white = AttributeValue::create(['attribute_id' => $color->id, 'value' => 'أبيض', 'code' => 'white', 'sort_order' => 1]);
    $colorAxis = ProductAttribute::create(['product_id' => $this->paper->id, 'attribute_id' => $color->id, 'is_variant_axis' => true, 'is_required' => true, 'sort_order' => 1]);
    $sizeAxis = ProductAttribute::create(['product_id' => $this->paper->id, 'attribute_id' => $size->id, 'is_variant_axis' => true, 'is_required' => true, 'sort_order' => 2]);
    $whiteValue = ProductAttributeValue::create(['product_attribute_id' => $colorAxis->id, 'attribute_value_id' => $white->id, 'is_active' => true]);
    $this->variants = [];
    foreach (['a4' => 'A4', 'a5' => 'A5'] as $code => $label) {
        $value = AttributeValue::create(['attribute_id' => $size->id, 'value' => $label, 'code' => $code, 'sort_order' => 1]);
        $variant = Variant::create(['product_id' => $this->paper->id, 'sku' => 'PP-WHITE-'.$label, 'is_active' => true]);
        $sizeValue = ProductAttributeValue::create(['product_attribute_id' => $sizeAxis->id, 'attribute_value_id' => $value->id, 'is_active' => true]);
        VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $whiteValue->id]);
        VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $sizeValue->id]);
        $this->variants[$code] = $variant;
    }

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['name' => 'سارة العميلة', 'is_active' => true]);
    $this->customer->assignRole('customer');

    // The shop's paper prices, sent the way the services dialog sends them.
    $this->paperSettings = [
        'price' => 1, 'days' => 2, 'capacity' => 200, 'colors' => ['white'], 'sizes' => ['a4', 'a5'],
        'paper_types' => ['standard'],
        'paper_ink' => ['standard' => ['bw' => ['count' => 100, 'price' => 9], 'color' => ['count' => 100, 'price' => 42]]],
        'paper_bindings' => ['spiral' => 8],
    ];

    /** A shop that is already live (approved, paper on sale), for the re-routing story. */
    $this->liveShop = function (string $name, array $extra = []) {
        $owner = User::factory()->create(['is_active' => true]);
        $owner->assignRole('print_provider');
        $provider = $owner->printProvider()->create($extra + ['company_name' => $name, 'approval_status' => 'approved', 'is_active' => true]);
        $offering = $provider->primaryBranch()->branchProductOfferings()->create([
            'product_id' => $this->paper->id, 'base_price' => 1, 'currency' => 'ILS',
            'production_time_min' => 1, 'production_time_max' => 1, 'daily_capacity' => 50, 'is_active' => true,
        ]);
        foreach ($this->variants as $variant) {
            $offering->branchOfferingVariants()->create(['variant_id' => $variant->id, 'is_available' => true]);
        }
        PaperPrices::save($offering->refresh(), collect($this->variants)->mapWithKeys(fn ($v, $code) => [$v->id => $code])->all(), [
            'types' => ['standard'], 'ink' => ['standard' => ['bw' => ['count' => 100, 'price' => 9]]], 'bindings' => ['spiral' => 8],
        ], ['a4', 'a5']);

        return $provider;
    };

    /** The customer orders 10 A4 pages, black and white, uploads a PDF and a payment receipt; returns the order. */
    $this->customerOrders = function (): Order {
        $this->actingAs($this->customer)->post(route('customer.cart.store-paper'), [
            'job' => json_encode(['grouping' => 'combined', 'quantity' => 1, 'combined_binding' => 'none', 'files' => [
                ['pages' => 10, 'size' => 'A4', 'paper' => 'standard', 'color' => 'bw', 'sides' => 'single', 'layout' => 1, 'binding' => null, 'quantity' => 1],
            ]]),
            'settings' => ['paper_type' => 'standard', 'color_mode' => 'bw'],
            'files' => [UploadedFile::fake()->create('thesis.pdf', 30, 'application/pdf')],
        ])->assertSessionHasNoErrors()->assertRedirect(route('customer.basket'));

        $this->actingAs($this->customer)->get(route('customer.checkout'))->assertOk();
        $this->actingAs($this->customer)->post(route('customer.checkout.store'), [
            'recipient_name' => 'سارة العميلة', 'phone' => '0599111222', 'city' => 'رام الله', 'street' => 'شارع الإرسال',
            'payment_method' => 'palpay', 'payment_receipt' => UploadedFile::fake()->create('receipt.jpg', 40, 'image/jpeg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('customer.orders'));

        return Order::latest('id')->firstOrFail();
    };

    /** The admin approves the order's payment notice and sends it to the given shop's branch. */
    $this->adminRoutes = function (Order $order, PrintProvider $provider) {
        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $this->actingAs($this->admin)->get(route('admin.payment-notices'))->assertOk()->assertSee($order->order_number);

        return $this->actingAs($this->admin)->post(route('admin.payment-notices.approve', $payment), ['branch_id' => $provider->branches()->value('id')]);
    };

    $this->telegram = fn (array $update) => $this->postJson(route('webhooks.telegram'), $update + ['update_id' => random_int(1, 1_000_000_000)], ['X-Telegram-Bot-Api-Secret-Token' => 'tg-secret'])->assertOk();
    $this->tap = fn (string $data, string $chat) => ($this->telegram)(['callback_query' => ['id' => 'cb-'.random_int(1, 99999), 'from' => ['id' => (int) $chat], 'data' => $data, 'message' => ['message_id' => 102, 'chat' => ['id' => (int) $chat, 'type' => 'private']]]]);
    $this->say = fn (string $text, string $chat) => ($this->telegram)(['message' => ['message_id' => random_int(200, 9999), 'from' => ['id' => (int) $chat, 'username' => 'shop'], 'chat' => ['id' => (int) $chat, 'type' => 'private'], 'text' => $text]]);
});

test('a print shop goes from sign-up to a delivered order', function () {
    // 1. Sign-up as a print shop.
    $this->post(route('register'), [
        'account_type' => 'print_provider', 'name' => 'مطبعة الأمل', 'email' => 'alamal@palprints.test',
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!', 'terms' => '1',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login'));
    $owner = User::where('email', 'alamal@palprints.test')->firstOrFail();
    expect($owner->hasRole('print_provider'))->toBeTrue()->and($owner->approvalStatus())->toBe('draft');
    auth()->logout();

    // Before approval the shop cannot reach its orders or services.
    $this->actingAs($owner)->get(route('print-provider.requests'))->assertRedirect();

    // 2. The profile: shop data, WhatsApp opt-in, documents, and paper printing among its products.
    $this->actingAs($owner)->get(route('print-provider.profile'))->assertOk();
    $this->actingAs($owner)->patch(route('print-provider.profile.update'), [
        'company_name' => 'مطبعة الأمل', 'contact_name' => 'أمل الطابعة', 'email' => 'alamal@palprints.test',
        'phone' => '022987654', 'owner_phone' => '0599000111', 'address' => 'رام الله، شارع الإرسال',
        'whatsapp_number' => '+972 59 768 1977', 'whatsapp_notifications' => '1',
        'verification_document' => UploadedFile::fake()->create('licence.pdf', 40, 'application/pdf'),
        'id_document' => UploadedFile::fake()->create('id.jpg', 40, 'image/jpeg'),
        'products' => [$this->paper->id],
        'available' => '1', 'days' => ['السبت', 'الأحد'], 'from' => '08:00', 'to' => '18:00',
    ])->assertSessionHasNoErrors();
    $provider = $owner->fresh()->printProvider;
    expect($provider->profile_completed_at)->not->toBeNull()
        ->and($provider->whatsapp_number)->toBe('972597681977')
        ->and($provider->whatsapp_notifications_enabled)->toBeTrue();

    // 3. Sent for approval, 4. approved by the admin. (A fresh copy of the user, as every real request loads one.)
    $owner = $owner->fresh();
    $this->actingAs($owner)->post(route('onboarding.submit'))->assertSessionHasNoErrors();
    $request = ApprovalRequest::where('user_id', $owner->id)->firstOrFail();
    $this->actingAs($this->admin)->post(route('admin.approval-requests.review', $request), ['action' => 'approved'])->assertSessionHasNoErrors();
    $owner = $owner->fresh();
    expect($owner->approvalStatus())->toBe('approved');

    // 5. Paper printing priced and on sale.
    $this->actingAs($owner)->get(route('print-provider.services'))->assertOk();
    $this->actingAs($owner)->putJson(route('print-provider.services.save', $this->paper), $this->paperSettings)->assertOk();
    $offering = $provider->primaryBranch()->branchProductOfferings()->where('product_id', $this->paper->id)->firstOrFail();
    expect($offering->is_active)->toBeTrue();

    // 6. Telegram connected from the profile (one-time t.me link, then Start in the bot).
    $link = $this->actingAs($owner)->post(route('print-provider.telegram.link'))->headers->get('Location');
    expect($link)->toStartWith('https://t.me/palprints_test_bot?start=');
    ($this->say)('/start '.explode('?start=', $link)[1], '555');
    expect($provider->fresh()->telegram_chat_id)->toBe('555');

    // 7. A customer orders paper printing; the shop sees nothing until the admin approves the payment.
    $order = ($this->customerOrders)();
    expect($order->status)->toBe('awaiting_payment_review');
    $this->actingAs($owner)->get(route('print-provider.requests'))->assertOk()->assertDontSee($order->order_number);

    // 8. The admin approves and routes it to the shop: Telegram goes out on its own, WhatsApp is ready for the admin.
    $response = ($this->adminRoutes)($order, $provider)->assertSessionHasNoErrors();
    $notice = $response->getSession()->get('notice_whatsapp');
    expect($order->fresh()->status)->toBe('processing')
        ->and($notice['telegram'])->toBeTrue()
        ->and($notice['url'])->toStartWith('https://wa.me/972597681977?text=')
        ->and(rawurldecode($notice['url']))->toContain($order->order_number)->not->toContain('سارة العميلة')
        ->and(Notification::where('user_id', $owner->id)->where('type', 'order.new_for_provider')->exists())->toBeTrue();

    Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendDocument') && collect($r->data())->firstWhere('name', 'document')['filename'] === 'thesis.pdf');
    Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage') && str_contains((string) $r['text'], $order->order_number) && isset($r['reply_markup']['inline_keyboard']));

    // The file link in the WhatsApp message works without logging in.
    preg_match('#https?://\S+/shared/print-files/\d+\?\S+#', rawurldecode($notice['url']), $fileLink);
    auth()->logout();
    $this->get($fileLink[0])->assertOk();

    // 9. The shop sees the order with its file on the dashboard.
    $file = PrintFile::whereNotNull('order_item_id')->firstOrFail();
    $this->actingAs($owner)->get(route('print-provider.requests'))->assertOk()->assertSee($order->order_number);
    $this->actingAs($owner)->get(route('print-provider.requests.files', [$order, $file]))->assertOk();

    // 10. It accepts from Telegram: the order is confirmed and the customer is told.
    $offer = TelegramMessage::where(['order_id' => $order->id, 'purpose' => 'offer'])->firstOrFail();
    ($this->tap)('acc:'.$offer->action_token, '555');
    expect($order->fresh()->status)->toBe('confirmed')
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.confirmed')->exists())->toBeTrue();

    // 11. Ready on the dashboard, 12. shipped and delivered by the admin.
    $this->actingAs($owner)->postJson(route('print-provider.requests.ready', $order))->assertOk();
    expect($order->fresh()->status)->toBe('ready');
    $this->actingAs($this->admin)->patchJson(route('admin.orders.update', $order->id), ['status' => 'shipped'])->assertOk();
    $this->actingAs($this->admin)->patchJson(route('admin.orders.update', $order->id), ['status' => 'completed'])->assertOk();
    expect($order->fresh()->status)->toBe('delivered');

    $this->actingAs($this->customer)->get(route('customer.orders'))->assertOk()->assertSee($order->order_number);
    expect(Notification::where('user_id', $this->customer->id)->whereIn('type', ['order.ready', 'order.shipped', 'order.delivered'])->count())->toBe(3);

    // The shop's pages still open after the order is done.
    $this->actingAs($owner)->get(route('print-provider.dashboard'))->assertOk();
    $this->actingAs($owner)->get(route('print-provider.earnings'))->assertOk();
});

test('a shop that refuses an order in Telegram hands it back, and the admin sends it to another shop', function () {
    $first = ($this->liveShop)('مطبعة النور', ['telegram_chat_id' => '777']);
    $second = ($this->liveShop)('مطبعة القدس', ['whatsapp_number' => '0599333444', 'whatsapp_notifications_enabled' => true]);

    $order = ($this->customerOrders)();
    ($this->adminRoutes)($order, $first)->assertSessionHasNoErrors();

    // The first shop refuses in the chat and gives its reason.
    $offer = TelegramMessage::where(['order_id' => $order->id, 'purpose' => 'offer'])->firstOrFail();
    ($this->tap)('rej:'.$offer->action_token, '777');
    ($this->say)('الورق الفاخر غير متوفر هذا الأسبوع', '777');

    expect($order->fresh()->status)->toBe('rejected')
        ->and(Notification::where('user_id', $this->admin->id)->where('type', 'order.rejected_by_provider')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.rejected')->exists())->toBeFalse();

    // It cannot be sent back to the shop that refused it; the second shop gets it.
    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order), ['branch_id' => $first->branches()->value('id')])->assertStatus(422);
    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order), ['branch_id' => $second->branches()->value('id')])->assertOk();

    $notice = session('notice_whatsapp');
    expect($order->fresh()->status)->toBe('processing')
        ->and($notice['url'])->toStartWith('https://wa.me/970599333444')
        ->and($notice['telegram'])->toBeFalse()
        ->and(Notification::where('user_id', $second->user_id)->where('type', 'order.new_for_provider')->exists())->toBeTrue();

    // The first shop no longer has it; the second accepts it on the dashboard.
    $listed = fn (User $shopUser) => collect($this->actingAs($shopUser)->get(route('print-provider.requests'))->assertOk()->viewData('orders'))->pluck('number')->all();
    expect($listed($first->user))->not->toContain($order->order_number)->and($listed($second->user))->toContain($order->order_number);
    $this->actingAs($second->user)->postJson(route('print-provider.requests.accept', $order))->assertOk();
    expect($order->fresh()->status)->toBe('confirmed');
});
