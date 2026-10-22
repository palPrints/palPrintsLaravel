<?php

use App\Models\CartItem;
use App\Models\StickerBundleLine;
use App\Models\StickerCategory;
use App\Models\StickerLibraryItem;
use App\Models\User;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StickerDemoSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $shop = User::factory()->create(['is_active' => true]);
    $shop->assignRole('print_provider');
    $shop->printProvider()->create(['company_name' => 'Sticker Shop', 'phone' => '0599000001', 'approval_status' => 'approved', 'is_active' => true]);

    $this->seed(CatalogDemoSeeder::class);
    $this->seed(StickerDemoSeeder::class);

    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');

    $category = StickerCategory::query()->firstOrFail();
    $this->sticker = StickerLibraryItem::create([
        'sticker_category_id' => $category->id,
        'name' => 'Test Sticker',
        'image_path' => 'stickers/library/test.png',
        'disk' => 'public',
        'mime_type' => 'image/png',
        'file_size' => 100,
        'is_active' => true,
        'sort_order' => 10,
    ]);
});

test('library sticker image is served without requiring a public storage symlink', function () {
    Storage::disk('public')->put('stickers/library/test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

    expect($this->sticker->imageUrl())->toContain(route('stickers.library-image', $this->sticker, false));

    $this->get($this->sticker->imageUrl())->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('sticker preview returns server price and a4 layout', function () {
    $this->postJson(route('customer.stickers.preview'), [
        'lines' => [[
            'client_id' => 'line-1',
            'source' => 'library',
            'library_item_id' => $this->sticker->id,
            'quantity' => 20,
            'size_key' => '5x5',
            'cut_type' => 'circle',
        ]],
    ])->assertOk()
        ->assertJsonPath('quote.quantity', 20)
        ->assertJsonPath('quote.total', 10)
        ->assertJsonPath('layout_snapshot.page_count', 2);
});

test('sticker bundle rejects below minimum and stores valid bundle as one cart item', function () {
    $line = [
        'client_id' => 'line-1',
        'source' => 'library',
        'library_item_id' => $this->sticker->id,
        'size_key' => '5x5',
        'cut_type' => 'circle',
    ];

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-sticker-bundle'), [
        'lines' => [$line + ['quantity' => 19]],
    ])->assertUnprocessable();

    $this->actingAs($this->customer)->postJson(route('customer.cart.store-sticker-bundle'), [
        'lines' => [$line + ['quantity' => 20]],
    ])->assertOk()->assertJson(['redirect' => route('customer.basket')]);

    $item = CartItem::query()->with('stickerBundleLines')->sole();
    expect($item->item_type)->toBe(CartItem::TYPE_STICKER_BUNDLE)
        ->and((float) $item->unit_price)->toBe(10.0)
        ->and($item->quantity)->toBe(1)
        ->and($item->selected_options['sticker_quantity'])->toBe(20)
        ->and($item->selected_options['layout_snapshot']['page_count'])->toBe(2);

    expect(StickerBundleLine::query()->count())->toBe(1);
});

test('the basket shows the stickers of a bundle, not the product picture', function () {
    $this->actingAs($this->customer)->postJson(route('customer.cart.store-sticker-bundle'), [
        'lines' => [[
            'client_id' => 'line-1', 'source' => 'library', 'library_item_id' => $this->sticker->id,
            'size_key' => '5x5', 'cut_type' => 'circle', 'quantity' => 20,
        ]],
    ])->assertOk();

    $this->actingAs($this->customer)->get(route('customer.basket'))
        ->assertOk()
        ->assertSee('sticker-thumbs', false)
        ->assertSee($this->sticker->imageUrl(), false)
        ->assertViewHas('items', fn ($items) => $items->first()['sticker_images'] === [$this->sticker->imageUrl()]);
});
