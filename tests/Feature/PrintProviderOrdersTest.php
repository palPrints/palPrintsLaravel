<?php

use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Storage;

function ordersShop(string $name): array
{
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $provider = $owner->printProvider()->create(['company_name' => $name, 'approval_status' => 'approved', 'is_active' => true]);

    return [$owner, $provider->primaryBranch()];
}

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleAndPermissionSeeder::class);

    $category = Category::create(['name' => 'ورق', 'slug' => 'paper']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);
    $this->variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'PP-A4', 'is_active' => true]);

    [$this->owner, $this->branch] = ordersShop('مطبعة النور');
    [$this->otherOwner, $this->otherBranch] = ordersShop('مطبعة الأمل');

    foreach ([$this->branch, $this->otherBranch] as $branch) {
        $branch->branchProductOfferings()->create([
            'product_id' => $this->product->id, 'base_price' => 20, 'currency' => 'ILS',
            'production_time_min' => 1, 'production_time_max' => 1, 'daily_capacity' => 5, 'is_active' => true,
        ]);
    }

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');

    $this->makeOrder = function (string $number, string $status, $branch = null, string $payment = 'paid') {
        $order = Order::create([
            'user_id' => $this->customer->id, 'order_number' => $number, 'status' => $status,
            'payment_status' => $payment, 'payment_method' => 'bank', 'subtotal' => 50, 'total_amount' => 50,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id,
            'item_type' => 'customer_upload', 'print_provider_branch_id' => ($branch ?? $this->branch)->id,
            'branch_product_offering_id' => ($branch ?? $this->branch)->branchProductOfferings()->first()->id,
            'quantity' => 2, 'unit_price' => 25, 'total_price' => 50, 'provider_cost' => 20,
            'selected_options' => ['paper_size' => 'A4', 'color_mode' => 'bw'],
        ]);

        return [$order, $item];
    };
});

test('the page lists only paid orders routed to this shop, from the database', function () {
    ($this->makeOrder)('MINE-1', 'processing');
    ($this->makeOrder)('THEIRS-1', 'processing', $this->otherBranch);
    ($this->makeOrder)('UNPAID-1', 'awaiting_payment_review', null, 'pending_review');

    $this->actingAs($this->owner)
        ->get(route('print-provider.requests'))
        ->assertOk()
        ->assertSee('MINE-1')
        ->assertDontSee('THEIRS-1')
        ->assertDontSee('UNPAID-1');
});

test('an order the admin just approved is new for the shop, which can accept it and then mark it ready', function () {
    [$order] = ($this->makeOrder)('FLOW-1', 'processing');

    $this->actingAs($this->owner)->get(route('print-provider.requests'))
        ->assertViewHas('counts', fn ($counts) => $counts['new'] === 1 && $counts['progress'] === 0);

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertOk();
    expect($order->fresh()->status)->toBe('confirmed');

    // accepting twice is refused, and it cannot be marked ready before it is accepted
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertStatus(422);

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.ready', $order))->assertOk();
    expect($order->fresh()->status)->toBe('ready');

    $this->assertDatabaseHas('order_status_history', ['order_id' => $order->id, 'from_status' => 'processing', 'to_status' => 'confirmed', 'changed_by' => $this->owner->id]);
    expect(Notification::where('user_id', $this->customer->id)->where('type', 'order.confirmed')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.ready')->exists())->toBeTrue();
});

test('rejecting needs a reason, tells the customer and the admins, and the order leaves the new tab', function () {
    [$order] = ($this->makeOrder)('REJ-1', 'processing');

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.reject', $order), [])->assertUnprocessable();
    expect($order->fresh()->status)->toBe('processing');

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.reject', $order), ['reason' => 'لا يتوفر ورق'])->assertOk();

    expect($order->fresh()->status)->toBe('rejected')
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.rejected')->first()->message)->toContain('لا يتوفر ورق')
        ->and(Notification::where('user_id', $this->admin->id)->where('type', 'order.rejected_by_provider')->first()->message)->toContain('مطبعة النور', 'REJ-1', 'لا يتوفر ورق');

    // a rejected order cannot be accepted afterwards
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertStatus(422);
});

test('a shop cannot act on another shops order', function () {
    [$order] = ($this->makeOrder)('THEIRS-2', 'processing', $this->otherBranch);

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertNotFound();
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.reject', $order), ['reason' => 'xxx'])->assertNotFound();
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.ready', $order))->assertNotFound();
    expect($order->fresh()->status)->toBe('processing');
});

test('an order whose payment is not approved is hidden from the shop and cannot be accepted', function () {
    [$order] = ($this->makeOrder)('UNPAID-2', 'awaiting_payment_review', null, 'pending_review');

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertNotFound();
});

test('customers and designers cannot use the shop orders routes', function () {
    [$order] = ($this->makeOrder)('ROLE-1', 'processing');

    $this->actingAs($this->customer)->get(route('print-provider.requests'))->assertForbidden();
    $this->actingAs($this->customer)->postJson(route('print-provider.requests.accept', $order))->assertForbidden();
});

test('the shop downloads the customer files of its own orders only', function () {
    [$order, $item] = ($this->makeOrder)('FILE-1', 'processing');
    Storage::disk('local')->put('customer-print-files/demo/a.pdf', '%PDF-1.4 test');
    $file = PrintFile::create([
        'user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $item->id,
        'original_name' => 'عقد.pdf', 'stored_path' => 'customer-print-files/demo/a.pdf', 'disk' => 'local',
        'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 13, 'status' => 'attached_to_order',
    ]);

    $this->actingAs($this->owner)
        ->get(route('print-provider.requests.files', [$order, $file]))
        ->assertOk()
        ->assertHeader('content-disposition', "attachment; filename=aakd.pdf; filename*=utf-8''".rawurlencode('عقد.pdf'));

    $this->actingAs($this->otherOwner)->get(route('print-provider.requests.files', [$order, $file]))->assertNotFound();

    // a file of another order cannot be fetched through this order's URL
    [$otherOrder] = ($this->makeOrder)('FILE-2', 'processing');
    $this->actingAs($this->owner)->get(route('print-provider.requests.files', [$otherOrder, $file]))->assertNotFound();
});

test('payment approval by the admin sends the order to the shop as a new order', function () {
    $order = Order::create([
        'user_id' => $this->customer->id, 'order_number' => 'APPROVE-1', 'status' => 'awaiting_payment_review',
        'payment_status' => 'pending_review', 'payment_method' => 'bank', 'subtotal' => 50, 'total_amount' => 50,
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id,
        'item_type' => 'customer_upload', 'print_provider_branch_id' => $this->otherBranch->id,
        'branch_product_offering_id' => $this->otherBranch->branchProductOfferings()->first()->id,
        'quantity' => 1, 'unit_price' => 25, 'total_price' => 25, 'provider_cost' => 20,
    ]);
    $payment = Payment::create([
        'order_id' => $order->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-A1',
        'status' => 'pending_review', 'method' => 'bank', 'amount' => 50, 'gateway_payload' => ['mode' => 'manual_receipt_review'],
    ]);

    $this->actingAs($this->owner)->get(route('print-provider.requests'))->assertDontSee('APPROVE-1');

    $this->actingAs($this->admin)
        ->post(route('admin.payment-notices.approve', $payment), ['branch_id' => $this->branch->id])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->owner)->get(route('print-provider.requests'))
        ->assertOk()
        ->assertSee('APPROVE-1')
        ->assertViewHas('orders', fn ($orders) => $orders->firstWhere('number', 'APPROVE-1')['actions']['accept'] === true);

    // the shop that was not chosen sees nothing
    $this->actingAs($this->otherOwner)->get(route('print-provider.requests'))->assertDontSee('APPROVE-1');
});
