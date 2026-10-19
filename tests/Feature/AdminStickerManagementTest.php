<?php

use App\Models\StickerCategory;
use App\Models\StickerLibraryItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
});

test('admin uploads a sticker image and receives refreshed categories', function () {
    Storage::fake('public');

    $category = StickerCategory::create([
        'name' => 'هندسة',
        'slug' => 'engineering',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $this->actingAs($this->admin)->postJson(route('admin.stickers.items.store'), [
        'sticker_category_id' => $category->id,
        'name' => 'مسطرة',
        'image' => pngUpload('ruler.png'),
        'is_active' => 1,
        'sort_order' => 20,
    ])->assertOk()
        ->assertJsonPath('categories.0.stickers.0.name', 'مسطرة');

    $item = StickerLibraryItem::query()->sole();
    Storage::disk('public')->assertExists($item->image_path);
});

test('admin settings persist exact sizes and preview uses unsaved server layout', function () {
    $service = app(\App\Services\Stickers\StickerSettingsService::class);
    $draft = $service->get();
    $draft['layout']['sizes'] = [['key' => '5x5', 'label' => '50 mm', 'width_mm' => 50, 'height_mm' => 50]];
    $draft['layout']['margin_right_mm'] = 20;
    $draft['pricing']['base_price'] = 15;
    $this->actingAs($this->admin)->postJson(route('admin.stickers.preview'), $draft)
        ->assertOk()->assertJsonPath('layout.placements.0.x_mm', 140)
        ->assertJsonPath('layout.page_count', 2);
    expect($service->get()['pricing']['base_price'])->toBe(10);
    $this->postJson(route('admin.stickers.settings.update'), $draft)->assertOk();
    expect($service->get()['layout']['sizes'])->toHaveCount(1)
        ->and($service->get()['pricing']['base_price'])->toBe(15);
    $this->get(route('admin.stickers'))->assertOk()->assertSee('panel-printing', false);
    $this->get(route('customer.stickers'))->assertForbidden();
    $draft['layout']['sizes'][0]['width_mm'] = 190;
    $draft['layout']['margin_left_mm'] = 50;
    $this->postJson(route('admin.stickers.settings.update'), $draft)->assertUnprocessable();
    expect($service->get()['layout']['sizes'][0]['width_mm'])->toBe(50);
});

test('categories persist changes and nonempty categories cannot be deleted', function () {
    $this->actingAs($this->admin)->postJson(route('admin.stickers.categories.store'), ['name' => 'Engineering', 'sort_order' => 3])->assertOk();
    $category = StickerCategory::query()->sole();
    $this->patchJson(route('admin.stickers.categories.update', $category), ['name' => 'Medicine', 'sort_order' => 8, 'is_active' => false])->assertOk();
    expect($category->fresh()->name)->toBe('Medicine')->and($category->fresh()->is_active)->toBeFalse();
    StickerLibraryItem::create(['sticker_category_id' => $category->id, 'name' => 'Test', 'image_path' => 'test.png', 'disk' => 'public', 'is_active' => true]);
    $this->deleteJson(route('admin.stickers.categories.destroy', $category))->assertUnprocessable();
    expect($category->fresh())->not->toBeNull();
});

test('non admins cannot save settings or request admin layout previews', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('customer');
    $this->actingAs($user)->postJson(route('admin.stickers.preview'), [])->assertForbidden();
    $this->postJson(route('admin.stickers.settings.update'), [])->assertForbidden();
});

test('hidden library images remain visible to admin but not customers', function () {
    Storage::fake('public');
    $category = StickerCategory::create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);
    $item = StickerLibraryItem::create(['sticker_category_id' => $category->id, 'name' => 'Hidden image', 'image_path' => 'hidden.png', 'disk' => 'public', 'is_active' => false]);
    Storage::disk('public')->put('hidden.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
    $this->actingAs($this->admin)->get($item->imageUrl())->assertOk();
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');
    $this->actingAs($customer)->get($item->imageUrl())->assertNotFound();
});

function pngUpload(string $name): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'sticker-test-');
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

    return new UploadedFile($path, $name, 'image/png', null, true);
}
