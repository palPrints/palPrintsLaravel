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

function pngUpload(string $name): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'sticker-test-');
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

    return new UploadedFile($path, $name, 'image/png', null, true);
}
