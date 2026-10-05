<?php

use App\Models\Category;
use App\Models\Design;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\RoleAndPermissionSeeder;

function noticeShop(string $name, Product $product, bool $offersProduct = true): \App\Models\PrintProviderBranch
{
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $provider = $owner->printProvider()->create(['company_name' => $name, 'approval_status' => 'approved', 'is_active' => true]);
    $branch = $provider->primaryBranch();

    if ($offersProduct) {
        $branch->branchProductOfferings()->create([
            'product_id' => $product->id, 'base_price' => 20, 'currency' => 'ILS',
            'production_time_min' => 2, 'production_time_max' => 2, 'daily_capacity' => 10, 'is_active' => true,
        ]);
    }

    return $branch;
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');

    $category = Category::create(['name' => 'ملابس', 'slug' => 'clothes']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'تيشيرت', 'code' => 'TSHIRT-CLASSIC', 'is_active' => true]);
    $variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'TS-1', 'is_active' => true]);

    $this->suggested = noticeShop('مطبعة النور', $this->product);
    $this->other = noticeShop('مطبعة الأمل', $this->product);
    $this->cannot = noticeShop('مطبعة بدون تيشيرت', $this->product, false);

    $this->customer = User::factory()->create(['name' => 'سارة', 'is_active' => true]);
    $this->customer->assignRole('customer');
    $designer = User::factory()->create();

    $design = Design::create([
        'product_id' => $this->product->id, 'designer_id' => $designer->id, 'title' => 'تصميم',
        'status' => 'published', 'base_price' => 25, 'selling_price' => 30, 'published_at' => now(),
    ]);

    $this->order = Order::create([
        'user_id' => $this->customer->id, 'order_number' => 'PP-1001', 'status' => 'awaiting_payment_review',
        'payment_status' => 'pending_review', 'payment_method' => 'bank', 'subtotal' => 30, 'total_amount' => 30,
    ]);
    $this->item = OrderItem::create([
        'order_id' => $this->order->id, 'product_id' => $this->product->id, 'variant_id' => $variant->id,
        'design_id' => $design->id, 'designer_id' => $designer->id,
        'print_provider_branch_id' => $this->suggested->id,
        'branch_product_offering_id' => $this->suggested->branchProductOfferings()->first()->id,
        'quantity' => 1, 'unit_price' => 30, 'total_price' => 30,
    ]);
    $this->payment = Payment::create([
        'order_id' => $this->order->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-1001',
        'status' => 'pending_review', 'method' => 'bank', 'amount' => 30,
        'gateway_payload' => ['mode' => 'manual_receipt_review', 'receipt_original_name' => 'receipt-99887766.png'],
    ]);
});

test('the admin sees the pending notice with only the shops that offer the order products', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.payment-notices'))
        ->assertOk()
        ->assertSee('PP-1001')
        ->assertSee('receipt-99887766.png')
        ->assertSee('مطبعة النور — مقترحة')
        ->assertSee('مطبعة الأمل')
        ->assertDontSee('مطبعة بدون تيشيرت');
});

test('a customer cannot open or decide on payment notices', function () {
    $this->actingAs($this->customer)->get(route('admin.payment-notices'))->assertForbidden();
    $this->actingAs($this->customer)->post(route('admin.payment-notices.approve', $this->payment), ['branch_id' => $this->other->id])->assertForbidden();
});

test('approving marks the payment paid, routes the order to the chosen shop and tells the customer', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.payment-notices.approve', $this->payment), ['branch_id' => $this->other->id])
        ->assertRedirect()
        ->assertSessionHas('notice_status');

    expect($this->payment->fresh()->status)->toBe('paid')
        ->and($this->payment->fresh()->paid_at)->not->toBeNull()
        ->and($this->order->fresh()->payment_status)->toBe('paid')
        ->and($this->order->fresh()->status)->toBe('processing')
        ->and($this->item->fresh()->print_provider_branch_id)->toBe($this->other->id)
        ->and($this->item->fresh()->branch_product_offering_id)->toBe($this->other->branchProductOfferings()->first()->id)
        ->and($this->order->statusHistory()->count())->toBe(1)
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.payment_approved')->exists())->toBeTrue()
        // The chosen shop is told about the new order; the other shops are not.
        ->and(Notification::where('user_id', $this->other->printProvider->user_id)->where('type', 'order.new_for_provider')->count())->toBe(1)
        ->and(Notification::where('user_id', $this->suggested->printProvider->user_id)->where('type', 'order.new_for_provider')->exists())->toBeFalse();
});

test('approving needs a shop that offers every product of the order', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.payment-notices.approve', $this->payment), ['branch_id' => $this->cannot->id])
        ->assertSessionHasErrors('branch_id');

    expect($this->payment->fresh()->status)->toBe('pending_review')
        ->and($this->item->fresh()->print_provider_branch_id)->toBe($this->suggested->id);
});

test('rejecting cancels the order, keeps the reason and tells the customer', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.payment-notices.reject', $this->payment), ['reason' => 'المبلغ لا يطابق قيمة الطلب'])
        ->assertRedirect();

    expect($this->payment->fresh()->status)->toBe('failed')
        ->and($this->payment->fresh()->failure_reason)->toBe('المبلغ لا يطابق قيمة الطلب')
        ->and($this->order->fresh()->status)->toBe('cancelled')
        ->and($this->order->fresh()->payment_status)->toBe('failed')
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.payment_rejected')->exists())->toBeTrue();

    $this->actingAs($this->admin)->post(route('admin.payment-notices.reject', $this->payment), [])->assertRedirect()->assertSessionHas('notice_error');
});

test('a notice that was already decided cannot be decided again', function () {
    $this->actingAs($this->admin)->post(route('admin.payment-notices.approve', $this->payment), ['branch_id' => $this->other->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.payment-notices.reject', $this->payment), ['reason' => 'تغيير رأي'])
        ->assertSessionHas('notice_error');

    expect($this->payment->fresh()->status)->toBe('paid')->and($this->order->fresh()->status)->toBe('processing');
});

test('the shop does not see the order until the admin approves its payment notice', function () {
    $owner = $this->suggested->printProvider->user;

    $this->actingAs($owner)->get(route('print-provider.dashboard'))->assertOk()->assertDontSee('PP-1001');

    $this->actingAs($this->admin)->post(route('admin.payment-notices.approve', $this->payment), ['branch_id' => $this->suggested->id]);

    $this->actingAs($owner)->get(route('print-provider.dashboard'))->assertOk()->assertSee('PP-1001');
});
