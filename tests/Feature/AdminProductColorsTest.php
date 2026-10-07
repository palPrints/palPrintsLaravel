<?php

use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Support\ProductVariants;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** A 1x1 PNG, so the image rule passes without the GD extension. */
function productPng(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('shirt.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
}

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
    $this->category = Category::create(['name' => 'ملابس', 'slug' => 'clothes']);

    $this->create = fn (array $overrides = []) => $this->actingAs($this->admin)->post(route('admin.products.store'), array_merge([
        'name' => 'تيشيرت رياضي',
        'code' => 'SPORT-TEE',
        'category_id' => $this->category->id,
        'image' => productPng(),
        'colors' => json_encode([['name' => 'أحمر', 'hex' => '#ff0000'], ['name' => 'أزرق', 'hex' => '#0000ff']]),
        'sizes' => 'S, M, L',
    ], $overrides), ['Accept' => 'application/json']);
});

test('a new product gets its colours, sizes and every colour x size variant', function () {
    ($this->create)()->assertCreated();

    $product = Product::where('code', 'SPORT-TEE')->firstOrFail();
    $options = ProductVariants::options($product);

    expect(collect($options['colors'])->pluck('name')->all())->toBe(['أحمر', 'أزرق'])
        ->and(collect($options['colors'])->pluck('hex')->all())->toBe(['#ff0000', '#0000ff'])
        ->and($options['sizes'])->toBe(['S', 'M', 'L'])
        ->and($product->variants()->count())->toBe(6)
        ->and($product->variants()->where('is_active', true)->count())->toBe(6)
        ->and($product->variants()->pluck('sku')->sort()->values()->all())->toContain('SPORT-TEE-CFF0000-S', 'SPORT-TEE-C0000FF-L');
});

test('an existing colour of the platform is reused, not duplicated', function () {
    ($this->create)(['colors' => json_encode([['name' => 'أحمر', 'hex' => '#ff0000']])])->assertCreated();
    ($this->create)(['code' => 'SECOND-TEE', 'colors' => json_encode([['name' => 'أحمر', 'hex' => '#ff0000']])])->assertCreated();

    expect(AttributeValue::where('code', 'cff0000')->count())->toBe(1);
});

test('a product cannot be saved without a colour or a size, and nothing is left behind', function () {
    ($this->create)(['colors' => json_encode([])])->assertUnprocessable()->assertJsonValidationErrors('colors');
    ($this->create)(['sizes' => ''])->assertUnprocessable()->assertJsonValidationErrors('sizes');
    ($this->create)(['colors' => json_encode([['name' => 'أحمر', 'hex' => 'red']])])->assertUnprocessable();
    ($this->create)(['colors' => json_encode([['name' => 'أحمر', 'hex' => '#ff0000'], ['name' => 'نفس اللون', 'hex' => '#FF0000']])])->assertUnprocessable()->assertJsonValidationErrors('colors');
    ($this->create)(['colors' => json_encode([['name' => '', 'hex' => '#ff0000']])])->assertUnprocessable();

    expect(Product::count())->toBe(0)
        ->and(Variant::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('adding a colour on edit only adds the new variants', function () {
    ($this->create)()->assertCreated();
    $product = Product::where('code', 'SPORT-TEE')->firstOrFail();
    $before = $product->variants()->pluck('id')->sort()->values()->all();
    $options = ProductVariants::options($product);

    $this->actingAs($this->admin)->patch(route('admin.products.update', $product), [
        'name' => $product->name, 'code' => $product->code, 'category_id' => $this->category->id,
        'colors' => json_encode(array_merge($options['colors'], [['name' => 'أخضر', 'hex' => '#00ff00']])),
        'sizes' => implode(',', $options['sizes']),
    ], ['Accept' => 'application/json'])->assertOk();

    $after = $product->variants()->pluck('id')->sort()->values()->all();
    expect(array_slice($after, 0, 6))->toBe($before)
        ->and(count($after))->toBe(9)
        ->and($product->variants()->where('is_active', false)->count())->toBe(0);
});

test('taking a colour off switches its variants off and adding it back switches them on again', function () {
    ($this->create)()->assertCreated();
    $product = Product::where('code', 'SPORT-TEE')->firstOrFail();
    $options = ProductVariants::options($product);
    $red = $options['colors'][0];
    $blue = $options['colors'][1];
    $send = fn (array $colors) => $this->actingAs($this->admin)->patch(route('admin.products.update', $product), [
        'name' => $product->name, 'code' => $product->code, 'category_id' => $this->category->id,
        'colors' => json_encode($colors), 'sizes' => 'S, M, L',
    ], ['Accept' => 'application/json'])->assertOk();

    $ids = $product->variants()->pluck('id')->sort()->values()->all();

    $send([$red]);
    expect($product->variants()->count())->toBe(6)               // nothing is deleted
        ->and($product->variants()->where('is_active', true)->count())->toBe(3)
        ->and(collect(ProductVariants::options($product->fresh())['colors'])->pluck('name')->all())->toBe(['أحمر']);

    $send([$red, $blue]);
    expect($product->variants()->pluck('id')->sort()->values()->all())->toBe($ids)   // the same variants come back
        ->and($product->variants()->where('is_active', true)->count())->toBe(6);
});

test('taking a size off switches those variants off', function () {
    ($this->create)()->assertCreated();
    $product = Product::where('code', 'SPORT-TEE')->firstOrFail();

    $this->actingAs($this->admin)->patch(route('admin.products.update', $product), [
        'name' => $product->name, 'code' => $product->code, 'category_id' => $this->category->id,
        'colors' => json_encode(ProductVariants::options($product)['colors']), 'sizes' => 'S, M',
    ], ['Accept' => 'application/json'])->assertOk();

    expect($product->variants()->where('is_active', true)->count())->toBe(4)
        ->and(ProductVariants::options($product->fresh())['sizes'])->toBe(['S', 'M']);
});

test('editing a product without sending colours leaves them alone', function () {
    ($this->create)()->assertCreated();
    $product = Product::where('code', 'SPORT-TEE')->firstOrFail();

    $this->actingAs($this->admin)->patch(route('admin.products.update', $product), [
        'name' => 'اسم جديد', 'code' => $product->code, 'category_id' => $this->category->id,
    ], ['Accept' => 'application/json'])->assertOk();

    expect($product->fresh()->name)->toBe('اسم جديد')
        ->and($product->variants()->where('is_active', true)->count())->toBe(6);
});

test('"one size" is stored as the single-size value', function () {
    ($this->create)(['code' => 'MUG-X', 'sizes' => 'مقاس واحد'])->assertCreated();
    $product = Product::where('code', 'MUG-X')->firstOrFail();

    expect(ProductVariants::options($product)['sizes'])->toBe(['مقاس واحد'])
        ->and($product->variants()->pluck('sku')->all())->toBe(['MUG-X-CFF0000', 'MUG-X-C0000FF']);
});

test('the admin products page hands the dialog each product\'s colours and sizes', function () {
    ($this->create)()->assertCreated();

    $this->actingAs($this->admin)->get(route('admin.products'))
        ->assertOk()
        ->assertSee('data-sizes="S، M، L"', false)
        ->assertSee('أحمر');
});

test('only an admin can set a product\'s colours', function () {
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');

    $this->actingAs($customer)->post(route('admin.products.store'), ['name' => 'x'], ['Accept' => 'application/json'])->assertForbidden();
    expect(Product::count())->toBe(0);
});

test('the colours the admin adds appear on the print shop\'s services page, with their name and colour', function () {
    ($this->create)()->assertCreated();
    $product = Product::where('code', 'SPORT-TEE')->firstOrFail();

    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $owner->printProvider()->create(['company_name' => 'مطبعة', 'approval_status' => 'approved', 'is_active' => true]);

    $this->actingAs($owner)->get(route('print-provider.services'))
        ->assertOk()
        ->assertViewHas('servicesData', function ($data) use ($product) {
            $listed = collect($data['products'])->firstWhere('id', $product->id);
            $colors = collect($listed['colors'])->keyBy('id');

            return $colors->has('cff0000') && $colors['cff0000']['label'] === 'أحمر' && $colors['cff0000']['value'] === '#ff0000'
                && collect($listed['sizes'])->pluck('label')->all() === ['S', 'M', 'L'];
        });
});