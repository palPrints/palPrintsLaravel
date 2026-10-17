<?php

use App\Models\CartItem;
use App\Models\Design;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

function publishedDesignFor(string $code, float $price = 30): Design
{
    $designer = User::factory()->create();

    return Design::create([
        'product_id' => Product::where('code', $code)->firstOrFail()->id,
        'designer_id' => $designer->id,
        'title' => 'تصميم '.$code,
        'status' => 'published',
        'base_price' => $price - 5,
        'selling_price' => $price,
        'published_at' => now(),
    ]);
}

function catalogGroup(string $color, string $size, int $quantity = 1): array
{
    return ['color_id' => $color, 'color_name' => $color, 'size_id' => $size, 'size_name' => $size, 'quantity' => $quantity, 'print_areas' => ['الأمام']];
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    // The catalog seeder gives its branches and offerings to the first print shop on file; the cart needs a shop that makes the product.
    $shop = User::factory()->create(['is_active' => true]);
    $shop->assignRole('print_provider');
    $shop->printProvider()->create(['company_name' => 'Demo Print House', 'phone' => '0599000001', 'approval_status' => 'approved', 'is_active' => true]);

    $this->seed(CatalogDemoSeeder::class);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

test('t-shirt is added with the exact variant and the server-side price', function () {
    $design = publishedDesignFor('TSHIRT-CLASSIC', 30);

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
        'product_code' => 'TSHIRT-CLASSIC',
        'design_id' => $design->id, 'printing_method' => 'dtf',
        'unit_price' => 1, // must be ignored
        'groups' => [catalogGroup('black', 'm', 2)],
    ])->assertOk()->assertJson(['redirect' => route('customer.basket')]);

    $item = CartItem::with('variant')->sole();
    expect($item->variant->sku)->toBe('TSHIRT-CLASSIC-BLACK-M')
        ->and($item->quantity)->toBe(2)
        ->and((float) $item->unit_price)->toBe(30.0)
        ->and($item->design_id)->toBe($design->id);

    $this->actingAs($this->customer)->get(route('customer.basket'))->assertOk()->assertSee('طباعة: الأمام');
});

test('unknown color and missing size fall back to a real variant but keep the choice', function () {
    $design = publishedDesignFor('TSHIRT-CLASSIC');

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
        'product_code' => 'TSHIRT-CLASSIC',
        'design_id' => $design->id, 'printing_method' => 'dtf',
        'groups' => [catalogGroup('default', 'xxl')],
    ])->assertOk();

    $item = CartItem::with('variant')->sole();
    expect($item->variant->product->code)->toBe('TSHIRT-CLASSIC')
        ->and($item->selected_options['size'])->toBe('xxl');
});

test('hoodie is added with its own design and the chosen navy variant', function () {
    $design = publishedDesignFor('HOODIE-PREMIUM', 45);

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
        'product_code' => 'HOODIE-PREMIUM',
        'design_id' => $design->id, 'printing_method' => 'dtf',
        'groups' => [catalogGroup('navy', 'l')],
    ])->assertOk();

    $item = CartItem::with('variant')->sole();
    expect($item->design_id)->toBe($design->id)->and($item->variant->sku)->toBe('HOODIE-PREMIUM-NAVY-L');
});

test('a design that is not published for that product is rejected', function () {
    $tshirtDesign = publishedDesignFor('TSHIRT-CLASSIC');
    publishedDesignFor('HOODIE-PREMIUM');

    foreach ([$tshirtDesign->id, 999999] as $designId) {
        $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
            'product_code' => 'HOODIE-PREMIUM',
            'design_id' => $designId,
            'groups' => [catalogGroup('black', 'm')],
        ])->assertUnprocessable()->assertJsonValidationErrors('product_code');
    }

    expect(CartItem::count())->toBe(0);
});

test('catalog options are normalized and de-duplicated from attribute values', function () {
    $options = App\Support\CatalogProductData::catalogOptions(
        Product::with('attributes.attribute', 'attributes.values.attributeValue')->where('code', 'TSHIRT-CLASSIC')->first()
    );

    expect(collect($options['colors'])->pluck('id')->all())->toBe(['white', 'black', 'navy'])
        ->and(collect($options['sizes'])->pluck('id')->all())->toBe(['s', 'm', 'l', 'xl'])
        ->and(App\Support\CatalogProductData::normalizeCode('tshirt-classic-XXL'))->toBe('xxl')
        ->and(App\Support\CatalogProductData::normalizeCode('mug-ceramic-قياسي'))->toBe('one-size');
});

test('mug is added with its one-size variant', function () {
    $design = publishedDesignFor('MUG-CERAMIC', 25);

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
        'product_code' => 'MUG-CERAMIC',
        'design_id' => $design->id, 'printing_method' => 'dtf',
        // The mug has one area, which the studio calls "front" (the database calls it "wrap").
        'groups' => [array_merge(catalogGroup('default', 'standard'), ['print_areas' => ['front']])],
    ])->assertOk();

    expect(CartItem::with('variant')->sole()->variant->sku)->toBe('MUG-CERAMIC-WHITE');
});

test('adding the same choice twice increases the quantity', function () {
    $design = publishedDesignFor('TSHIRT-CLASSIC');
    $payload = ['product_code' => 'TSHIRT-CLASSIC', 'design_id' => $design->id, 'printing_method' => 'dtf', 'groups' => [catalogGroup('white', 'l', 2)]];

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), $payload)->assertOk();
    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), $payload)->assertOk();

    expect(CartItem::count())->toBe(1)->and(CartItem::sole()->quantity)->toBe(4);
});

test('product without a published design is rejected', function () {
    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
        'product_code' => 'MUG-CERAMIC',
        'design_id' => 1,
        'groups' => [catalogGroup('default', 'standard')],
    ])->assertUnprocessable()->assertJsonValidationErrors('product_code');

    expect(CartItem::count())->toBe(0);
});

test('the printing method is required when the product has several, and it must be one a shop offers', function () {
    $design = publishedDesignFor('TSHIRT-CLASSIC');
    $payload = ['product_code' => 'TSHIRT-CLASSIC', 'design_id' => $design->id, 'groups' => [catalogGroup('black', 'm')]];

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), $payload)
        ->assertStatus(422)->assertJsonValidationErrors('printing_method');
    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), $payload + ['printing_method' => 'vinyl-cut'])
        ->assertStatus(422)->assertJsonValidationErrors('printing_method');
    expect(CartItem::count())->toBe(0);
});

test('the chosen printing method is saved on the cart line and the preview offers only real methods', function () {
    $design = publishedDesignFor('TSHIRT-CLASSIC');

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
        'product_code' => 'TSHIRT-CLASSIC', 'design_id' => $design->id, 'printing_method' => 'dtg', 'groups' => [catalogGroup('black', 'm')],
    ])->assertOk();

    expect(CartItem::sole()->selected_options)->toMatchArray(['printing_method' => 'dtg', 'printing_method_name' => \App\Models\PrintingMethod::where('code', 'dtg')->value('name')]);
    expect(array_column(\App\Support\PrintingMethodOptions::forPreview()['TSHIRT-CLASSIC'] ?? [], 'id'))->toContain('dtf', 'dtg');
});
