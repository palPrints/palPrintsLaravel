<?php

use App\Models\BranchProductOffering;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Services\Stickers\StickerProviderPricing;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $category = Category::create(['name' => 'Stickers', 'slug' => 'stickers']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'Stickers', 'code' => 'STICKER-CUSTOM', 'is_active' => true]);
    $this->variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'ST-A4', 'is_active' => true]);
    $this->owner = User::factory()->create(['is_active' => true]);
    $this->owner->assignRole('print_provider');
    $this->provider = $this->owner->printProvider()->create(['company_name' => 'Print shop', 'approval_status' => 'approved', 'is_active' => true]);
});

test('sticker offering saves sheet price and capacity without garment options', function () {
    $this->actingAs($this->owner)->putJson(route('print-provider.services.save', $this->product), ['price' => 2.5, 'days' => 2, 'capacity' => 100])
        ->assertOk()->assertJsonPath('product.settings.price', 2.5)->assertJsonPath('product.settings.capacity', 100);
    $offering = $this->provider->primaryBranch()->branchProductOfferings()->sole();
    expect((float) $offering->base_price)->toBe(2.5)
        ->and($offering->branchOfferingVariants()->where('variant_id', $this->variant->id)->value('is_available'))->toBeTrue()
        ->and($offering->branchPrintAreas()->count())->toBe(0);
    $this->get(route('print-provider.services'))->assertOk();
    $this->putJson(route('print-provider.services.save', $this->product), ['price' => -1, 'days' => 2, 'capacity' => 100])->assertUnprocessable();
    expect((float) $offering->fresh()->base_price)->toBe(2.5);
});

test('provider cost uses actual snapshot pages not sticker quantity or claimed page count', function () {
    $offering = new BranchProductOffering(['base_price' => 2.5]);
    $options = ['sticker_quantity' => 20, 'layout_snapshot' => ['page_count' => 999, 'pages' => [['items' => [['copy' => 1]]], ['items' => [['copy' => 2]]]]]];
    $pricing = app(StickerProviderPricing::class);
    expect($pricing->quote($offering, $options))->toBe(['unit' => 'a4_sheet', 'sheets' => 2, 'unit_price' => 2.5, 'total' => 5.0]);
    $item = new OrderItem(['item_type' => OrderItem::TYPE_STICKER_BUNDLE, 'total_price' => 10, 'selected_options' => $options]);
    $offering->base_price = 3;
    $assignment = $pricing->assignment($item, $offering);
    expect($assignment['provider_cost'])->toBe(6.0)->and($assignment['platform_commission'])->toBe(4.0)
        ->and($assignment['selected_options']['provider_pricing']['unit_price'])->toBe(3.0)
        ->and((float) $item->total_price)->toBe(10.0);
    expect(fn () => $pricing->quote($offering, []))->toThrow(ValidationException::class);
});
