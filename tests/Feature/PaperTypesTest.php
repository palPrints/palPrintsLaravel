<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\BranchPricingRule;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantValue;
use App\Support\PaperBindings;
use App\Support\PaperPrices;
use App\Support\PaperTypes;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');

    $category = Category::create(['name' => 'ورق', 'slug' => 'paper']);
    $this->category = $category;
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);
    $color = Attribute::create(['name' => 'اللون', 'code' => 'color', 'data_type' => 'text', 'is_active' => true]);
    $size = Attribute::create(['name' => 'المقاس', 'code' => 'size', 'data_type' => 'text', 'is_active' => true]);
    $white = AttributeValue::create(['attribute_id' => $color->id, 'value' => 'أبيض', 'code' => 'white', 'sort_order' => 1]);
    $colorAxis = ProductAttribute::create(['product_id' => $this->product->id, 'attribute_id' => $color->id, 'is_variant_axis' => true, 'is_required' => true, 'sort_order' => 1]);
    $sizeAxis = ProductAttribute::create(['product_id' => $this->product->id, 'attribute_id' => $size->id, 'is_variant_axis' => true, 'is_required' => true, 'sort_order' => 2]);
    $whiteValue = ProductAttributeValue::create(['product_attribute_id' => $colorAxis->id, 'attribute_value_id' => $white->id, 'is_active' => true]);

    $this->variants = [];
    foreach (['a4' => 'A4', 'a5' => 'A5'] as $code => $label) {
        $value = AttributeValue::create(['attribute_id' => $size->id, 'value' => $label, 'code' => $code, 'sort_order' => 1]);
        $variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'PP-WHITE-'.$label, 'is_active' => true]);
        $sizeValue = ProductAttributeValue::create(['product_attribute_id' => $sizeAxis->id, 'attribute_value_id' => $value->id, 'is_active' => true]);
        VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $whiteValue->id]);
        VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $sizeValue->id]);
        $this->variants[$code] = $variant;
    }

    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');

    $this->owner = User::factory()->create(['is_active' => true]);
    $this->owner->assignRole('print_provider');
    $this->owner->printProvider()->create(['company_name' => 'مطبعة النور', 'approval_status' => 'approved', 'is_active' => true]);

    $this->saveShop = fn (array $paper) => $this->actingAs($this->owner)->putJson(route('print-provider.services.save', $this->product), $paper + [
        'price' => 1, 'days' => 1, 'capacity' => 50, 'colors' => ['white'], 'sizes' => ['a4', 'a5'],
    ]);

    /** The admin saves the paper types with the paper product (its edit dialog). */
    $this->adminSaves = fn (array $types, string $code = 'PAPER-PRINT') => $this->actingAs($this->admin)->patchJson(route('admin.products.update', $this->product), [
        'name' => 'طباعة الأوراق', 'code' => $code, 'category_id' => $this->category->id, 'paper_types' => json_encode($types),
    ]);

    /** The list the admin sends: the three originals plus whatever is added. */
    $this->withTypes = fn (array $extra = []) => array_merge(
        array_map(fn ($type) => ['key' => $type['key'], 'label' => $type['label'], 'note' => $type['note'], 'active' => true], PaperTypes::defaults()),
        $extra,
    );
});

test('without a saved list the three original paper types are offered', function () {
    expect(array_keys(PaperTypes::active()))->toBe(['standard', 'thick', 'coated']);

    $this->actingAs($this->admin)->get(route('admin.products'))->assertOk()->assertViewHas('paperTypes', fn ($types) => count($types) === 3 && $types[0]['label'] === 'عادي 80 جم');
});

test('only an admin can change the paper types', function () {
    $this->actingAs($this->owner)->patchJson(route('admin.products.update', $this->product), ['name' => 'x', 'code' => 'PAPER-PRINT', 'category_id' => $this->category->id, 'paper_types' => json_encode(($this->withTypes)([['key' => '', 'label' => 'جديد', 'active' => true]]))])->assertForbidden();
    expect(array_keys(PaperTypes::active()))->toBe(['standard', 'thick', 'coated']);
});

test('the admin adds a paper type and it gets its own key', function () {
    ($this->adminSaves)(($this->withTypes)([['key' => '', 'label' => 'كرتون 200 جم', 'note' => 'للأغلفة', 'active' => true]]))->assertOk();

    expect(array_keys(PaperTypes::labels()))->toBe(['standard', 'thick', 'coated', 'p1'])
        ->and(PaperTypes::active()['p1'])->toBe('كرتون 200 جم');

    // the next one is p2, never a reused key
    ($this->adminSaves)(array_merge(PaperTypes::all(), [['key' => '', 'label' => 'ورق صور', 'note' => '', 'active' => true]]))->assertOk();
    expect(array_keys(PaperTypes::labels()))->toBe(['standard', 'thick', 'coated', 'p1', 'p2']);
});

test('a type left out of the list is hidden, not deleted, and a name cannot repeat or be empty', function () {
    ($this->adminSaves)(array_slice(($this->withTypes)(), 0, 2))->assertOk();

    expect(array_keys(PaperTypes::active()))->toBe(['standard', 'thick'])
        ->and(PaperTypes::labels())->toHaveKey('coated');

    ($this->adminSaves)([['key' => 'standard', 'label' => 'ورق', 'active' => true], ['key' => 'thick', 'label' => 'ورق', 'active' => true]])->assertUnprocessable()->assertJsonValidationErrors('paper_types');

    ($this->adminSaves)([['key' => 'standard', 'label' => '  ', 'active' => true]])->assertUnprocessable();

    ($this->adminSaves)([['key' => 'standard', 'label' => 'عادي', 'active' => false]])->assertUnprocessable()->assertJsonValidationErrors('paper_types');

    // a product that is not the paper product never touches the list
    ($this->adminSaves)([['key' => '', 'label' => 'جديد', 'active' => true]], 'OTHER-1')->assertOk();
    expect(array_keys(PaperTypes::labels()))->toBe(['standard', 'thick', 'coated']);
});

test('a shop chooses an admin-made type and writes its prices, and the customer page offers it by name', function () {
    ($this->adminSaves)(($this->withTypes)([['key' => '', 'label' => 'كرتون 200 جم', 'note' => 'للأغلفة', 'active' => true]]))->assertOk();

    $response = ($this->saveShop)([
        'paper_types' => ['p1'],
        'paper_ink' => ['p1' => ['bw' => ['count' => 10, 'price' => 5]]],
    ])->assertOk();

    // the shop sees the admin's type with its name, switched on
    $types = collect($response->json('product.paper.types'))->keyBy('key');
    expect($types['p1']['label'])->toBe('كرتون 200 جم')->and($types['p1']['enabled'])->toBeTrue();
    expect(BranchPricingRule::where('pricing_type', 'paper_ink_p1_bw')->where('is_active', true)->count())->toBe(1);

    // the customer's paper page lists it, with the admin's name and hint
    \App\Models\BranchProductOffering::query()->update(['is_active' => true]);
    $this->actingAs($this->customer)->get(route('customer.paperPrinting'))
        ->assertOk()->assertSee('كرتون 200 جم')->assertSee('للأغلفة')->assertSee('data-value="p1"', false);
});

test('a hidden type cannot be switched on by a shop and is not offered to customers', function () {
    // the shop sells thick paper while it is still on offer
    ($this->saveShop)([
        'paper_types' => ['thick'],
        'paper_ink' => ['thick' => ['bw' => ['count' => 100, 'price' => 12]]],
    ])->assertOk();

    // the admin hides thick and coated
    ($this->adminSaves)([['key' => 'standard', 'label' => 'عادي 80 جم', 'note' => '', 'active' => true]])->assertOk();

    // a shop cannot start selling coated (hidden), it is just ignored; thick, already sold, stays visible to the shop to switch off
    $response = ($this->saveShop)([
        'paper_types' => ['thick', 'coated'],
        'paper_ink' => ['thick' => ['bw' => ['count' => 100, 'price' => 12]], 'coated' => ['bw' => ['count' => 100, 'price' => 20]]],
    ])->assertOk();

    expect(BranchPricingRule::where('pricing_type', 'paper_ink_coated_bw')->count())->toBe(0);
    $types = collect($response->json('product.paper.types'))->keyBy('key');
    expect($types)->toHaveKeys(['standard', 'thick'])->not->toHaveKey('coated');

    // the customer is offered neither hidden type even though a shop still has a price for thick
    \App\Models\BranchProductOffering::query()->update(['is_active' => true]);
    $this->actingAs($this->customer)->get(route('customer.paperPrinting'))
        ->assertOk()->assertDontSee('data-value="thick"', false);
});

test('the admin keeps the bindings list in the same dialog: six originals, a new one gets b1, a left-out one is hidden, not deleted', function () {
    expect(array_keys(PaperBindings::active()))->toBe(['spiral', 'clearCover', 'glue', 'sideStaple', 'cornerStaple', 'lamination']);

    $withBindings = fn (array $bindings) => $this->actingAs($this->admin)->patchJson(route('admin.products.update', $this->product), [
        'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'category_id' => $this->category->id, 'paper_bindings' => json_encode($bindings),
    ]);

    $originals = array_map(fn ($b) => $b + ['active' => true], PaperBindings::defaults());
    $withBindings(array_merge($originals, [['key' => '', 'label' => 'تغليف بلاستيك', 'active' => true]]))->assertOk();
    expect(array_keys(PaperBindings::labels()))->toBe(['spiral', 'clearCover', 'glue', 'sideStaple', 'cornerStaple', 'lamination', 'b1'])
        ->and(PaperBindings::active()['b1'])->toBe('تغليف بلاستيك');

    // two with the same name are refused; an empty name is refused
    $withBindings([['key' => 'spiral', 'label' => 'سلك', 'active' => true], ['key' => 'glue', 'label' => 'سلك', 'active' => true]])->assertUnprocessable()->assertJsonValidationErrors('paper_bindings');
    $withBindings([['key' => 'spiral', 'label' => ' ', 'active' => true]])->assertUnprocessable();

    // leaving the others out hides them, they are not deleted
    $withBindings([['key' => 'spiral', 'label' => 'سلك حلزوني', 'active' => true]])->assertOk();
    expect(array_keys(PaperBindings::active()))->toBe(['spiral'])->and(PaperBindings::labels())->toHaveKey('b1');
});

test('a shop prices an admin-made binding and the customer page offers it by name; a hidden binding is neither offered nor switchable', function () {
    ($this->actingAs($this->admin)->patchJson(route('admin.products.update', $this->product), [
        'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'category_id' => $this->category->id,
        'paper_bindings' => json_encode(array_merge(array_map(fn ($b) => $b + ['active' => true], PaperBindings::defaults()), [['key' => '', 'label' => 'تغليف بلاستيك', 'active' => true]])),
    ]))->assertOk();

    $response = ($this->saveShop)([
        'paper_types' => ['standard'],
        'paper_ink' => ['standard' => ['bw' => ['count' => 100, 'price' => 9]]],
        'paper_bindings' => ['b1' => 3, 'spiral' => 8],
    ])->assertOk();

    $bindings = collect($response->json('product.paper.bindings'))->keyBy('key');
    expect($bindings['b1']['label'])->toBe('تغليف بلاستيك')->and($bindings['b1']['enabled'])->toBeTrue();
    expect(BranchPricingRule::where('pricing_type', 'paper_binding_b1')->where('is_active', true)->count())->toBe(1);

    \App\Models\BranchProductOffering::query()->update(['is_active' => true]);
    $this->actingAs($this->customer)->get(route('customer.paperPrinting'))->assertOk()
        ->assertViewHas('paperPrinting', fn ($page) => collect($page['bindings'])->firstWhere('id', 'b1')['label'] === 'تغليف بلاستيك' && collect($page['bindings'])->firstWhere('id', 'b1')['from'] == 3.0);

    // the admin hides every binding but spiral: the shop cannot newly switch a hidden one on, and the customer no longer sees it
    $this->actingAs($this->admin)->patchJson(route('admin.products.update', $this->product), [
        'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'category_id' => $this->category->id,
        'paper_bindings' => json_encode([['key' => 'spiral', 'label' => 'سلك حلزوني', 'active' => true]]),
    ])->assertOk();

    ($this->saveShop)([
        'paper_types' => ['standard'],
        'paper_ink' => ['standard' => ['bw' => ['count' => 100, 'price' => 9]]],
        'paper_bindings' => ['spiral' => 8, 'glue' => 5],
    ])->assertOk();
    expect(BranchPricingRule::where('pricing_type', 'paper_binding_glue')->count())->toBe(0);

    $this->actingAs($this->customer)->get(route('customer.paperPrinting'))->assertOk()
        ->assertViewHas('paperPrinting', fn ($page) => collect($page['bindings'])->pluck('id')->all() === ['spiral']);
});
