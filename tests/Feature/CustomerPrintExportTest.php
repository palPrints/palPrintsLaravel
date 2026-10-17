<?php

use App\Models\CartItem;
use App\Models\PrintFile;
use App\Models\User;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

function designRequest(array $extra = []): array
{
    return array_merge([
        'product_code' => 'TSHIRT-CLASSIC',
        'design_name' => 'تصميمي',
        'printing_method' => 'dtf',
        'groups' => json_encode([['color_id' => 'black', 'color_name' => 'black', 'size_id' => 'm', 'size_name' => 'm', 'quantity' => 1, 'print_areas' => ['الأمام']]]),
    ], $extra);
}

function pngFile(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, base64_decode(ONE_PIXEL_PNG));
}

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleAndPermissionSeeder::class);

    $shop = User::factory()->create(['is_active' => true]);
    $shop->assignRole('print_provider');
    $shop->printProvider()->create(['company_name' => 'Demo Print House', 'phone' => '0599000001', 'approval_status' => 'approved', 'is_active' => true]);

    $this->seed(CatalogDemoSeeder::class);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

test('the print-ready picture of a printed area is saved with the cart line', function () {
    $this->actingAs($this->customer)->post(route('customer.cart.store-custom-design'), designRequest([
        'files' => [pngFile('logo.png')],
        'print_exports' => [pngFile('print-front.png')],
        'print_export_areas' => ['الأمام'],
    ]), ['Accept' => 'application/json'])->assertOk();

    $item = CartItem::with('printFiles')->sole();
    $export = $item->printFiles->firstWhere('original_name', 'print-ready-الأمام.png');

    expect($item->printFiles)->toHaveCount(2)
        ->and($export)->not->toBeNull()
        ->and($export->mime_type)->toBe('image/png')
        ->and($export->stored_path)->toStartWith('customer-print-files/exports/'.$this->customer->id.'/');
    Storage::disk('local')->assertExists($export->stored_path);
});

test('a picture for an area the line does not print is ignored', function () {
    $this->actingAs($this->customer)->post(route('customer.cart.store-custom-design'), designRequest([
        'files' => [pngFile('logo.png')],
        'print_exports' => [pngFile('print-back.png')],
        'print_export_areas' => ['الخلف'],
    ]), ['Accept' => 'application/json'])->assertOk();

    expect(PrintFile::where('original_name', 'like', 'print-ready-%')->count())->toBe(0);
});

test('the print-ready picture must really be a PNG, whatever its name says', function () {
    // A real file on disk: a fake upload takes its type from its name, a real one is read.
    $path = tempnam(sys_get_temp_dir(), 'exp');
    file_put_contents($path, '<?php echo 1;');

    $this->actingAs($this->customer)->post(route('customer.cart.store-custom-design'), designRequest([
        'files' => [pngFile('logo.png')],
        'print_exports' => [new UploadedFile($path, 'print-front.png', 'image/png', null, true)],
        'print_export_areas' => ['الأمام'],
    ]), ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('print_exports.0');
});
