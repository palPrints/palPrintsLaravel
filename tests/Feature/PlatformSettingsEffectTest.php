<?php

use App\Models\Notification;
use App\Models\User;
use App\Support\OrderNotifier;
use App\Support\PlatformSettings;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // The settings live in a JSON file on the default disk: keep the real one untouched.
    Storage::fake('local');
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');
});

test('maintenance mode shows the 503 page to visitors and customers but not to admins', function () {
    PlatformSettings::update('general', ['maintenance_mode' => true]);

    $this->get('/')->assertStatus(503);
    $this->get(route('customer.store'))->assertStatus(503);
    $this->get(route('login'))->assertOk(); // sign-in stays reachable so an admin can get in
    $this->postJson(route('login'), [])->assertStatus(422);
    $this->actingAs($this->customer)->get(route('customer.store'))->assertStatus(503);

    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($this->admin)->get('/')->assertOk();
});

test('the site is open again once maintenance mode is switched off', function () {
    PlatformSettings::update('general', ['maintenance_mode' => true]);
    PlatformSettings::update('general', ['maintenance_mode' => false]);

    $this->get('/')->assertOk();
});

test('checkout only offers the payment methods the admin switched on', function () {
    PlatformSettings::update('payments', ['palpay' => false, 'jawwal_pay' => true, 'bank_of_palestine' => false]);

    $methods = (new ReflectionMethod(\App\Http\Controllers\Customer\CheckoutController::class, 'enabledPaymentMethods'));
    $controller = new \App\Http\Controllers\Customer\CheckoutController();

    expect($methods->invoke($controller))->toBe(['jawwal']);
});

test('the last payment method that is on cannot be switched off', function () {
    PlatformSettings::update('payments', ['palpay' => true, 'jawwal_pay' => false, 'bank_of_palestine' => false]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.settings.payments', 'palpay'), ['enabled' => 0])
        ->assertStatus(422);

    expect(PlatformSettings::get('payments', 'palpay'))->toBeTrue();

    $this->actingAs($this->admin)
        ->postJson(route('admin.settings.payments', 'jawwal_pay'), ['enabled' => 1])->assertOk();
    $this->actingAs($this->admin)
        ->postJson(route('admin.settings.payments', 'palpay'), ['enabled' => 0])->assertOk();
});

test('order updates are not sent while that switch is off, and the notices a shop or admin must act on still are', function () {
    PlatformSettings::update('notifications', ['order_updates' => false]);

    OrderNotifier::statusChanged($this->customer->id, 'PP-1', 'shipped');
    expect(Notification::where('user_id', $this->customer->id)->count())->toBe(0);

    OrderNotifier::newOrderForShop($this->customer->id, 'PP-1', 1);
    expect(Notification::where('type', 'order.new_for_provider')->count())->toBe(1);

    PlatformSettings::update('notifications', ['order_updates' => true]);
    OrderNotifier::statusChanged($this->customer->id, 'PP-1', 'shipped');
    expect(Notification::where('type', 'order.shipped')->count())->toBe(1);
});

test('payment and approval notices follow their own switches', function () {
    PlatformSettings::update('notifications', ['payments_withdrawals' => false, 'approval_results' => true]);

    OrderNotifier::paymentReviewed($this->customer->id, 'PP-2', true);
    expect(Notification::where('type', 'order.payment_approved')->count())->toBe(0);

    Notification::create(['user_id' => $this->customer->id, 'type' => 'approval.approved', 'title' => 'x', 'message' => 'y']);
    expect(Notification::where('type', 'approval.approved')->count())->toBe(1);
});
