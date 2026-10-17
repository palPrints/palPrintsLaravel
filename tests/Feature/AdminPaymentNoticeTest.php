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
        $offering = $branch->branchProductOfferings()->create([
            'product_id' => $product->id, 'base_price' => 20, 'currency' => 'ILS',
            'production_time_min' => 2, 'production_time_max' => 2, 'daily_capacity' => 10, 'is_active' => true,
        ]);

        // A shop only counts for an order when it offers the exact colour and size (variant) that was ordered.
        Variant::where('product_id', $product->id)->get()->each(
            fn (Variant $variant) => $offering->branchOfferingVariants()->create(['variant_id' => $variant->id, 'is_available' => true])
        );
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
        ->assertSee('مطبعة النور')
        ->assertSee('الأفضل') // the best-ranked shop carries this tag, the same one the order is routed to on approval
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
        ->assertRedirect(route('admin.payment-notices'))
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
        ->assertRedirect(route('admin.payment-notices'));

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

test('a payment notification opens that order\'s notice, including notifications saved before the order number was in the link', function () {
    $old = \App\Models\Notification::create([
        'user_id' => \App\Models\User::factory()->create()->id, 'type' => 'order.payment_review', 'title' => 'إشعار دفع جديد بانتظار المراجعة',
        'message' => 'طلب جديد رقم PLP-20261014-GHM5RP من سارة بقيمة 44.00 ₪. راجع إشعار الدفع.', 'link' => route('admin.payment-notices'),
    ]);
    $new = \App\Models\Notification::create([
        'user_id' => $old->user_id, 'type' => 'order.payment_review', 'title' => 'x', 'message' => 'طلب جديد رقم PLP-1', 'link' => route('admin.payment-notices', ['order' => 'PLP-1']),
    ]);
    $other = \App\Models\Notification::create(['user_id' => $old->user_id, 'type' => 'support.new', 'title' => 'x', 'message' => 'm', 'link' => route('admin.support')]);

    expect(\App\Support\AdminNotifier::linkFor($old))->toBe(route('admin.payment-notices', ['order' => 'PLP-20261014-GHM5RP']))
        ->and(\App\Support\AdminNotifier::linkFor($new))->toBe(route('admin.payment-notices', ['order' => 'PLP-1']))
        ->and(\App\Support\AdminNotifier::linkFor($other))->toBe(route('admin.support'));
});

test('every kind of admin notification opens the item it is about, old and new', function () {
    $admin = \App\Models\User::factory()->create();
    $make = fn (string $type, string $message, string $route, string $title = 't') => \App\Models\Notification::create(['user_id' => $admin->id, 'type' => $type, 'title' => $title, 'message' => $message, 'link' => route($route)]);

    // an order cancelled / refund / turned down by a shop: that order opens
    foreach (['order.payment_cancelled', 'order.refund_needed', 'order.rejected_by_provider'] as $type) {
        expect(\App\Support\AdminNotifier::linkFor($make($type, 'أُلغي الطلب رقم PLP-77 بعد اعتماد دفعه. أرجِع المبلغ.', 'admin.orders')))->toBe(route('admin.orders', ['open' => 'PLP-77']));
    }

    // a withdrawal request: found by its reference
    $withdrawer = \App\Models\User::factory()->create();
    $wallet = \App\Models\Wallet::create(['user_id' => $withdrawer->id, 'total_balance' => 50, 'available_balance' => 50, 'pending_balance' => 0, 'total_withdrawn' => 0]);
    $withdrawal = \App\Models\WithdrawalRequest::create(['user_id' => $withdrawer->id, 'wallet_id' => $wallet->id, 'amount' => 50, 'method' => 'bank', 'status' => 'pending', 'reference_id' => 'WDR-ABC123XYZ9', 'account_details' => '-']);
    expect(\App\Support\AdminNotifier::linkFor($make('withdrawal.requested', 'طلب سارة سحب 50 ₪. رقم المرجع: WDR-ABC123XYZ9', 'admin.payments')))->toBe(route('admin.payments', ['open' => $withdrawal->id]));

    // notifications that already name the item keep their link
    $named = \App\Models\Notification::create(['user_id' => $admin->id, 'type' => 'support.new', 'title' => 't', 'message' => 'm', 'link' => route('admin.support', ['open' => 'REQ-1001'])]);
    expect(\App\Support\AdminNotifier::linkFor($named))->toBe(route('admin.support', ['open' => 'REQ-1001']));
});
