<?php

use App\Models\User;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

test('catalog pages open without an upload-your-design button or dialog', function (string $routeName) {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(CatalogDemoSeeder::class);
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route($routeName))
        ->assertOk()
        ->assertDontSee('ارفع تصميمك الخاص')
        ->assertDontSee('uploadDesignDialog', false)
        ->assertDontSee('front/js/customer/uploadDesign.js', false)
        ->assertDontSee('catalog-back', false)
        ->assertDontSee('العودة إلى المنتجات');
})->with(['customer.tshirts', 'customer.hoodies', 'customer.mugs', 'customer.stickers']);
