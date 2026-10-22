<?php

use App\Models\BranchProductOffering;
use App\Models\Category;
use App\Models\Design;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintFile;
use App\Models\PrintingMethod;
use App\Models\PrintProvider;
use App\Models\PrintProviderBranch;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\StickerLibraryItem;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\DemoShopsSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');

    // The real shop whose settings the demo shops copy: four products, each with one variant, one area and one price.
    $category = Category::create(['name' => 'منتجات', 'slug' => 'all']);
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $this->source = $owner->printProvider()->create(['company_name' => 'نور', 'approval_status' => 'approved', 'is_active' => true, 'phone' => '0599'])->primaryBranch();
    $this->source->update(['city' => 'غزة']);

    foreach (['TSHIRT-CLASSIC', 'HOODIE-PREMIUM', 'MUG-CERAMIC', 'STICKER-CUSTOM'] as $code) {
        $product = Product::create(['category_id' => $category->id, 'name' => $code, 'code' => $code, 'is_active' => true]);
        $variant = Variant::create(['product_id' => $product->id, 'sku' => $code.'-WHITE', 'is_active' => true]);
        $offering = $this->source->branchProductOfferings()->create([
            'product_id' => $product->id, 'base_price' => 20, 'currency' => 'ILS',
            'production_time_min' => 3, 'production_time_max' => 3, 'daily_capacity' => 10, 'is_active' => true,
        ]);
        $offering->branchOfferingVariants()->create(['variant_id' => $variant->id, 'is_available' => true]);
        $area = $offering->branchPrintAreas()->create(['code' => 'front', 'name' => 'أمامي', 'max_width_mm' => 300, 'max_height_mm' => 400, 'is_active' => true]);
        $method = PrintingMethod::firstOrCreate(['code' => 'dtg'], ['name' => 'DTG', 'is_active' => true]);
        $capability = $area->branchPrintCapabilities()->create(['printing_method_id' => $method->id, 'applies_to_all_variants' => true, 'max_width_mm' => 300, 'max_height_mm' => 400, 'is_active' => true]);
        $offering->branchPricingRules()->create(['branch_print_capability_id' => $capability->id, 'min_quantity' => 1, 'pricing_type' => 'print', 'value_type' => 'fixed', 'amount' => 8, 'priority' => 10, 'is_active' => true]);
    }
});

function demoShop(int $number): PrintProviderBranch
{
    return User::where('email', 'demo.shop'.$number.'@palprints.test')->firstOrFail()->printProvider->primaryBranch();
}

/** @return list<string> the product codes the branch sells */
function demoShopProducts(PrintProviderBranch $branch): array
{
    return $branch->branchProductOfferings()->where('is_active', true)->with('product')->get()->pluck('product.code')->sort()->values()->all();
}

test('the demo seeder adds shops in Gaza, Khan Younis, Deir al-Balah and the West Bank, each selling its own products at its own prices', function () {
    (new DemoShopsSeeder)->run();

    $branches = User::where('email', 'like', DemoShopsSeeder::SHOP_EMAIL)->get()->map(fn ($user) => $user->printProvider->primaryBranch());
    expect($branches)->toHaveCount(9)
        ->and($branches->pluck('city')->unique()->sort()->values()->all())->toBe(['الخليل', 'بيت لحم', 'جنين', 'خانيونس', 'دير البلح', 'رام الله', 'غزة', 'نابلس'])
        ->and($branches->pluck('region')->unique()->sort()->values()->all())->toBe(['الضفة الغربية', 'قطاع غزة']);

    expect(demoShopProducts(demoShop(2)))->toBe(['HOODIE-PREMIUM', 'TSHIRT-CLASSIC']) // Hebron: clothes only
        ->and(demoShopProducts(demoShop(3)))->toBe(['MUG-CERAMIC', 'STICKER-CUSTOM']) // Bethlehem: no clothes
        ->and(demoShopProducts(demoShop(7)))->toBe(['STICKER-CUSTOM']); // Shujaiya: paper and stickers

    // The Nablus shop sells at 0.9 of the real shop's price, Ramallah at 1.15.
    $price = fn (int $shop) => (float) demoShop($shop)->branchProductOfferings()->whereHas('product', fn ($q) => $q->where('code', 'TSHIRT-CLASSIC'))->value('base_price');
    expect([$price(1), $price(4)])->toBe([18.0, 23.0]);

    $copy = demoShop(1)->branchProductOfferings()->first();
    expect($copy->branchOfferingVariants()->count())->toBe(1)
        ->and($copy->branchPrintAreas()->first()->branchPrintCapabilities()->count())->toBe(1)
        ->and($copy->branchPricingRules()->count())->toBe(1);

    expect(Order::where('order_number', 'like', 'DEMO-%')->pluck('status')->unique()->sort()->values()->all())
        ->toBe(['awaiting_payment_review', 'cancelled', 'confirmed', 'delivered', 'in_production', 'processing', 'ready', 'rejected', 'shipped']);
});

test('designers with designs, a sticker library and orders of designs, own artwork and sticker sheets come with it', function () {
    (new DemoShopsSeeder)->run();

    $designers = User::where('email', 'like', DemoShopsSeeder::DESIGNER_EMAIL)->get();
    expect($designers)->toHaveCount(4)
        ->and($designers->every(fn ($designer) => $designer->designerProfile?->approval_status === 'approved'))->toBeTrue();

    $designs = Design::whereIn('designer_id', $designers->pluck('id'))->get();
    expect($designs)->toHaveCount(12)
        ->and($designs->where('status', 'published'))->toHaveCount(9)
        ->and($designs->every(fn ($design) => is_file(public_path($design->image))))->toBeTrue()
        ->and($designs->every(fn ($design) => Storage::disk('local')->exists($design->design_payload['print_exports'][0]['path'])))->toBeTrue();

    $stickers = StickerLibraryItem::where('image_path', 'like', DemoShopsSeeder::STICKER_PREFIX.'%')->get();
    expect($stickers)->toHaveCount(14)
        ->and($stickers->every(fn ($sticker) => Storage::disk('public')->exists($sticker->image_path)))->toBeTrue();

    $items = OrderItem::whereHas('order', fn ($q) => $q->where('order_number', 'like', 'DEMO-%'))->get();
    expect($items->pluck('item_type')->unique()->sort()->values()->all())->toBe(['catalog_design', 'customer_upload', 'sticker_bundle']);

    // The shop has a file to print for every design and own-artwork line, and the sticker lines with their layout.
    $printed = $items->where('item_type', '!=', 'sticker_bundle');
    expect($printed->every(fn ($item) => PrintFile::where('order_item_id', $item->id)->get()->every(fn ($file) => Storage::disk('local')->exists($file->stored_path))
        && PrintFile::where('order_item_id', $item->id)->exists()))->toBeTrue();
    $sheets = $items->where('item_type', 'sticker_bundle');
    expect($sheets->every(fn ($item) => $item->stickerBundleLines()->exists() && ! empty($item->selected_options['layout_snapshot']['pages'])))->toBeTrue();

    $delivered = Order::where('order_number', 'like', 'DEMO-%')->where('status', 'delivered')->pluck('id');
    expect(Shipment::whereIn('order_id', $delivered)->count())->toBe(3)
        ->and(Review::whereIn('order_id', $delivered)->count())->toBe(3);

    // Delivered design orders are money the designer can withdraw; the ones still on their way are pending.
    $rana = User::where('email', 'demo.designer1@palprints.test')->first();
    expect((float) $rana->wallet->available_balance)->toBeGreaterThan(0)
        ->and((float) $rana->wallet->pending_balance)->toBeGreaterThan(0);
});

test('running it again adds nothing', function () {
    (new DemoShopsSeeder)->run();
    $counts = fn () => [Order::count(), OrderItem::count(), User::count(), BranchProductOffering::count(), Design::count(), StickerLibraryItem::count(), PrintFile::count(), Notification::count()];
    $before = $counts();

    (new DemoShopsSeeder)->run();

    expect($counts())->toBe($before);
});

test('the pages of every kind of account open with the demo data', function () {
    (new DemoShopsSeeder)->run();
    $account = fn (string $email) => User::where('email', $email)->firstOrFail();

    $this->actingAs($this->admin)->get(route('admin.orders'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.payment-notices'))->assertOk()->assertSee('DEMO-1001');
    $this->actingAs($this->admin)->get(route('admin.designs'))->assertOk()->assertSee('غزة في القلب');
    $this->actingAs($this->admin)->get(route('admin.stickers'))->assertOk();

    // Mohammad (Gaza) has orders at the Rimal shop: a ready one with a design and a sticker sheet.
    $this->actingAs($account('demo.shop6@palprints.test'))->get(route('print-provider.requests'))->assertOk()->assertSee('DEMO-1007');
    $this->actingAs($account('demo.shop6@palprints.test'))->get(route('print-provider.dashboard'))->assertOk();
    $this->actingAs($account('demo.customer3@palprints.test'))->get(route('customer.orders'))->assertOk()->assertSee('DEMO-1007');
    $this->actingAs($account('demo.customer3@palprints.test'))->get(route('customer.favorites'))->assertOk();
    $this->actingAs($account('demo.designer1@palprints.test'))->get(route('designer.dashboard'))->assertOk();
    $this->actingAs($account('demo.designer1@palprints.test'))->get(route('designer.designs.index'))->assertOk()->assertSee('بطيخة فلسطين');
    $this->actingAs($account('demo.customer3@palprints.test'))->get(route('customer.tshirts'))->assertOk()->assertSee(json_encode('غزة في القلب'), false);
});

test('the refused orders show the admin the shops that can still make them', function () {
    (new DemoShopsSeeder)->run();

    $orders = $this->actingAs($this->admin)->get(route('admin.orders'))->assertOk()->getContent();
    preg_match_all('/data-reroute="([^"]+)"/', $orders, $matches);
    $choices = collect($matches[1])->map(fn ($json) => html_entity_decode($json));

    expect($choices)->toHaveCount(2);
    // DEMO-1013 (a t-shirt) was refused by Nablus and Jenin: neither is offered again, Gaza's Rimal shop is.
    expect($choices->contains(fn ($json) => ! str_contains($json, 'مطبعة جبل النار') && ! str_contains($json, 'مطبعة جنين للإعلان') && str_contains($json, 'مطبعة الرمال')))->toBeTrue();
    expect(Notification::where('user_id', $this->admin->id)->where('type', 'order.rejected_by_provider')->count())->toBe(2);
});

test('the cleanup removes exactly the demo data and keeps the real shop and its orders', function () {
    $real = Order::create(['user_id' => User::factory()->create()->id, 'order_number' => 'PP-REAL-1', 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 10, 'total_amount' => 10]);
    $otherDemo = Order::create(['user_id' => User::factory()->create()->id, 'order_number' => 'DEMO-ORD-1001', 'status' => 'shipped', 'payment_status' => 'paid', 'payment_method' => 'demo_card', 'subtotal' => 10, 'total_amount' => 10]);
    (new DemoShopsSeeder)->run();

    $removed = DemoShopsSeeder::clean();

    expect($removed['orders'])->toBe(18)
        ->and(Order::where('order_number', 'like', 'DEMO-1%')->count())->toBe(0)
        ->and(Order::find($otherDemo->id))->not->toBeNull()
        ->and(User::where('email', 'like', 'demo.%@palprints.test')->count())->toBe(0)
        ->and(PrintProviderBranch::count())->toBe(1)
        ->and(Order::find($real->id))->not->toBeNull()
        ->and($this->source->fresh()->branchProductOfferings()->count())->toBe(4)
        ->and(Design::count())->toBe(0)
        ->and(StickerLibraryItem::count())->toBe(0)
        ->and(PrintFile::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(Notification::where('message', 'like', '%DEMO-1%')->count())->toBe(0);
});

test('with no approved real shop to copy it stops with a clear message instead of making half of the data', function () {
    PrintProvider::query()->update(['approval_status' => 'draft']);

    expect(fn () => (new DemoShopsSeeder)->run())->toThrow(RuntimeException::class, 'no approved print shop');
    expect(User::where('email', 'like', 'demo.%@palprints.test')->count())->toBe(0);
});
