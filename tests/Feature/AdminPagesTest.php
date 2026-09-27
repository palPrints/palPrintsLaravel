<?php

use App\Models\Category;
use App\Models\Design;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function makeAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function makeProduct(array $overrides = []): Product
{
    $category = Category::firstOrCreate(['slug' => 'apparel'], ['name' => 'ملابس', 'is_active' => true]);

    return Product::create($overrides + [
        'category_id' => $category->id,
        'name' => 'تيشيرت',
        'code' => 'TSHIRT-1',
        'is_active' => true,
    ]);
}

function makeDesign(string $status = 'review'): Design
{
    $designer = User::factory()->create(['name' => 'مصممة الاختبار']);
    $designer->assignRole('designer');

    return Design::create([
        'designer_id' => $designer->id,
        'product_id' => makeProduct(['code' => 'P-'.uniqid()])->id,
        'title' => 'تصميم الاختبار',
        'status' => $status,
    ]);
}

function makePendingWithdrawal(float $amount = 40): WithdrawalRequest
{
    $designer = User::factory()->create();
    $designer->assignRole('designer');
    $wallet = Wallet::create(['user_id' => $designer->id, 'total_balance' => 100, 'available_balance' => 100, 'pending_balance' => 0, 'total_withdrawn' => 0]);

    return app(WithdrawalService::class)->request($designer, $amount, 'bank');
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('every admin page renders and is limited to admins', function (string $routeName) {
    $this->actingAs(makeAdmin())->get(route($routeName))->assertOk();

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    auth()->logout();

    $this->actingAs($customer)->get(route($routeName))->assertForbidden();
})->with(['admin.designs', 'admin.products', 'admin.orders', 'admin.payments']);

test('admin approves a design and the designer is notified', function () {
    $design = makeDesign();

    $this->actingAs(makeAdmin())
        ->postJson(route('admin.designs.review', $design), ['action' => 'approve'])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($design->fresh()->status)->toBe('published')
        ->and($design->fresh()->published_at)->not->toBeNull();

    expect(Notification::where('user_id', $design->designer_id)->where('type', 'design_review')->exists())->toBeTrue();
});

test('admin rejects a design with a reason', function () {
    $design = makeDesign();

    $this->actingAs(makeAdmin())
        ->postJson(route('admin.designs.review', $design), ['action' => 'reject', 'reason' => 'الجودة منخفضة'])
        ->assertOk();

    expect($design->fresh()->status)->toBe('rejected')
        ->and($design->fresh()->rejection_reason)->toBe('الجودة منخفضة');
});

test('a design that is not waiting for review cannot be reviewed again', function () {
    $design = makeDesign('published');

    $this->actingAs(makeAdmin())
        ->postJson(route('admin.designs.review', $design), ['action' => 'reject'])
        ->assertStatus(422);

    expect($design->fresh()->status)->toBe('published');
});

test('approving a withdrawal moves the pending amount out of the wallet', function () {
    $withdrawal = makePendingWithdrawal(40);

    $this->actingAs(makeAdmin())
        ->postJson(route('admin.payments.withdrawals.review', $withdrawal), ['action' => 'approve'])
        ->assertOk();

    $wallet = $withdrawal->wallet->fresh();

    expect($withdrawal->fresh()->status)->toBe('approved')
        ->and((float) $wallet->available_balance)->toBe(60.0)
        ->and((float) $wallet->pending_balance)->toBe(0.0)
        ->and((float) $wallet->total_withdrawn)->toBe(40.0);

    expect(WalletTransaction::where('reference_id', $withdrawal->reference_id)->value('status'))->toBe('complete');
});

test('rejecting a withdrawal returns the amount to the available balance', function () {
    $withdrawal = makePendingWithdrawal(40);

    $this->actingAs(makeAdmin())
        ->postJson(route('admin.payments.withdrawals.review', $withdrawal), ['action' => 'reject', 'reason' => 'بيانات ناقصة'])
        ->assertOk();

    $wallet = $withdrawal->wallet->fresh();

    expect($withdrawal->fresh()->status)->toBe('rejected')
        ->and((float) $wallet->available_balance)->toBe(100.0)
        ->and((float) $wallet->pending_balance)->toBe(0.0)
        ->and((float) $wallet->total_withdrawn)->toBe(0.0);
});

test('a withdrawal cannot be reviewed twice', function () {
    $withdrawal = makePendingWithdrawal(40);
    $admin = makeAdmin();

    $this->actingAs($admin)->postJson(route('admin.payments.withdrawals.review', $withdrawal), ['action' => 'approve'])->assertOk();
    $this->actingAs($admin)->postJson(route('admin.payments.withdrawals.review', $withdrawal), ['action' => 'reject'])->assertStatus(422);

    expect((float) $withdrawal->wallet->fresh()->available_balance)->toBe(60.0);
});

test('admin can add, update, pause and delete a product', function () {
    Storage::fake('public');
    $admin = makeAdmin();
    $category = Category::create(['name' => 'ملابس', 'slug' => 'apparel', 'is_active' => true]);

    $this->actingAs($admin)->postJson(route('admin.products.store'), [
        'name' => 'هودي',
        'code' => 'HOODIE-9',
        'category_id' => $category->id,
        'image' => UploadedFile::fake()->create('hoodie.png', 20, 'image/png'),
    ])->assertCreated();

    $product = Product::where('code', 'HOODIE-9')->firstOrFail();
    expect($product->image)->toStartWith('storage/products/');
    Storage::disk('public')->assertExists(substr($product->image, strlen('storage/')));

    $this->actingAs($admin)->patchJson(route('admin.products.update', $product), [
        'name' => 'هودي شتوي',
        'code' => 'HOODIE-9',
        'category_id' => $category->id,
    ])->assertOk();
    expect($product->fresh()->name)->toBe('هودي شتوي');

    $this->actingAs($admin)->patchJson(route('admin.products.toggle', $product))->assertOk()->assertJson(['active' => false]);
    expect($product->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->deleteJson(route('admin.products.destroy', $product))->assertOk();
    expect(Product::whereKey($product->id)->exists())->toBeFalse();
});

test('product code must be unique and an image is required for new products', function () {
    $admin = makeAdmin();
    $existing = makeProduct(['code' => 'DUP-1']);

    $this->actingAs($admin)->postJson(route('admin.products.store'), [
        'name' => 'منتج',
        'code' => 'DUP-1',
        'category_id' => $existing->category_id,
    ])->assertStatus(422)->assertJsonValidationErrors(['code', 'image']);
});

test('the orders page shows real orders and admin can update their status', function () {
    Schema::create('orders', function ($table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('order_number');
        $table->decimal('final_amount', 14, 2)->default(0);
        $table->string('status')->default('pending');
        $table->string('payment_status')->default('unpaid');
        $table->string('payment_method')->nullable();
        $table->unsignedBigInteger('print_provider_id')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    $customer = User::factory()->create(['name' => 'عميلة تجريبية']);
    $id = DB::table('orders')->insertGetId([
        'user_id' => $customer->id,
        'order_number' => 'ORD-7001',
        'final_amount' => 85,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $admin = makeAdmin();

    $this->actingAs($admin)->get(route('admin.orders'))
        ->assertOk()
        ->assertSee('ORD-7001')
        ->assertSee('عميلة تجريبية')
        ->assertSee('85.00 ₪');

    $this->actingAs($admin)->patchJson(route('admin.orders.update', $id), ['status' => 'shipped'])->assertOk();

    expect(DB::table('orders')->where('id', $id)->value('status'))->toBe('shipped');

    $this->actingAs($admin)->patchJson(route('admin.orders.update', $id), ['status' => 'nonsense'])->assertStatus(422);
});
