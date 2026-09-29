<?php

use App\Models\User;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

test('catalog pages show the upload-your-design button instead of the back button', function (string $routeName) {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(CatalogDemoSeeder::class);
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route($routeName))
        ->assertOk()
        ->assertSee('ارفع تصميمك الخاص')
        ->assertSee('uploadDesignDialog', false)
        ->assertSee('front/js/customer/uploadDesign.js', false)
        ->assertDontSee('catalog-back', false)
        ->assertDontSee('العودة إلى المنتجات');
})->with(['customer.tshirts', 'customer.hoodies', 'customer.mugs', 'customer.stickers']);
