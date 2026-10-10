<?php

use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['name' => 'سارة', 'is_active' => true]);
    $this->customer->assignRole('customer');

    $this->shopOwner = User::factory()->create(['is_active' => true]);
    $this->shopOwner->assignRole('print_provider');
    $this->branch = $this->shopOwner->printProvider()->create(['company_name' => 'مطبعة النور', 'approval_status' => 'approved', 'is_active' => true])->primaryBranch();

    $category = Category::create(['name' => 'ملابس', 'slug' => 'clothes']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'تيشيرت', 'code' => 'TSHIRT-CLASSIC', 'is_active' => true]);
    $this->variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'TS-1', 'is_active' => true]);
    $this->offering = $this->branch->branchProductOfferings()->create([
        'product_id' => $this->product->id, 'base_price' => 20, 'currency' => 'ILS',
        'production_time_min' => 2, 'production_time_max' => 2, 'daily_capacity' => 10, 'is_active' => true,
    ]);
});

function orderIn(string $status, string $paymentStatus = 'paid'): Order
{
    $order = Order::create([
        'user_id' => test()->customer->id, 'order_number' => 'PP-'.random_int(10000, 99999), 'status' => $status,
        'payment_status' => $paymentStatus, 'payment_method' => 'bank', 'subtotal' => 30, 'total_amount' => 30,
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'product_id' => test()->product->id, 'variant_id' => test()->variant->id,
        'print_provider_branch_id' => test()->branch->id, 'branch_product_offering_id' => test()->offering->id,
        'quantity' => 1, 'unit_price' => 30, 'total_price' => 30,
    ]);

    return $order;
}

function moveTo(Order $order, string $state)
{
    return test()->actingAs(test()->admin)->patchJson(route('admin.orders.update', $order->id), ['status' => $state]);
}

test('the admin can only move an order along: ready to shipped to completed, or cancel it', function (string $from, string $to, bool $allowed, string $result) {
    $order = orderIn($from);

    $response = moveTo($order, $to);

    $allowed ? $response->assertOk() : $response->assertStatus(422);
    expect($order->fresh()->status)->toBe($result);
})->with([
    'ready -> shipped' => ['ready', 'shipped', true, 'shipped'],
    'shipped -> completed (stored as delivered)' => ['shipped', 'completed', true, 'delivered'],
    'ready -> cancelled' => ['ready', 'cancelled', true, 'cancelled'],
    'in progress -> cancelled' => ['confirmed', 'cancelled', true, 'cancelled'],
    'new for the shop -> cancelled' => ['processing', 'cancelled', true, 'cancelled'],
    'shop rejected -> cancelled' => ['rejected', 'cancelled', true, 'cancelled'],
    'in progress -> shipped is refused' => ['confirmed', 'shipped', false, 'confirmed'],
    'new for the shop -> shipped is refused' => ['processing', 'shipped', false, 'processing'],
    'ready -> completed skips shipping' => ['ready', 'completed', false, 'ready'],
    'shipped -> cancelled is refused' => ['shipped', 'cancelled', false, 'shipped'],
    'completed stays' => ['delivered', 'cancelled', false, 'delivered'],
    'cancelled cannot be reopened' => ['cancelled', 'shipped', false, 'cancelled'],
]);

test('the admin cannot set the states that belong to the shop or the payment review', function (string $state) {
    $order = orderIn('ready');

    moveTo($order, $state)->assertStatus(422);
    expect($order->fresh()->status)->toBe('ready');
})->with(['processing', 'pending', 'confirmed']);

test('an order waiting for its payment review cannot be changed from the orders page, only from the payment notices', function (string $state) {
    $order = orderIn('awaiting_payment_review', 'pending_review');
    Payment::create(['order_id' => $order->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-'.$order->id, 'status' => 'pending_review', 'method' => 'bank', 'amount' => 30]);

    moveTo($order, $state)->assertStatus(422)->assertJsonPath('message', fn ($message) => str_contains($message, 'إشعارات الدفع'));

    expect($order->fresh()->status)->toBe('awaiting_payment_review')->and($order->fresh()->payment_status)->toBe('pending_review');
})->with(['shipped', 'completed', 'cancelled']);

test('the old second way to approve a payment from the orders page is gone', function () {
    $order = orderIn('awaiting_payment_review', 'pending_review');

    $this->actingAs($this->admin)->postJson('/admin/orders/'.$order->id.'/payment/approve')->assertStatus(404);
    expect($order->fresh()->payment_status)->toBe('pending_review');
});

test('cancelling a paid order tells the admins to return the money and tells the shop to stop', function () {
    $order = orderIn('confirmed', 'paid');

    moveTo($order, 'cancelled')->assertOk();

    expect(Notification::where('user_id', $this->admin->id)->where('type', 'order.refund_needed')->count())->toBe(1)
        ->and(Notification::where('user_id', $this->shopOwner->id)->where('type', 'order.cancelled_for_provider')->count())->toBe(1)
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.cancelled')->count())->toBe(1);
});

test('cancelling an order the shop had already turned down does not bother that shop', function () {
    $order = orderIn('rejected', 'paid');

    moveTo($order, 'cancelled')->assertOk();

    expect(Notification::where('type', 'order.cancelled_for_provider')->count())->toBe(0)
        ->and(Notification::where('type', 'order.refund_needed')->count())->toBe(1);
});

test('shipping and completing tell the customer, and a customer or shop cannot use this page', function () {
    $order = orderIn('ready');

    moveTo($order, 'shipped')->assertOk();
    moveTo($order, 'completed')->assertOk();

    expect(Notification::where('user_id', $this->customer->id)->whereIn('type', ['order.shipped', 'order.delivered'])->count())->toBe(2);

    $other = orderIn('ready');
    $this->actingAs($this->customer)->patchJson(route('admin.orders.update', $other->id), ['status' => 'shipped'])->assertForbidden();
    $this->actingAs($this->shopOwner)->patchJson(route('admin.orders.update', $other->id), ['status' => 'shipped'])->assertForbidden();
    expect($other->fresh()->status)->toBe('ready');
});

/** A second shop, with the product and the variant of the order (so it can make it). */
function secondShop(string $name, bool $offersProduct = true, ?string $city = null)
{
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $branch = $owner->printProvider()->create(['company_name' => $name, 'approval_status' => 'approved', 'is_active' => true])->primaryBranch();
    if ($city) {
        $branch->update(['city' => $city]);
    }
    if ($offersProduct) {
        $offering = $branch->branchProductOfferings()->create([
            'product_id' => test()->product->id, 'base_price' => 25, 'currency' => 'ILS',
            'production_time_min' => 3, 'production_time_max' => 3, 'daily_capacity' => 10, 'is_active' => true,
        ]);
        $offering->branchOfferingVariants()->create(['variant_id' => test()->variant->id, 'is_available' => true]);
    }

    return [$owner, $branch];
}

test('a shop turning an order down tells the admin and records which shop it was', function () {
    $order = orderIn('processing');
    $order->items()->update(['print_provider_branch_id' => $this->branch->id]);

    $this->actingAs($this->shopOwner)->postJson(route('print-provider.requests.reject', $order), ['reason' => 'لا نملك الخامة'])->assertOk();

    expect($order->fresh()->status)->toBe('rejected')
        ->and(Notification::where('user_id', $this->admin->id)->where('type', 'order.rejected_by_provider')->count())->toBe(1)
        ->and(\App\Models\OrderStatusHistory::where('order_id', $order->id)->where('to_status', 'rejected')->first()->metadata['rejected_branch_ids'])->toBe([$this->branch->id]);
});

test('an order a shop turned down is offered to the other shops, never to the one that refused', function () {
    $this->offering->branchOfferingVariants()->create(['variant_id' => $this->variant->id, 'is_available' => true]);
    [, $near] = secondShop('مطبعة الأمل');
    [, $far] = secondShop('مطبعة الجنوب');
    secondShop('مطبعة بدون تيشيرت', false);

    $order = orderIn('rejected');
    $order->items()->update(['print_provider_branch_id' => $this->branch->id]);
    \App\Models\OrderStatusHistory::create(['order_id' => $order->id, 'changed_by' => $this->shopOwner->id, 'from_status' => 'processing', 'to_status' => 'rejected', 'metadata' => ['rejected_branch_ids' => [$this->branch->id]]]);

    $html = $this->actingAs($this->admin)->get(route('admin.orders'))->assertOk()->getContent();

    // Only what is offered for re-routing counts (the page lists every shop elsewhere, e.g. in the display-only printer field).
    preg_match('/data-reroute="([^"]+)"/', $html, $match);
    $choices = html_entity_decode($match[1]);

    expect($choices)->toContain('مطبعة الأمل')
        ->and($choices)->toContain('مطبعة الجنوب')
        ->and($choices)->not->toContain('مطبعة بدون تيشيرت') // cannot make the product
        ->and($choices)->not->toContain('مطبعة النور') // the shop that refused
        ->and($choices)->toContain(str_replace('/', '\/', route('admin.orders.reroute', $order->id)));
});

test('sending a rejected order to another shop makes it new for that shop and tells everyone concerned', function () {
    [$otherOwner, $other] = secondShop('مطبعة الأمل');
    $order = orderIn('rejected');
    $order->items()->update(['print_provider_branch_id' => $this->branch->id]);

    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $other->id])->assertOk();

    $order->refresh();
    expect($order->status)->toBe('processing')
        ->and($order->items()->first()->print_provider_branch_id)->toBe($other->id)
        ->and($order->items()->first()->branch_product_offering_id)->toBe($other->branchProductOfferings()->first()->id)
        ->and(Notification::where('user_id', $otherOwner->id)->where('type', 'order.new_for_provider')->count())->toBe(1)
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.processing')->count())->toBe(1)
        ->and(\App\Models\OrderStatusHistory::where('order_id', $order->id)->where('to_status', 'processing')->count())->toBe(1);

    // The shop that refused no longer has it; the new one does.
    $this->actingAs($this->shopOwner)->get(route('print-provider.requests'))->assertOk()->assertDontSee($order->order_number);
    $this->actingAs($otherOwner)->get(route('print-provider.requests'))->assertOk()->assertSee($order->order_number);
});

test('a rejected order cannot go back to the shop that refused it, or to a shop that cannot make it, or when it is not rejected', function () {
    [, $cannot] = secondShop('مطبعة بدون تيشيرت', false);
    [, $other] = secondShop('مطبعة الأمل');
    $order = orderIn('rejected');
    $order->items()->update(['print_provider_branch_id' => $this->branch->id]);

    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $this->branch->id])->assertStatus(422);
    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $cannot->id])->assertStatus(422);
    expect($order->fresh()->status)->toBe('rejected');

    $running = orderIn('confirmed');
    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $running->id), ['branch_id' => $other->id])->assertStatus(422);
    expect($running->fresh()->status)->toBe('confirmed');
});

test('an order refused twice never returns to either shop that refused it', function () {
    [$secondOwner, $second] = secondShop('مطبعة الأمل');
    [, $third] = secondShop('مطبعة الجنوب');
    // The first shop really refuses it (this is what records which shop it was).
    $order = orderIn('processing');
    $order->items()->update(['print_provider_branch_id' => $this->branch->id]);
    $this->actingAs($this->shopOwner)->postJson(route('print-provider.requests.reject', $order), ['reason' => 'لا نملك الخامة'])->assertOk();

    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $second->id])->assertOk();
    $this->actingAs($secondOwner)->postJson(route('print-provider.requests.reject', $order), ['reason' => 'مشغولون'])->assertOk();

    expect($order->fresh()->status)->toBe('rejected');
    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $this->branch->id])->assertStatus(422);
    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $second->id])->assertStatus(422);
    $this->actingAs($this->admin)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $third->id])->assertOk();
});

test('only an admin can send an order to another shop', function () {
    [, $other] = secondShop('مطبعة الأمل');
    $order = orderIn('rejected');

    $this->actingAs($this->customer)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $other->id])->assertForbidden();
    $this->actingAs($this->shopOwner)->postJson(route('admin.orders.reroute', $order->id), ['branch_id' => $other->id])->assertForbidden();
});

test('the orders page tells each order where it can go', function () {
    $ready = orderIn('ready');
    $waiting = orderIn('awaiting_payment_review', 'pending_review');
    $done = orderIn('delivered');

    $html = $this->actingAs($this->admin)->get(route('admin.orders'))->assertOk()->getContent();

    expect($html)->toContain('data-next="shipped,cancelled"')
        ->and($html)->toContain('data-awaiting-payment="1"')
        ->and($html)->toContain('data-next=""')
        ->and($html)->not->toContain('approvePaymentButton')
        ->and($html)->toContain(route('admin.payment-notices'));
});
