<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Variant;
use App\Models\Wallet;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(RoleAndPermissionSeeder::class);

    $category = Category::create(['name' => 'أكواب', 'slug' => 'mugs']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'كوب', 'code' => 'MUG-CERAMIC', 'is_active' => true]);
    $this->variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'MUG-1', 'is_active' => true]);
});

function member(string $role): User
{
    $user = User::factory()->create(['is_active' => true, 'password' => 'secret-pass-1']);
    $user->assignRole($role);

    return $user;
}

function deleteAccount(User $user)
{
    return test()->actingAs($user)->delete('/profile', ['password' => 'secret-pass-1']);
}

function makeOrder(User $customer, string $status, ?int $branchId = null, ?int $designId = null, ?int $designerId = null): Order
{
    $order = Order::create(['user_id' => $customer->id, 'order_number' => 'PP-'.random_int(1000, 99999), 'status' => $status, 'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 25, 'total_amount' => 25]);
    $owner = member('print_provider');
    $branch = $branchId ? null : $owner->printProvider()->create(['company_name' => 'مطبعة', 'approval_status' => 'approved', 'is_active' => true])->primaryBranch();
    $offering = ($branch ?? \App\Models\PrintProviderBranch::find($branchId))->branchProductOfferings()->create([
        'product_id' => test()->product->id, 'base_price' => 10, 'currency' => 'ILS',
        'production_time_min' => 2, 'production_time_max' => 2, 'daily_capacity' => 10, 'is_active' => true,
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'product_id' => test()->product->id, 'variant_id' => test()->variant->id, 'design_id' => $designId, 'designer_id' => $designerId,
        'print_provider_branch_id' => $branchId ?? $branch->id, 'branch_product_offering_id' => $offering->id, 'quantity' => 1, 'unit_price' => 25, 'total_price' => 25,
    ]);

    return $order;
}

test('a customer with no history is deleted completely', function () {
    $customer = member('customer');
    SocialAccount::create(['user_id' => $customer->id, 'provider' => 'google', 'provider_user_id' => 'g-1', 'provider_email' => $customer->email]);

    deleteAccount($customer)->assertRedirect('/');

    expect(User::find($customer->id))->toBeNull()->and(SocialAccount::count())->toBe(0);
    $this->assertGuest();
});

test('a customer who has finished orders is closed, not failed with an error, and the orders stay', function () {
    $customer = member('customer');
    $order = makeOrder($customer, 'delivered');
    $email = $customer->email;
    Storage::disk('public')->put('customer/avatars/me.png', 'x');
    $customer->update(['avatar_path' => 'customer/avatars/me.png', 'phone' => '0599000000']);

    deleteAccount($customer)->assertRedirect('/');

    $closed = $customer->fresh();
    expect($closed)->not->toBeNull()
        ->and($closed->name)->toBe('حساب محذوف')
        ->and($closed->email)->not->toBe($email)->and($closed->email)->toEndWith('@deleted.invalid')
        ->and($closed->is_active)->toBeFalse()
        ->and($closed->phone)->toBeNull()
        ->and($closed->avatar_path)->toBeNull()
        ->and(Order::find($order->id))->not->toBeNull();
    Storage::disk('public')->assertMissing('customer/avatars/me.png');
    $this->assertGuest();

    // The old email and password no longer open anything.
    $this->post('/login', ['email' => $email, 'password' => 'secret-pass-1'])->assertSessionHasErrors();
    $this->assertGuest();
});

test('a customer with a running order is told why and keeps the account', function () {
    $customer = member('customer');
    makeOrder($customer, 'processing');

    deleteAccount($customer)->assertSessionHasErrorsIn('userDeletion', 'password');

    expect($customer->fresh()->is_active)->toBeTrue()->and($customer->fresh()->name)->not->toBe('حساب محذوف');
    $this->assertAuthenticatedAs($customer);
});

test('money in the wallet or a pending withdrawal blocks the deletion', function () {
    $designer = member('designer');
    Wallet::create(['user_id' => $designer->id, 'available_balance' => 40, 'pending_balance' => 0, 'total_earned' => 40, 'total_withdrawn' => 0]);

    deleteAccount($designer)->assertSessionHasErrorsIn('userDeletion', 'password');

    expect(User::find($designer->id))->not->toBeNull();
});

test('an empty wallet does not block the deletion', function () {
    $designer = member('designer');
    $designer->designerProfile()->create(['full_name' => $designer->name, 'approval_status' => 'approved']);
    Wallet::create(['user_id' => $designer->id, 'available_balance' => 0, 'pending_balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0]);

    deleteAccount($designer)->assertRedirect('/');

    expect(User::find($designer->id))->toBeNull();
});

test('the admin account cannot be deleted from the profile', function () {
    $admin = member('admin');

    deleteAccount($admin)->assertSessionHasErrorsIn('userDeletion', 'password');

    expect(User::find($admin->id))->not->toBeNull();
    $this->assertAuthenticatedAs($admin);
});

test('a wrong password deletes nothing', function () {
    $customer = member('customer');

    $this->actingAs($customer)->delete('/profile', ['password' => 'not-my-password'])->assertSessionHasErrorsIn('userDeletion', 'password');

    expect(User::find($customer->id))->not->toBeNull();
});

test('a designer whose designs were ordered is closed: designs leave the store and baskets, orders keep them', function () {
    $designer = member('designer');
    $profile = $designer->designerProfile()->create(['full_name' => 'اسم المصمم', 'bio' => 'نبذة', 'approval_status' => 'approved']);
    $design = Design::create([
        'designer_id' => $designer->id, 'product_id' => $this->product->id, 'title' => 'منشور', 'description' => 'x',
        'base_price' => 10, 'selling_price' => 25, 'designer_profit' => 15, 'status' => 'published', 'published_at' => now(),
        'image' => 'storage/designs/1-a.png',
    ]);
    Storage::disk('public')->put('designs/1-a.png', 'x');
    $buyer = member('customer');
    makeOrder($buyer, 'delivered', null, $design->id, $designer->id);
    $cart = Cart::create(['user_id' => member('customer')->id, 'status' => 'active']);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id, 'design_id' => $design->id, 'quantity' => 1, 'unit_price' => 25, 'item_type' => CartItem::TYPE_CATALOG_DESIGN]);

    deleteAccount($designer)->assertRedirect('/');

    expect($designer->fresh()->is_active)->toBeFalse()
        ->and($design->fresh()->status)->toBe('draft')
        ->and(CartItem::where('design_id', $design->id)->count())->toBe(0)
        ->and(OrderItem::where('design_id', $design->id)->count())->toBe(1)
        ->and($profile->fresh()->bio)->toBeNull()
        ->and($profile->fresh()->full_name)->toBe('حساب محذوف');
});

test('a print shop with finished orders is closed and stops receiving orders; one with running orders is not', function () {
    $owner = member('print_provider');
    $provider = $owner->printProvider()->create(['company_name' => 'مطبعة النور', 'phone' => '0599', 'approval_status' => 'approved', 'is_active' => true]);
    $branch = $provider->primaryBranch();
    $order = makeOrder(member('customer'), 'processing', $branch->id);

    deleteAccount($owner)->assertSessionHasErrorsIn('userDeletion', 'password');
    expect($provider->fresh()->is_active)->toBeTrue();

    $order->update(['status' => 'completed']);
    deleteAccount($owner)->assertRedirect('/');

    expect($owner->fresh()->is_active)->toBeFalse()
        ->and($provider->fresh()->is_active)->toBeFalse()
        ->and($provider->fresh()->company_name)->toBe('مطبعة محذوفة')
        ->and($provider->fresh()->phone)->toBeNull()
        ->and($branch->fresh()->is_active)->toBeFalse();
});
