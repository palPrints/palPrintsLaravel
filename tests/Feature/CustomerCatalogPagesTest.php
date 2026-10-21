<?php

use App\Models\User;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(CatalogDemoSeeder::class);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

test('the product catalog pages open and no longer carry the old upload dialog or back button', function (string $routeName) {
    $this->actingAs($this->customer)
        ->get(route($routeName))
        ->assertOk()
        ->assertDontSee('uploadDesignDialog', false)
        ->assertDontSee('catalog-back', false)
        ->assertDontSee('العودة إلى المنتجات');
})->with(['customer.tshirts', 'customer.hoodies', 'customer.mugs', 'customer.stickers']);

test('the store page offers upload-your-design, which leads to choosing the product', function () {
    $this->actingAs($this->customer)
        ->get(route('customer.store'))
        ->assertOk()
        ->assertSee('ارفع تصميمك الخاص')
        ->assertSee(route('customer.chooseProduct'), false);
});

test('a visitor who is not signed in can open the catalog pages too', function (string $routeName) {
    $this->get(route($routeName))->assertOk();
})->with(['customer.store', 'customer.tshirts', 'customer.hoodies', 'customer.mugs', 'customer.stickers']);

test('the customer picks a product for their own design among the T-shirt, the hoodie and the mug, not stickers or paper', function () {
    $category = \App\Models\Category::firstOrCreate(['slug' => 'products'], ['name' => 'منتجات']);
    foreach (['PAPER-PRINT', 'STICKER-CUSTOM'] as $code) {
        \App\Models\Product::firstOrCreate(['code' => $code], ['category_id' => $category->id, 'name' => $code, 'is_active' => true])->update(['is_active' => true]);
    }
    $id = fn (string $code) => (string) \App\Models\Product::where('code', $code)->value('id');

    $listed = collect($this->get(route('customer.chooseProduct'))->assertOk()->viewData('designerCatalog')['products'])->pluck('id');

    expect($listed)->toContain($id('TSHIRT-CLASSIC'), $id('HOODIE-PREMIUM'), $id('MUG-CERAMIC'))
        ->not->toContain($id('PAPER-PRINT'))
        ->not->toContain($id('STICKER-CUSTOM'));
});
