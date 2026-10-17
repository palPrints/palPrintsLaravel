<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantValue;
use App\Support\PaperPrices;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** One pack price: every <count> sheets or pages cost <price>. */
function paperPack(int $count, float $price): array
{
    return ['count' => $count, 'price' => $price];
}

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleAndPermissionSeeder::class);

    $category = Category::create(['name' => 'ورق', 'slug' => 'paper']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);

    $color = Attribute::create(['name' => 'اللون', 'code' => 'color', 'data_type' => 'text', 'is_active' => true]);
    $size = Attribute::create(['name' => 'المقاس', 'code' => 'size', 'data_type' => 'text', 'is_active' => true]);
    $white = AttributeValue::create(['attribute_id' => $color->id, 'value' => 'أبيض', 'code' => 'white', 'sort_order' => 1]);
    $colorAxis = ProductAttribute::create(['product_id' => $this->product->id, 'attribute_id' => $color->id, 'is_variant_axis' => true, 'is_required' => true, 'sort_order' => 1]);
    $sizeAxis = ProductAttribute::create(['product_id' => $this->product->id, 'attribute_id' => $size->id, 'is_variant_axis' => true, 'is_required' => true, 'sort_order' => 2]);
    $whiteValue = ProductAttributeValue::create(['product_attribute_id' => $colorAxis->id, 'attribute_value_id' => $white->id, 'is_active' => true]);

    $this->variants = [];
    foreach (['a4' => 'A4', 'a5' => 'A5'] as $code => $label) {
        $value = AttributeValue::create(['attribute_id' => $size->id, 'value' => $label, 'code' => $code, 'sort_order' => 1]);
        $variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'PP-WHITE-'.$label, 'is_active' => true]);
        $sizeValue = ProductAttributeValue::create(['product_attribute_id' => $sizeAxis->id, 'attribute_value_id' => $value->id, 'is_active' => true]);
        VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $whiteValue->id]);
        VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $sizeValue->id]);
        $this->variants[$code] = $variant;
    }

    /** A live print shop that sells paper printing at the prices it is given. */
    $this->shop = function (string $name, array $paper, float $minimum = 1.0) {
        $owner = User::factory()->create(['is_active' => true]);
        $owner->assignRole('print_provider');
        $branch = $owner->printProvider()->create(['company_name' => $name, 'approval_status' => 'approved', 'is_active' => true])->primaryBranch();
        $offering = $branch->branchProductOfferings()->create([
            'product_id' => $this->product->id, 'base_price' => $minimum, 'currency' => 'ILS',
            'production_time_min' => 1, 'production_time_max' => 1, 'daily_capacity' => 50, 'is_active' => true,
        ]);
        foreach ($this->variants as $variant) {
            $offering->branchOfferingVariants()->create(['variant_id' => $variant->id, 'is_available' => true]);
        }
        $offering->refresh();
        PaperPrices::save($offering, collect($this->variants)->mapWithKeys(fn ($v, $code) => [$v->id => $code])->all(), $paper, ['a4', 'a5']);

        return $offering;
    };

    // A shop prices in packs: "every 100 sheets cost 20", "every 100 pages cost 9".
    $this->basic = [
        'types' => ['standard', 'thick'],
        'ink' => [
            'standard' => ['bw' => paperPack(100, 9), 'color' => paperPack(100, 42)],
            'thick' => ['bw' => paperPack(100, 12), 'color' => paperPack(100, 50)],
        ],
        'bindings' => ['spiral' => 8, 'sideStaple' => 1.5],
    ];

    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');

    /** One file of the job; every choice can be changed. */
    $this->job = fn (array $file = [], array $job = []) => $job + [
        'grouping' => 'combined', 'quantity' => 1, 'combined_binding' => 'none',
        'files' => [$file + ['pages' => 10, 'size' => 'A4', 'paper' => 'standard', 'color' => 'bw', 'sides' => 'single', 'layout' => 1, 'binding' => null, 'quantity' => 1]],
    ];

    $this->quote = fn (array $job) => $this->actingAs($this->customer)->postJson(route('customer.cart.quote-paper'), ['job' => $job]);
});

test('the price of a job is the shop\'s price per printed sheet, with the shop\'s minimum charge', function () {
    ($this->shop)('مطبعة النور', $this->basic, 0.5);

    // 10 sheets x 0.09 = 0.90, rounded up to a whole number for the customer
    ($this->quote)(($this->job)())->assertOk()->assertJsonPath('total', 1)->assertJsonPath('printing', 1)->assertJsonPath('binding', 0);

    // colour is dearer: 10 x 0.42 = 4.20 -> 5
    ($this->quote)(($this->job)(['color' => 'color']))->assertOk()->assertJsonPath('total', 5);

    // double sided, two pages per sheet side: 10 pages need 3 printed sheets = 3 x 0.42 = 1.26 -> 2
    ($this->quote)(($this->job)(['color' => 'color', 'sides' => 'double', 'layout' => 2]))->assertOk()->assertJsonPath('total', 2);

    // a binding is added once per copy, and the copies multiply the printing: 3 x 0.90 = 2.70 -> 3, plus 3 x 8
    ($this->quote)(($this->job)([], ['combined_binding' => 'spiral', 'quantity' => 3]))->assertOk()
        ->assertJsonPath('printing', 3)->assertJsonPath('binding', 24)->assertJsonPath('total', 27);

    // the least a customer pays for an order
    ($this->quote)(($this->job)(['pages' => 1]))->assertOk()->assertJsonPath('printing', 1)->assertJsonPath('total', 1);
});

test('files grouped separately are priced one by one with their own binding and quantity', function () {
    ($this->shop)('مطبعة النور', $this->basic, 1.0);

    $job = [
        'grouping' => 'separate', 'quantity' => 1, 'combined_binding' => null,
        'files' => [
            ['pages' => 10, 'size' => 'A4', 'paper' => 'standard', 'color' => 'bw', 'sides' => 'single', 'layout' => 1, 'binding' => 'spiral', 'quantity' => 2],
            ['pages' => 4, 'size' => 'A5', 'paper' => 'thick', 'color' => 'color', 'sides' => 'single', 'layout' => 1, 'binding' => 'none', 'quantity' => 1],
        ],
    ];

    // file 1: 10 x 0.09 x 2 = 1.80, binding 8 x 2 = 16; file 2 (thick, colour): 4 x 0.50 = 2.00
    ($this->quote)($job)->assertOk()
        ->assertJsonPath('lines.0.amount', 1.8)->assertJsonPath('lines.1.amount', 2)
        ->assertJsonPath('printing', 4)->assertJsonPath('binding', 16)->assertJsonPath('total', 20);
});

test('the cheapest shop that can make the job sets the price, and a price change by the shop changes it', function () {
    $dear = ($this->shop)('مطبعة الأمل', $this->basic, 0.1);
    ($this->shop)('مطبعة النور', array_replace_recursive($this->basic, ['ink' => ['standard' => ['bw' => paperPack(100, 6)]]]), 0.1);

    // 100 sheets x 0.06 = 6.00 at the cheaper shop (the other one asks 9.00)
    ($this->quote)(($this->job)(['pages' => 100]))->assertOk()->assertJsonPath('total', 6);

    // the other shop drops below it tomorrow
    PaperPrices::save($dear, collect($this->variants)->mapWithKeys(fn ($v, $code) => [$v->id => $code])->all(),
        array_replace_recursive($this->basic, ['ink' => ['standard' => ['bw' => paperPack(100, 4)]]]), ['a4', 'a5']);

    ($this->quote)(($this->job)(['pages' => 100]))->assertOk()->assertJsonPath('total', 4);
});

test('a job no shop can make is refused with a reason: a colour, paper type, size or binding nobody sells', function () {
    ($this->shop)('مطبعة النور', array_replace($this->basic, ['ink' => ['standard' => ['bw' => paperPack(100, 9)], 'thick' => ['bw' => paperPack(100, 12)]], 'bindings' => []]), 1.0);

    ($this->quote)(($this->job)(['color' => 'color']))->assertUnprocessable()->assertJsonValidationErrors('job');
    ($this->quote)(($this->job)(['paper' => 'coated']))->assertUnprocessable()->assertJsonValidationErrors('job');
    ($this->quote)(($this->job)([], ['combined_binding' => 'spiral']))->assertUnprocessable()->assertJsonValidationErrors('job');

    // things the platform does not sell at all
    ($this->quote)(($this->job)(['size' => 'A3']))->assertUnprocessable()->assertJsonValidationErrors('job');
    ($this->quote)(($this->job)(['paper' => 'gold']))->assertUnprocessable()->assertJsonValidationErrors('job');
    ($this->quote)(($this->job)(['pages' => 501]))->assertUnprocessable()->assertJsonValidationErrors('job');
});

test('with no shop selling paper there is nothing to print on', function () {
    ($this->quote)(($this->job)())->assertUnprocessable()->assertJsonValidationErrors('job');
});

test('the paper page offers only what the shops sell, and no price list', function () {
    ($this->shop)('مطبعة النور', array_replace($this->basic, ['types' => ['standard'], 'ink' => ['standard' => ['bw' => paperPack(100, 9)]], 'bindings' => ['spiral' => 8]]), 1.0);
    ($this->shop)('مطبعة الأمل', array_replace($this->basic, ['types' => ['standard', 'thick'], 'ink' => ['standard' => ['bw' => paperPack(100, 9), 'color' => paperPack(100, 42)], 'thick' => ['bw' => paperPack(100, 12)]], 'bindings' => ['spiral' => 6, 'glue' => 9]]), 1.0);

    $this->get(route('customer.paperPrinting'))->assertOk()->assertViewHas('paperPrinting', function (array $page) {
        $values = $page['options']['availableValues'];
        $bindings = collect($page['bindings'])->pluck('from', 'id')->all();

        $sorted = fn (array $list) => collect($list)->sort()->values()->all();

        return $page['available'] === true
            && $sorted($values['size']) === ['A4', 'A5']
            && $sorted($values['paper']) === ['standard', 'thick']
            && $sorted($values['color']) === ['bw', 'color']
            // the lowest price among the shops that sell each binding
            && $bindings === ['spiral' => 6.0, 'glue' => 9.0]
            && ! array_key_exists('pricing', $page);
    });
});

test('a shop that is switched off or not approved sells nothing on the page', function () {
    $offering = ($this->shop)('مطبعة النور', $this->basic, 1.0);
    $offering->update(['is_active' => false]);

    $this->get(route('customer.paperPrinting'))->assertOk()->assertViewHas('paperPrinting', fn (array $page) => $page['available'] === false && $page['options']['availableValues']['paper'] === []);
});

test('adding a paper job to the cart is priced by the server, whatever the browser says', function () {
    $offering = ($this->shop)('مطبعة النور', $this->basic, 1.0);
    $pdf = UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf');

    $this->actingAs($this->customer)->post(route('customer.cart.store-paper'), [
        'job' => json_encode(($this->job)([], ['combined_binding' => 'spiral'])),
        'total_price' => 0.01, 'size' => 'A4',
        'settings' => ['paper_type' => 'standard', 'color_mode' => 'bw'],
        'files' => [$pdf],
    ])->assertRedirect(route('customer.basket'));

    $item = CartItem::firstOrFail();
    expect((float) $item->unit_price)->toBe(9.0)->and($item->unit_price == (int) $item->unit_price)->toBeTrue()
        ->and($item->selected_options['paper_job']['combined_binding'])->toBe('spiral')
        ->and($item->selected_options['print_provider_branch_id'])->toBe($offering->print_provider_branch_id)
        ->and($item->selected_options['paper_size'])->toBe('A4')
        ->and($item->printFiles)->toHaveCount(1);
});

test('the job and the uploaded files must match, and a choice no shop sells adds nothing', function () {
    ($this->shop)('مطبعة النور', array_replace($this->basic, ['bindings' => []]), 1.0);
    $pdf = UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf');

    $this->actingAs($this->customer)->post(route('customer.cart.store-paper'), [
        'job' => json_encode(($this->job)()), 'files' => [$pdf, UploadedFile::fake()->create('more.pdf', 20, 'application/pdf')],
    ])->assertSessionHasErrors('job');

    $this->actingAs($this->customer)->post(route('customer.cart.store-paper'), [
        'job' => json_encode(($this->job)([], ['combined_binding' => 'spiral'])), 'files' => [$pdf],
    ])->assertSessionHasErrors('job');

    expect(CartItem::count())->toBe(0);
});

test('the shop sees a paper order file by file: pages, size, paper, colour, binding and copies', function () {
    $offering = ($this->shop)('مطبعة النور', $this->basic, 1.0);
    $owner = $offering->printProviderBranch->printProvider->user;

    $this->actingAs($this->customer)->post(route('customer.cart.store-paper'), [
        'job' => json_encode([
            'grouping' => 'separate', 'quantity' => 1, 'combined_binding' => null,
            'files' => [
                ['pages' => 10, 'size' => 'A4', 'paper' => 'thick', 'color' => 'color', 'sides' => 'double', 'layout' => 2, 'binding' => 'spiral', 'quantity' => 3],
                ['pages' => 4, 'size' => 'A5', 'paper' => 'standard', 'color' => 'bw', 'sides' => 'single', 'layout' => 1, 'binding' => 'none', 'quantity' => 1],
            ],
        ]),
        'files' => [UploadedFile::fake()->create('first.pdf', 20, 'application/pdf'), UploadedFile::fake()->create('second.pdf', 20, 'application/pdf')],
    ])->assertRedirect(route('customer.basket'));

    $item = CartItem::firstOrFail();
    $order = \App\Models\Order::create(['user_id' => $this->customer->id, 'order_number' => 'PAPER-1', 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 1, 'total_amount' => 1]);
    $orderItem = \App\Models\OrderItem::create([
        'order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $item->variant_id, 'item_type' => 'customer_upload',
        'print_provider_branch_id' => $offering->print_provider_branch_id, 'branch_product_offering_id' => $offering->id,
        'quantity' => 1, 'unit_price' => $item->unit_price, 'total_price' => $item->unit_price, 'provider_cost' => 1, 'selected_options' => $item->selected_options,
    ]);
    foreach (['first.pdf', 'second.pdf'] as $name) {
        \App\Models\PrintFile::create(['user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $orderItem->id, 'original_name' => $name,
            'stored_path' => 'customer-print-files/x/'.$name, 'disk' => 'local', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 1, 'page_count' => 1, 'status' => 'attached_to_order']);
    }

    $this->actingAs($owner)->get(route('print-provider.requests'))->assertViewHas('orders', function ($orders) {
        $item = $orders->first()['items'][0];
        $details = collect($item['details'])->pluck('value', 'label');
        $first = collect($item['paper_files'][0]['rows'])->pluck('value', 'label');
        $second =collect($item['paper_files'][1]['rows'])->pluck('value', 'label');

        return $item['paper_files'][0]['title'] === 'first.pdf' && str_contains($first['الورق'], 'فاخر 120 جم') && str_contains($first['الورق'], 'ملوّن')
            && str_contains($first['التغليف والنسخ'], 'سلك حلزوني') && str_contains($first['التغليف والنسخ'], 'النسخ: 3')
            && str_contains($second['التغليف والنسخ'], 'بدون تغليف') && str_contains($second['الورق'], 'A5')
            && ! $details->has('نوع الورق') && ! $details->has('الملف 1');
    });
});

test('the colour a shop prints depends on the paper: a choice that does not go with the others is not offered', function () {
    // thick paper only in black and white, standard paper in both
    ($this->shop)('مطبعة النور', array_replace($this->basic, ['ink' => ['standard' => ['bw' => paperPack(100, 9), 'color' => paperPack(100, 42)], 'thick' => ['bw' => paperPack(100, 12)]]]), 1.0);

    $this->get(route('customer.paperPrinting'))->assertOk()->assertViewHas('paperPrinting', function (array $page) {
        $combinations = collect($page['options']['combinations']);

        return $combinations->contains(['size' => 'A4', 'paper' => 'standard', 'color' => 'color'])
            && $combinations->contains(['size' => 'A5', 'paper' => 'thick', 'color' => 'bw'])
            && ! $combinations->contains(['size' => 'A4', 'paper' => 'thick', 'color' => 'color'])
            && $combinations->count() === 6;
    });

    ($this->quote)(($this->job)(['paper' => 'thick', 'color' => 'bw']))->assertOk();
    ($this->quote)(($this->job)(['paper' => 'thick', 'color' => 'color']))->assertUnprocessable()->assertJsonValidationErrors('job');
});

test('a pack price is the price of one sheet or page times how many are printed, so "10 for 5" and "100 for 50" are the same', function () {
    ($this->shop)('مطبعة النور', array_replace($this->basic, [
        'ink' => ['standard' => ['bw' => paperPack(10, 2), 'color' => paperPack(10, 8)], 'thick' => ['bw' => paperPack(10, 3), 'color' => paperPack(10, 9)]],
    ]), 0.5);

    // 7 sheets x (2/10) = 1.40, rounded up to 2
    ($this->quote)(($this->job)(['pages' => 7]))->assertOk()->assertJsonPath('total', 2);
});


test('only the pages in the range are printed and priced, and a wrong range is refused', function () {
    ($this->shop)('مطبعة النور', $this->basic, 0.1);

    // 100 pages, range "1-9, 12" = 10 pages x 0.09 = 0.90 -> 1
    ($this->quote)(($this->job)(['pages' => 100, 'range' => '1-9, 12']))->assertOk()->assertJsonPath('total', 1);

    // the whole file: 100 x 0.09 = 9
    ($this->quote)(($this->job)(['pages' => 100]))->assertOk()->assertJsonPath('total', 9);

    // Arabic digits and separators are understood, a repeated page counts once: 1-5 and 3-6 = 6 pages x 0.42 = 2.52 -> 3
    ($this->quote)(($this->job)(['pages' => 100, 'color' => 'color', 'range' => '١-٥،٣-٦']))->assertOk()->assertJsonPath('total', 3);

    foreach (['0-3', '5-2', '1-101', 'abc', '1,,2'] as $wrong) {
        ($this->quote)(($this->job)(['pages' => 100, 'range' => $wrong]))->assertUnprocessable()->assertJsonValidationErrors('job');
    }
});

test('a booklet is printed on both sides with four pages to a sheet, whatever else was chosen', function () {
    ($this->shop)('مطبعة النور', $this->basic, 0.1);

    // 20 pages in a booklet = 5 sheets x 0.42 = 2.10 -> 3, even though single sided one page per sheet was chosen
    ($this->quote)(($this->job)(['pages' => 20, 'color' => 'color', 'booklet' => true, 'sides' => 'single', 'layout' => 1]))->assertOk()->assertJsonPath('total', 3);

    // 21 pages need 6 sheets (a sheet holds four pages): 6 x 0.42 = 2.52 -> 3 ; without a booklet it would be 21 sheets = 8.82 -> 9
    ($this->quote)(($this->job)(['pages' => 21, 'color' => 'color', 'booklet' => true]))->assertOk()->assertJsonPath('total', 3);
    ($this->quote)(($this->job)(['pages' => 21, 'color' => 'color']))->assertOk()->assertJsonPath('total', 9);
});

test('the range, booklet, flip and the order of the copies reach the shop with the order', function () {
    $offering = ($this->shop)('مطبعة النور', $this->basic, 1.0);
    $owner = $offering->printProviderBranch->printProvider->user;

    $this->actingAs($this->customer)->post(route('customer.cart.store-paper'), [
        'job' => json_encode(($this->job)(['pages' => 40, 'range' => '1-9, 12', 'sides' => 'double', 'flip' => 'short'], ['quantity' => 3, 'collate' => false])),
        'files' => [UploadedFile::fake()->create('book.pdf', 20, 'application/pdf')],
    ])->assertRedirect(route('customer.basket'));

    $item = CartItem::firstOrFail();
    expect($item->selected_options['paper_job']['files'][0])->toMatchArray(['range' => '1-9, 12', 'printed' => 10, 'flip' => 'short', 'booklet' => false])
        ->and($item->selected_options['paper_job']['collate'])->toBeFalse();

    $order = \App\Models\Order::create(['user_id' => $this->customer->id, 'order_number' => 'PAPER-2', 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 1, 'total_amount' => 1]);
    $orderItem = \App\Models\OrderItem::create([
        'order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $item->variant_id, 'item_type' => 'customer_upload',
        'print_provider_branch_id' => $offering->print_provider_branch_id, 'branch_product_offering_id' => $offering->id,
        'quantity' => 1, 'unit_price' => $item->unit_price, 'total_price' => $item->unit_price, 'provider_cost' => 1, 'selected_options' => $item->selected_options,
    ]);
    \App\Models\PrintFile::create(['user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $orderItem->id, 'original_name' => 'book.pdf',
        'stored_path' => 'customer-print-files/x/book.pdf', 'disk' => 'local', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 1, 'page_count' => 40, 'status' => 'attached_to_order']);

    $this->actingAs($owner)->get(route('print-provider.requests'))->assertViewHas('orders', function ($orders) {
        $item = $orders->first()['items'][0];
        $details = collect($item['details'])->pluck('value', 'label');
        $first = collect($item['paper_files'][0]['rows'])->pluck('value', 'label');

        return str_contains($first['الصفحات'], '1-9, 12') && str_contains($first['الطباعة'], 'الطرف القصير')
            && str_contains($details['ترتيب النسخ'], 'كل صفحة بنسخها');
    });
});

test('the customer sees what was chosen for a paper order in my orders', function () {
    $offering = ($this->shop)('مطبعة النور', $this->basic, 1.0);

    $this->actingAs($this->customer)->post(route('customer.cart.store-paper'), [
        'job' => json_encode(($this->job)(['pages' => 40, 'range' => '1-9, 12', 'color' => 'color'], ['quantity' => 2])),
        'files' => [UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf')],
    ])->assertRedirect(route('customer.basket'));

    $item = CartItem::firstOrFail();
    $order = \App\Models\Order::create(['user_id' => $this->customer->id, 'order_number' => 'PAPER-3', 'status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => 'bank', 'subtotal' => 1, 'total_amount' => 1]);
    $orderItem = \App\Models\OrderItem::create([
        'order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $item->variant_id, 'item_type' => 'customer_upload',
        'print_provider_branch_id' => $offering->print_provider_branch_id, 'branch_product_offering_id' => $offering->id,
        'quantity' => 1, 'unit_price' => $item->unit_price, 'total_price' => $item->unit_price, 'provider_cost' => 1, 'selected_options' => $item->selected_options,
    ]);
    \App\Models\PrintFile::create(['user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $orderItem->id, 'original_name' => 'notes.pdf',
        'stored_path' => 'customer-print-files/x/notes.pdf', 'disk' => 'local', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 1, 'page_count' => 40, 'status' => 'attached_to_order']);

    $page = $this->actingAs($this->customer)->get(route('customer.orders'))->assertOk()->getContent();

    expect($page)->toContain('1-9, 12')->toContain('notes.pdf');
});

test('only the odd or only the even pages can be printed, and 6 or 9 pages can share a sheet', function () {
    ($this->shop)('مطبعة النور', $this->basic, 0.1);

    // 100 pages, only the odd ones = 50 sheets x 0.42 = 21
    ($this->quote)(($this->job)(['pages' => 100, 'color' => 'color', 'subset' => 'odd']))->assertOk()->assertJsonPath('total', 21);

    // the range 1-9 holds 5 odd pages (1,3,5,7,9) and 4 even ones: 5 x 0.42 = 2.10 -> 3 ; 4 x 0.42 = 1.68 -> 2
    ($this->quote)(($this->job)(['pages' => 100, 'color' => 'color', 'range' => '1-9', 'subset' => 'odd']))->assertOk()->assertJsonPath('total', 3);
    ($this->quote)(($this->job)(['pages' => 100, 'color' => 'color', 'range' => '1-9', 'subset' => 'even']))->assertOk()->assertJsonPath('total', 2);

    // a range with no page of the kind asked for is refused
    ($this->quote)(($this->job)(['pages' => 100, 'range' => '3', 'subset' => 'even']))->assertUnprocessable()->assertJsonValidationErrors('job');

    // 6 and 9 pages on a side: 54 pages on 9 a side = 6 sheets x 0.42 = 2.52 -> 3 ; on 6 a side = 9 sheets = 3.78 -> 4
    ($this->quote)(($this->job)(['pages' => 54, 'color' => 'color', 'layout' => 9]))->assertOk()->assertJsonPath('total', 3);
    ($this->quote)(($this->job)(['pages' => 54, 'color' => 'color', 'layout' => 6]))->assertOk()->assertJsonPath('total', 4);
    ($this->quote)(($this->job)(['pages' => 54, 'layout' => 5]))->assertUnprocessable();
});
