<?php

use App\Jobs\ForwardNotificationToShop;
use App\Models\Notification;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'telegram.enabled' => true, 'telegram.bot_token' => 'TESTTOKEN', 'telegram.bot_username' => 'palprints_test_bot',
        'whatsapp.enabled' => false, 'whatsapp.phone_number_id' => '111222333', 'whatsapp.access_token' => 'wa-token',
    ]);
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['is_active' => true]);
    $this->owner->assignRole('print_provider');
    $this->provider = $this->owner->printProvider()->create([
        'company_name' => 'مطبعة النور', 'approval_status' => 'approved', 'is_active' => true, 'telegram_chat_id' => '555',
    ]);

    $this->notify = fn (string $type = 'withdrawal.approved', ?User $user = null) => Notification::create([
        'user_id' => ($user ?? $this->owner)->id, 'type' => $type, 'title' => 'تمت الموافقة على السحب',
        'message' => 'تم تحويل 200 ₪ إلى محفظتك في PalPay.', 'link' => route('print-provider.earnings'),
    ]);

    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 9]]), 'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.N1']]])]);
});

test('a notification for a shop that connected Telegram is sent there too, with its link', function () {
    ($this->notify)();

    Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage') && $r['chat_id'] === '555'
        && str_contains($r['text'], '<b>تمت الموافقة على السحب</b>')
        && str_contains($r['text'], 'تم تحويل 200 ₪')
        && str_contains($r['text'], route('print-provider.earnings')));
});

test('the new-order notice is not repeated, since the order itself reaches the chat with its buttons', function () {
    ($this->notify)('order.new_for_provider');

    Http::assertNothingSent();
});

test('nothing goes out for a shop without a chat, for other accounts, or with the channels off', function () {
    Queue::fake();

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    ($this->notify)('order.confirmed', $customer);

    $this->provider->update(['telegram_chat_id' => null]);
    ($this->notify)();

    $this->provider->update(['telegram_chat_id' => '555', 'is_active' => false]);
    ($this->notify)();

    $this->provider->update(['is_active' => true]);
    config(['telegram.enabled' => false]);
    ($this->notify)();

    Queue::assertNothingPushed();
});

test('with the WhatsApp Cloud API on, an opted-in shop gets the notification template', function () {
    config(['whatsapp.enabled' => true, 'telegram.enabled' => false]);
    $this->provider->update(['whatsapp_number' => '970599123456', 'whatsapp_notifications_enabled' => true]);

    ($this->notify)();

    Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'graph.facebook.com') && $r['to'] === '970599123456'
        && $r['template']['name'] === 'palprints_notification'
        && collect($r['template']['components'][0]['parameters'])->pluck('text')->all() === ['تمت الموافقة على السحب', 'تم تحويل 200 ₪ إلى محفظتك في PalPay.']);
    expect(WhatsAppMessage::where('purpose', 'notification')->value('wa_message_id'))->toBe('wamid.N1');
});

test('a chat that refuses the message does not break the notification', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden: bot was blocked by the user'], 403)]);

    $notification = ($this->notify)();

    expect($notification->exists)->toBeTrue()->and(Notification::count())->toBe(1);
});

test('the bell feed gives the open page the unread count, the newest notice and the menu', function () {
    config(['telegram.enabled' => false]);
    $older = ($this->notify)();
    $older->update(['is_read' => true]);
    $newest = ($this->notify)();

    $this->actingAs($this->owner)->getJson(route('print-provider.notifications.feed'))
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonPath('latest_id', $newest->id)
        ->assertJsonPath('newest.title', 'تمت الموافقة على السحب')
        ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, 'تم تحويل 200 ₪') && str_contains($html, '1 جديد'))->etc());

    // the page starts from the newest notice it already shows
    $this->actingAs($this->owner)->get(route('print-provider.notifications'))->assertOk()
        ->assertSee('data-latest-id="'.$newest->id.'"', false)
        ->assertSee('data-feed-url="'.route('print-provider.notifications.feed').'"', false);
});

test('the feed is only for the shop itself', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->getJson(route('print-provider.notifications.feed'))->assertUnauthorized();
    $this->actingAs($customer)->getJson(route('print-provider.notifications.feed'))->assertForbidden();
});
