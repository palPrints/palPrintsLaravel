<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(RoleAndPermissionSeeder::class);

    $this->designer = User::factory()->create(['is_active' => true]);
    $this->designer->assignRole('designer');
    $this->designer->designerProfile()->create(['full_name' => $this->designer->name, 'approval_status' => 'approved']);

    $category = Category::create(['name' => 'أكواب', 'slug' => 'mugs']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'كوب', 'code' => 'MUG-CERAMIC', 'is_active' => true]);
    $this->variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'MUG-1', 'is_active' => true]);
});

/** A design of this designer with a saved picture and one private artwork file, like the studio saves it. */
function savedDesign($designer, $product, string $status = 'draft', string $title = 'تصميمي'): Design
{
    $design = Design::create([
        'designer_id' => $designer->id, 'product_id' => $product->id, 'title' => $title, 'description' => 'كوب',
        'base_price' => 10, 'selling_price' => 25, 'designer_profit' => 15, 'status' => $status,
        'selected_options' => ['color_id' => 'white', 'size_id' => 'standard', 'allowed_color_ids' => ['white'], 'display_category' => null, 'allowed_size_ids' => []],
    ]);

    Storage::disk('public')->put('designs/'.$design->id.'-old.png', 'preview');
    $path = 'designer-designs/'.$designer->id.'/'.$design->id.'/art.png';
    Storage::disk('local')->put($path, 'artwork');

    $design->update([
        'image' => 'storage/designs/'.$design->id.'-old.png',
        'design_payload' => [
            'product_code' => $product->code,
            'layout' => ['colorId' => 'white', 'sizeId' => 'standard', 'areas' => ['front' => ['objects' => [['id' => 'o1', 'kind' => 'image', 'assetId' => 'asset-1', 'x' => 0.5, 'y' => 0.5]]]]],
            'files' => [['asset_id' => 'asset-1', 'name' => 'art.png', 'path' => $path, 'mime_type' => 'image/png', 'size' => 7]],
        ],
    ]);

    return $design->fresh();
}

function buyDesign(Design $design, $variant): void
{
    $buyer = User::factory()->create();
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $branch = $owner->printProvider()->create(['company_name' => 'مطبعة', 'approval_status' => 'approved', 'is_active' => true])->primaryBranch();
    $offering = $branch->branchProductOfferings()->create([
        'product_id' => $design->product_id, 'base_price' => 10, 'currency' => 'ILS',
        'production_time_min' => 2, 'production_time_max' => 2, 'daily_capacity' => 10, 'is_active' => true,
    ]);
    $order = Order::create(['user_id' => $buyer->id, 'order_number' => 'PP-'.$design->id, 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 25, 'total_amount' => 25]);
    OrderItem::create([
        'order_id' => $order->id, 'product_id' => $design->product_id, 'variant_id' => $variant->id, 'design_id' => $design->id, 'designer_id' => $design->designer_id,
        'print_provider_branch_id' => $branch->id, 'branch_product_offering_id' => $offering->id, 'quantity' => 1, 'unit_price' => 25, 'total_price' => 25,
    ]);
}

function otherDesigner(): User
{
    $other = User::factory()->create(['is_active' => true]);
    $other->assignRole('designer');
    $other->designerProfile()->create(['full_name' => $other->name, 'approval_status' => 'approved']);

    return $other;
}

function studioPayload(array $overrides = []): array
{
    return [
        'data' => json_encode($overrides + [
            'status' => 'draft', 'productCode' => 'MUG-CERAMIC', 'colorId' => 'white', 'sizeId' => 'standard', 'designName' => 'الاسم الجديد',
            'sellingPrice' => 30, 'allowedColorIds' => ['white'],
            'layout' => ['colorId' => 'white', 'sizeId' => 'standard', 'areas' => ['front' => ['objects' => [['id' => 'o1', 'kind' => 'image', 'assetId' => 'asset-1']]]]],
            'assets' => [['assetId' => 'asset-1', 'name' => 'new.png', 'mimeType' => 'image/png', 'size' => 3]],
        ]),
        'preview' => UploadedFile::fake()->create('preview.png', 10, 'image/png'),
        'files' => [UploadedFile::fake()->create('new.png', 10, 'image/png')],
        'file_assets' => ['asset-1'],
    ];
}

test('the designs page tells the page what each design allows', function () {
    $draft = savedDesign($this->designer, $this->product, 'draft', 'مسودة أولى');
    $published = savedDesign($this->designer, $this->product, 'published', 'منشور أول');

    $html = $this->actingAs($this->designer)->get(route('designer.designs.index'))->assertOk()->getContent();

    expect($html)->toContain(json_encode(route('design-studio', ['edit' => $draft->id]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
        ->and($html)->toContain('"unpublish":true')
        ->and($html)->toContain('"edit":true');
});

test('each design that cannot be edited says why, and an editable one says nothing', function () {
    $draft = savedDesign($this->designer, $this->product, 'draft', 'مسودة');
    $published = savedDesign($this->designer, $this->product, 'published', 'منشور');
    $waiting = savedDesign($this->designer, $this->product, 'review', 'ينتظر');
    $ordered = savedDesign($this->designer, $this->product, 'draft', 'مطلوب');
    buyDesign($ordered, $this->variant);

    $reason = fn (Design $design) => \App\Http\Controllers\Designer\DesignController::lockReason($design->loadCount(['orderItems', 'cartItems']));

    expect($reason($draft))->toBeNull()
        ->and($reason($published))->toContain('منشور')->toContain('إيقاف نشره')
        ->and($reason($waiting))->toContain('قيد المراجعة')->toContain('اسحبه')
        ->and($reason($ordered))->toContain('طلبه عملاء');

    $html = $this->actingAs($this->designer)->get(route('designer.designs.index'))->assertOk()->getContent();
    expect($html)->toContain('"lockReason":"منشور: لا يُعدَّل');
});

test('a draft can be deleted together with its picture and artwork', function () {
    $design = savedDesign($this->designer, $this->product);

    $this->actingAs($this->designer)->deleteJson(route('designer.designs.destroy', $design))->assertOk();

    expect(Design::find($design->id))->toBeNull();
    Storage::disk('public')->assertMissing('designs/'.$design->id.'-old.png');
    Storage::disk('local')->assertMissing('designer-designs/'.$this->designer->id.'/'.$design->id.'/art.png');
});

test('a rejected design can be deleted', function () {
    $design = savedDesign($this->designer, $this->product, 'rejected');

    $this->actingAs($this->designer)->deleteJson(route('designer.designs.destroy', $design))->assertOk();
    expect(Design::find($design->id))->toBeNull();
});

test('a published design is never deleted', function () {
    $design = savedDesign($this->designer, $this->product, 'published');

    $this->actingAs($this->designer)->deleteJson(route('designer.designs.destroy', $design))->assertStatus(422);

    expect(Design::find($design->id))->not->toBeNull();
    Storage::disk('local')->assertExists('designer-designs/'.$this->designer->id.'/'.$design->id.'/art.png');
});

test('a design waiting for review can be deleted too, with its files', function () {
    $design = savedDesign($this->designer, $this->product, 'review');

    $this->actingAs($this->designer)->deleteJson(route('designer.designs.destroy', $design))->assertOk();

    expect(Design::find($design->id))->toBeNull();
    Storage::disk('local')->assertMissing('designer-designs/'.$this->designer->id.'/'.$design->id.'/art.png');
});

test('withdrawing a design from review turns it into a draft the designer can then edit, and the admin can no longer decide on it', function () {
    $design = savedDesign($this->designer, $this->product, 'review');
    $design->update(['submitted_at' => now()]);

    // Waiting for review: not editable yet.
    $this->actingAs($this->designer)->get(route('design-studio', ['edit' => $design->id]))->assertNotFound();

    $this->actingAs($this->designer)->postJson(route('designer.designs.withdraw', $design))
        ->assertOk()
        ->assertJson(['redirect' => route('design-studio', ['edit' => $design->id])]);

    expect($design->fresh()->status)->toBe('draft')->and($design->fresh()->submitted_at)->toBeNull();
    $this->actingAs($this->designer)->get(route('design-studio', ['edit' => $design->id]))->assertOk();

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');
    $this->actingAs($admin)->postJson(route('admin.designs.review', $design), ['action' => 'approve'])->assertStatus(422);
    expect($design->fresh()->status)->toBe('draft');
});

test('only a design that is waiting for review can be withdrawn, and only by its designer', function () {
    $draft = savedDesign($this->designer, $this->product, 'draft');
    $waiting = savedDesign($this->designer, $this->product, 'review');

    $this->actingAs($this->designer)->postJson(route('designer.designs.withdraw', $draft))->assertStatus(422);
    $this->actingAs(otherDesigner())->postJson(route('designer.designs.withdraw', $waiting))->assertNotFound();

    expect($waiting->fresh()->status)->toBe('review');
});

test('a design somebody ordered can be neither deleted nor edited, only copied', function () {
    $design = savedDesign($this->designer, $this->product, 'draft');
    buyDesign($design, $this->variant);

    $this->actingAs($this->designer)->deleteJson(route('designer.designs.destroy', $design))->assertStatus(422);
    $this->actingAs($this->designer)->get(route('design-studio', ['edit' => $design->id]))->assertNotFound();
    $this->actingAs($this->designer)->post(route('designer.designs.store'), studioPayload(['editingDesignId' => $design->id]), ['Accept' => 'application/json'])->assertStatus(422);

    expect(Design::find($design->id)->title)->toBe('تصميمي');
    $this->actingAs($this->designer)->postJson(route('designer.designs.duplicate', $design))->assertCreated();
});

test('nobody can touch another designer\'s design', function () {
    $design = savedDesign($this->designer, $this->product);
    $other = otherDesigner();

    $this->actingAs($other)->deleteJson(route('designer.designs.destroy', $design))->assertNotFound();
    $this->actingAs($other)->postJson(route('designer.designs.unpublish', $design))->assertNotFound();
    $this->actingAs($other)->postJson(route('designer.designs.duplicate', $design))->assertNotFound();
    $this->actingAs($other)->get(route('designer.designs.file', [$design, 'asset-1']))->assertNotFound();
    $this->actingAs($other)->get(route('design-studio', ['edit' => $design->id]))->assertNotFound();
    $this->actingAs($other)->post(route('designer.designs.store'), studioPayload(['editingDesignId' => $design->id]), ['Accept' => 'application/json'])->assertStatus(422);

    expect(Design::find($design->id))->not->toBeNull();
});

test('taking a design off the store makes it a draft and clears it from baskets, but orders keep it', function () {
    $design = savedDesign($this->designer, $this->product, 'published');
    $design->update(['published_at' => now()]);
    $buyer = User::factory()->create();
    $cart = Cart::create(['user_id' => $buyer->id, 'status' => 'active']);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id, 'design_id' => $design->id, 'quantity' => 1, 'unit_price' => 25, 'item_type' => CartItem::TYPE_CATALOG_DESIGN]);
    buyDesign($design, $this->variant);

    $this->actingAs($this->designer)->postJson(route('designer.designs.unpublish', $design))->assertOk();

    $design->refresh();
    expect($design->status)->toBe('draft')->and($design->published_at)->toBeNull()
        ->and(CartItem::where('design_id', $design->id)->count())->toBe(0)
        ->and(OrderItem::where('design_id', $design->id)->count())->toBe(1);
});

test('only a published design can be taken off the store', function () {
    $design = savedDesign($this->designer, $this->product, 'draft');

    $this->actingAs($this->designer)->postJson(route('designer.designs.unpublish', $design))->assertStatus(422);
});

test('a copy is a new draft with its own picture and artwork files', function () {
    $design = savedDesign($this->designer, $this->product, 'published', 'الأصل');

    $id = $this->actingAs($this->designer)->postJson(route('designer.designs.duplicate', $design))->assertCreated()->json('id');
    $copy = Design::find($id);

    expect($copy->status)->toBe('draft')
        ->and($copy->title)->toBe('نسخة من الأصل')
        ->and($copy->id)->not->toBe($design->id)
        ->and($copy->image)->not->toBe($design->image)
        ->and($copy->design_payload['files'][0]['path'])->not->toBe($design->design_payload['files'][0]['path'])
        ->and($copy->design_payload['files'][0]['asset_id'])->toBe('asset-1')
        ->and($design->fresh()->status)->toBe('published');

    Storage::disk('public')->assertExists(str_replace('storage/', '', $copy->image));
    Storage::disk('local')->assertExists($copy->design_payload['files'][0]['path']);
    Storage::disk('local')->assertExists($design->design_payload['files'][0]['path']); // the original keeps its own
});

test('the studio opens a saved draft with its layout, colour, size and artwork links', function () {
    $design = savedDesign($this->designer, $this->product, 'draft', 'كوب الصيف');

    $html = $this->actingAs($this->designer)->get(route('design-studio', ['edit' => $design->id]))->assertOk()->getContent();

    // The page embeds the design the way Blade's @json does (escaped), which is what the studio script reads.
    $embedded = fn (string $text) => trim(json_encode($text, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), '"');

    expect($html)->toContain('const EDIT = {"id":'.$design->id)
        ->and($html)->toContain($embedded('كوب الصيف'))
        ->and($html)->toContain('asset-1')
        ->and($html)->toContain($embedded(route('designer.designs.file', [$design, 'asset-1'])));
    $this->actingAs($this->designer)->get(route('design-studio'))->assertOk(); // no edit: the usual empty studio
});

test('a published or waiting design cannot be opened in the studio for editing', function (string $status) {
    $design = savedDesign($this->designer, $this->product, $status);

    $this->actingAs($this->designer)->get(route('design-studio', ['edit' => $design->id]))->assertNotFound();
})->with(['published', 'review']);

test('the owner can load an artwork file, and gets it without being able to run anything in it', function () {
    $design = savedDesign($this->designer, $this->product);

    $this->actingAs($this->designer)->get(route('designer.designs.file', [$design, 'asset-1']))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($this->designer)->get(route('designer.designs.file', [$design, 'nope']))->assertNotFound();
});

test('saving a reopened draft updates that design, replaces its picture and artwork, and can send it for review', function () {
    $design = savedDesign($this->designer, $this->product);
    $oldFile = $design->design_payload['files'][0]['path'];

    $response = $this->actingAs($this->designer)
        ->post(route('designer.designs.store'), studioPayload(['editingDesignId' => $design->id, 'status' => 'submitted', 'rightsConfirmed' => true]), ['Accept' => 'application/json'])
        ->assertCreated();

    expect($response->json('id'))->toBe($design->id)
        ->and(Design::count())->toBe(1);

    $design->refresh();
    expect($design->title)->toBe('الاسم الجديد')
        ->and((float) $design->selling_price)->toBe(30.0)
        ->and($design->status)->toBe('review')
        ->and($design->design_payload['assets'][0]['assetId'])->toBe('asset-1')
        ->and($design->design_payload['files'])->toHaveCount(1);

    Storage::disk('public')->assertMissing('designs/'.$design->id.'-old.png');
    Storage::disk('public')->assertExists(str_replace('storage/', '', $design->image));
    Storage::disk('local')->assertMissing($oldFile);
    Storage::disk('local')->assertExists($design->design_payload['files'][0]['path']);
});

test('a rejected design reopened and sent again goes back to review with the old verdict cleared', function () {
    $design = savedDesign($this->designer, $this->product, 'rejected');
    $design->update(['rejection_reason' => 'الصورة غير واضحة', 'reviewed_at' => now()]);

    $this->actingAs($this->designer)
        ->post(route('designer.designs.store'), studioPayload(['editingDesignId' => $design->id, 'status' => 'submitted', 'rightsConfirmed' => true]), ['Accept' => 'application/json'])
        ->assertCreated();

    $design->refresh();
    expect($design->status)->toBe('review')->and($design->rejection_reason)->toBeNull()->and($design->reviewed_at)->toBeNull();
});

test('a picture the layout still uses keeps its saved file when the browser does not send it again', function () {
    $design = savedDesign($this->designer, $this->product);
    $payload = studioPayload(['editingDesignId' => $design->id]);
    unset($payload['files'], $payload['file_assets']);

    $this->actingAs($this->designer)->post(route('designer.designs.store'), $payload, ['Accept' => 'application/json'])->assertCreated();

    $files = $design->fresh()->design_payload['files'];
    expect($files)->toHaveCount(1)->and($files[0]['asset_id'])->toBe('asset-1');
    Storage::disk('local')->assertExists($files[0]['path']);
});

test('a picture the layout no longer uses is dropped, so a print shop never gets stale files', function () {
    $design = savedDesign($this->designer, $this->product);
    $payload = studioPayload(['editingDesignId' => $design->id, 'layout' => ['colorId' => 'white', 'sizeId' => 'standard', 'areas' => ['front' => ['objects' => []]]]]);
    unset($payload['files'], $payload['file_assets']);
    $oldFile = $design->design_payload['files'][0]['path'];

    $this->actingAs($this->designer)->post(route('designer.designs.store'), $payload, ['Accept' => 'application/json'])->assertCreated();

    expect($design->fresh()->design_payload['files'])->toBe([]);
    Storage::disk('local')->assertMissing($oldFile);
});

test('saving without an editing id still creates a new design', function () {
    savedDesign($this->designer, $this->product);

    $this->actingAs($this->designer)->post(route('designer.designs.store'), studioPayload(), ['Accept' => 'application/json'])->assertCreated();

    expect(Design::count())->toBe(2);
});
