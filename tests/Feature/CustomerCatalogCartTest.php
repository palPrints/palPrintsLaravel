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
    $this->seed(CatalogDemoSeeder::class);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

test('t-shirt is added with the exact variant and the server-side price', function () {
    $design = publishedDesignFor('TSHIRT-CLASSIC', 30);

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
        'product_code' => 'TSHIRT-CLASSIC',
        'design_id' => $design->id,
        'unit_price' => 1, // must be ignored
        'groups' => [catalogGroup('black', 'm', 2)],
    ])->assertOk()->assertJson(['redirect' => route('customer.basket')]);

    $item = CartItem::with('variant')->sole();
    expect($item->variant->sku)->toBe('TSHIRT-CLASSIC-BLACK-M')
        ->and($item->quantity)->toBe(2)
        ->and((float) $item->unit_price)->toBe(30.0)
        ->and($item->design_id)->toBe($design->id);

    $this->actingAs($this->customer)->get(route('customer.basket'))->assertOk()->assertSee('black')->assertSee('طباعة: الأمام');
});

test('unknown color and missing size fall back to a real variant but keep the choice', function () {
    $design = publishedDesignFor('TSHIRT-CLASSIC');

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-catalog'), [
        'product_code' => 'TSHIRT-CLASSIC',
        'design_id' => $design->id,
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
        'design_id' => $design->id,
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
        'design_id' => $design->id,
        'groups' => [catalogGroup('default', 'standard')],
    ])->assertOk();

    expect(CartItem::with('variant')->sole()->variant->sku)->toBe('MUG-CERAMIC-WHITE');
});

test('adding the same choice twice increases the quantity', function () {
    $design = publishedDesignFor('TSHIRT-CLASSIC');
    $payload = ['product_code' => 'TSHIRT-CLASSIC', 'design_id' => $design->id, 'groups' => [catalogGroup('white', 'l', 2)]];

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
