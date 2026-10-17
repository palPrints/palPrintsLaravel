<?php

use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Storage;

function ordersShop(string $name): array
{
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $provider = $owner->printProvider()->create(['company_name' => $name, 'approval_status' => 'approved', 'is_active' => true]);

    return [$owner, $provider->primaryBranch()];
}

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleAndPermissionSeeder::class);

    $category = Category::create(['name' => 'ورق', 'slug' => 'paper']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);
    $this->variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'PP-A4', 'is_active' => true]);

    [$this->owner, $this->branch] = ordersShop('مطبعة النور');
    [$this->otherOwner, $this->otherBranch] = ordersShop('مطبعة الأمل');

    foreach ([$this->branch, $this->otherBranch] as $branch) {
        $branch->branchProductOfferings()->create([
            'product_id' => $this->product->id, 'base_price' => 20, 'currency' => 'ILS',
            'production_time_min' => 1, 'production_time_max' => 1, 'daily_capacity' => 5, 'is_active' => true,
        ]);
    }

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');

    $this->makeOrder = function (string $number, string $status, $branch = null, string $payment = 'paid') {
        $order = Order::create([
            'user_id' => $this->customer->id, 'order_number' => $number, 'status' => $status,
            'payment_status' => $payment, 'payment_method' => 'bank', 'subtotal' => 50, 'total_amount' => 50,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id,
            'item_type' => 'customer_upload', 'print_provider_branch_id' => ($branch ?? $this->branch)->id,
            'branch_product_offering_id' => ($branch ?? $this->branch)->branchProductOfferings()->first()->id,
            'quantity' => 2, 'unit_price' => 25, 'total_price' => 50, 'provider_cost' => 20,
            'selected_options' => ['paper_size' => 'A4', 'color_mode' => 'bw'],
        ]);

        return [$order, $item];
    };
});

test('the page lists only paid orders routed to this shop, from the database', function () {
    ($this->makeOrder)('MINE-1', 'processing');
    ($this->makeOrder)('THEIRS-1', 'processing', $this->otherBranch);
    ($this->makeOrder)('UNPAID-1', 'awaiting_payment_review', null, 'pending_review');

    $this->actingAs($this->owner)
        ->get(route('print-provider.requests'))
        ->assertOk()
        ->assertSee('MINE-1')
        ->assertDontSee('THEIRS-1')
        ->assertDontSee('UNPAID-1');
});

/** Reads the entries (name => contents) of a stored zip, without the zip extension: checks sizes and CRCs too. */
function readZip(string $path): array
{
    $data = file_get_contents($path);
    $end = unpack('Vsig/vdisk/vdiskCd/vcountDisk/vcount/VcdSize/VcdOffset', substr($data, strrpos($data, "PK\x05\x06")));
    expect($end['sig'])->toBe(0x06054B50);

    $entries = [];
    $position = $end['cdOffset'];

    for ($i = 0; $i < $end['count']; $i++) {
        $cd = unpack('Vsig/vmade/vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcomp/Vsize/vnameLen/vextraLen/vcommentLen/vdisk/vinternal/Vexternal/Voffset', substr($data, $position, 46));
        $name = substr($data, $position + 46, $cd['nameLen']);
        $local = unpack('Vsig/vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcomp/Vsize/vnameLen/vextraLen', substr($data, $cd['offset'], 30));
        $body = substr($data, $cd['offset'] + 30 + $local['nameLen'] + $local['extraLen'], $cd['size']);

        expect($cd['sig'])->toBe(0x02014B50)->and($local['sig'])->toBe(0x04034B50)
            ->and(hexdec(hash('crc32b', $body)))->toBe($cd['crc'])->and(strlen($body))->toBe($cd['size']);

        $entries[$name] = $body;
        $position += 46 + $cd['nameLen'] + $cd['extraLen'] + $cd['commentLen'];
    }

    return $entries;
}

test('the shop downloads one package per order line: area pictures by name, and the customers files apart', function () {
    [$order, $item] = ($this->makeOrder)('PKG-1', 'processing');

    foreach ([
        ['stored' => PrintFile::EXPORT_DIR.$this->customer->id.'/front--aaa.png', 'name' => 'print-ready-أمامي.png', 'body' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')],
        ['stored' => PrintFile::EXPORT_DIR.$this->customer->id.'/sleeve_left--bbb.png', 'name' => 'print-ready-الكم الأيسر.png', 'body' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')],
        ['stored' => 'customer-print-files/orders/1/zzz.jpg', 'name' => 'صورتي/المفضلة.jpg', 'body' => 'MY-JPG'],
    ] as $row) {
        Storage::disk('local')->put($row['stored'], $row['body']);
        PrintFile::create([
            'user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $item->id,
            'original_name' => $row['name'], 'stored_path' => $row['stored'], 'disk' => 'local', 'mime_type' => 'image/png',
            'extension' => pathinfo($row['stored'], PATHINFO_EXTENSION), 'file_size' => strlen($row['body']), 'page_count' => 1,
            'status' => PrintFile::STATUS_ATTACHED_TO_ORDER,
        ]);
    }

    // The zip holds the print-ready pictures at full quality; the customer's own upload is already inside the design.
    $response = $this->actingAs($this->owner)->get(route('print-provider.requests.package', [$order, $item]))->assertOk();
    $entries = readZip($response->baseResponse->getFile()->getPathname());

    expect(array_keys($entries))->toEqualCanonicalizing(['print-ready/front.png', 'print-ready/sleeve_left.png']);

    // The orders page lists the pictures apart from the originals and links the package and the sheet.
    $this->actingAs($this->owner)->get(route('print-provider.requests'))->assertViewHas('orders', function ($orders) {
        $line = $orders->first()['items'][0];

        return count($line['print_files']) === 2 && count($line['files']) === 1
            && $line['print_files'][0]['name'] === 'أمامي' && $line['package_url'] !== null && $line['sheet_url'] !== null;
    });

    // The sheet is one PDF.
    $sheet = $this->actingAs($this->owner)->get(route('print-provider.requests.sheet', [$order, $item]))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(file_get_contents($sheet->baseResponse->getFile()->getPathname()))->toStartWith('%PDF');
});

test('a shop cannot open the package or the sheet of another shops order line, nor one that is not approved', function () {
    [$theirs, $theirItem] = ($this->makeOrder)('THEIRS-PKG', 'processing', $this->otherBranch);
    [$unpaid, $unpaidItem] = ($this->makeOrder)('UNPAID-PKG', 'awaiting_payment_review', null, 'pending_review');
    [$mine] = ($this->makeOrder)('MINE-PKG', 'processing');

    $this->actingAs($this->owner)->get(route('print-provider.requests.package', [$theirs, $theirItem]))->assertNotFound();
    $this->actingAs($this->owner)->get(route('print-provider.requests.sheet', [$theirs, $theirItem]))->assertNotFound();
    $this->actingAs($this->owner)->get(route('print-provider.requests.sheet', [$unpaid, $unpaidItem]))->assertNotFound();
    // my order, but the line of someone else's order
    $this->actingAs($this->owner)->get(route('print-provider.requests.sheet', [$mine, $theirItem]))->assertNotFound();
    $this->actingAs($this->customer)->get(route('print-provider.requests.sheet', [$mine, $theirItem]))->assertForbidden();
});

test('an order placed days ago but approved just now is not shown as waiting for days', function () {
    [$late] = ($this->makeOrder)('LATE-1', 'processing');
    [$old] = ($this->makeOrder)('OLD-1', 'processing');

    foreach ([$late, $old] as $order) {
        $order->forceFill(['created_at' => now()->subDays(6)])->save();
    }

    // LATE-1: the admin approved it an hour ago. OLD-1: the shop has really left it for 4 days.
    Payment::create(['order_id' => $late->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-L1', 'method' => 'bank', 'amount' => 50, 'status' => 'paid', 'paid_at' => now()->subHour()]);
    Payment::create(['order_id' => $old->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-O1', 'method' => 'bank', 'amount' => 50, 'status' => 'paid', 'paid_at' => now()->subDays(4)]);

    $this->actingAs($this->owner)->get(route('print-provider.dashboard'))
        ->assertOk()
        ->assertSee('OLD-1')
        ->assertSee('ينتظر ردك منذ 4 أيام')
        ->assertDontSee('LATE-1 ينتظر');

    expect($late->fresh()->load('payments')->reachedShopAt()->diffInHours(now()))->toBeLessThan(2);
});

test('the sidebar counts the orders waiting for the shop to accept or reject', function () {
    ($this->makeOrder)('NEW-1', 'processing');
    ($this->makeOrder)('NEW-2', 'processing');
    ($this->makeOrder)('DONE-1', 'confirmed');
    ($this->makeOrder)('THEIRS-1', 'processing', $this->otherBranch);

    $this->actingAs($this->owner)->get(route('print-provider.dashboard'))
        ->assertOk()
        ->assertSee('2 طلبات جديدة بانتظار ردك');
});

test('the new-order notice keeps the order number and the count readable in Arabic', function () {
    \App\Support\OrderNotifier::newOrderForShop($this->owner->id, 'PLP-1', 3);

    $message = Notification::where('user_id', $this->owner->id)->sole()->message;

    expect($message)->toContain("\u{2066}PLP-1\u{2069}")->and($message)->toContain("\u{2066}3\u{2069} منتج");
});

test('an order the admin just approved is new for the shop, which can accept it and then mark it ready', function () {
    [$order] = ($this->makeOrder)('FLOW-1', 'processing');

    $this->actingAs($this->owner)->get(route('print-provider.requests'))
        ->assertViewHas('counts', fn ($counts) => $counts['new'] === 1 && $counts['progress'] === 0);

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertOk();
    expect($order->fresh()->status)->toBe('confirmed');

    // accepting twice is refused, and it cannot be marked ready before it is accepted
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertStatus(422);

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.ready', $order))->assertOk();
    expect($order->fresh()->status)->toBe('ready');

    $this->assertDatabaseHas('order_status_history', ['order_id' => $order->id, 'from_status' => 'processing', 'to_status' => 'confirmed', 'changed_by' => $this->owner->id]);
    expect(Notification::where('user_id', $this->customer->id)->where('type', 'order.confirmed')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.ready')->exists())->toBeTrue();
});

test('rejecting needs a reason, tells the customer and the admins, and the order leaves the new tab', function () {
    [$order] = ($this->makeOrder)('REJ-1', 'processing');

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.reject', $order), [])->assertUnprocessable();
    expect($order->fresh()->status)->toBe('processing');

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.reject', $order), ['reason' => 'لا يتوفر ورق'])->assertOk();

    expect($order->fresh()->status)->toBe('rejected')
        ->and(Notification::where('user_id', $this->customer->id)->where('type', 'order.rejected')->first()->message)->toContain('لا يتوفر ورق')
        ->and(Notification::where('user_id', $this->admin->id)->where('type', 'order.rejected_by_provider')->first()->message)->toContain('مطبعة النور', 'REJ-1', 'لا يتوفر ورق');

    // a rejected order cannot be accepted afterwards
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertStatus(422);
});

test('a shop cannot act on another shops order', function () {
    [$order] = ($this->makeOrder)('THEIRS-2', 'processing', $this->otherBranch);

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertNotFound();
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.reject', $order), ['reason' => 'xxx'])->assertNotFound();
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.ready', $order))->assertNotFound();
    expect($order->fresh()->status)->toBe('processing');
});

test('an order whose payment is not approved is hidden from the shop and cannot be accepted', function () {
    [$order] = ($this->makeOrder)('UNPAID-2', 'awaiting_payment_review', null, 'pending_review');

    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertNotFound();
});

test('customers and designers cannot use the shop orders routes', function () {
    [$order] = ($this->makeOrder)('ROLE-1', 'processing');

    $this->actingAs($this->customer)->get(route('print-provider.requests'))->assertForbidden();
    $this->actingAs($this->customer)->postJson(route('print-provider.requests.accept', $order))->assertForbidden();
});

test('the shop downloads the customer files of its own orders only', function () {
    [$order, $item] = ($this->makeOrder)('FILE-1', 'processing');
    Storage::disk('local')->put('customer-print-files/demo/a.pdf', '%PDF-1.4 test');
    $file = PrintFile::create([
        'user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $item->id,
        'original_name' => 'عقد.pdf', 'stored_path' => 'customer-print-files/demo/a.pdf', 'disk' => 'local',
        'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 13, 'status' => 'attached_to_order',
    ]);

    $this->actingAs($this->owner)
        ->get(route('print-provider.requests.files', [$order, $file]))
        ->assertOk()
        ->assertHeader('content-disposition', "attachment; filename=aakd.pdf; filename*=utf-8''".rawurlencode('عقد.pdf'));

    $this->actingAs($this->otherOwner)->get(route('print-provider.requests.files', [$order, $file]))->assertNotFound();

    // a file of another order cannot be fetched through this order's URL
    [$otherOrder] = ($this->makeOrder)('FILE-2', 'processing');
    $this->actingAs($this->owner)->get(route('print-provider.requests.files', [$otherOrder, $file]))->assertNotFound();
});

test('payment approval by the admin sends the order to the shop as a new order', function () {
    $order = Order::create([
        'user_id' => $this->customer->id, 'order_number' => 'APPROVE-1', 'status' => 'awaiting_payment_review',
        'payment_status' => 'pending_review', 'payment_method' => 'bank', 'subtotal' => 50, 'total_amount' => 50,
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id,
        'item_type' => 'customer_upload', 'print_provider_branch_id' => $this->otherBranch->id,
        'branch_product_offering_id' => $this->otherBranch->branchProductOfferings()->first()->id,
        'quantity' => 1, 'unit_price' => 25, 'total_price' => 25, 'provider_cost' => 20,
    ]);
    $payment = Payment::create([
        'order_id' => $order->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-A1',
        'status' => 'pending_review', 'method' => 'bank', 'amount' => 50, 'gateway_payload' => ['mode' => 'manual_receipt_review'],
    ]);

    $this->actingAs($this->owner)->get(route('print-provider.requests'))->assertDontSee('APPROVE-1');

    $this->actingAs($this->admin)
        ->post(route('admin.payment-notices.approve', $payment), ['branch_id' => $this->branch->id])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->owner)->get(route('print-provider.requests'))
        ->assertOk()
        ->assertSee('APPROVE-1')
        ->assertViewHas('orders', fn ($orders) => $orders->firstWhere('number', 'APPROVE-1')['actions']['accept'] === true);

    // the shop that was not chosen sees nothing
    $this->actingAs($this->otherOwner)->get(route('print-provider.requests'))->assertDontSee('APPROVE-1');
});

test('full journey: customer file, admin approval, shop notice opens the order, shop gets the file, accepts and finishes', function () {
    $order = Order::create([
        'user_id' => $this->customer->id, 'order_number' => 'JOURNEY-1', 'status' => 'awaiting_payment_review',
        'payment_status' => 'pending_review', 'payment_method' => 'bank', 'subtotal' => 50, 'total_amount' => 50,
    ]);
    $item = OrderItem::create([
        'order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id,
        'item_type' => 'customer_upload', 'print_provider_branch_id' => $this->branch->id,
        'branch_product_offering_id' => $this->branch->branchProductOfferings()->first()->id,
        'quantity' => 2, 'unit_price' => 25, 'total_price' => 50, 'provider_cost' => 20,
    ]);
    Storage::disk('local')->put('customer-print-files/journey/a.pdf', '%PDF-1.4 journey');
    $file = PrintFile::create([
        'user_id' => $this->customer->id, 'product_id' => $this->product->id, 'order_item_id' => $item->id,
        'original_name' => 'ملفي.pdf', 'stored_path' => 'customer-print-files/journey/a.pdf', 'disk' => 'local',
        'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 16, 'status' => PrintFile::STATUS_ATTACHED_TO_ORDER,
    ]);
    $payment = Payment::create([
        'order_id' => $order->id, 'user_id' => $this->customer->id, 'payment_number' => 'PAY-J1',
        'status' => 'pending_review', 'method' => 'bank', 'amount' => 50, 'gateway_payload' => ['mode' => 'manual_receipt_review'],
    ]);

    // before approval the shop sees nothing and cannot get the file
    $this->actingAs($this->owner)->get(route('print-provider.requests'))->assertDontSee('JOURNEY-1');
    $this->actingAs($this->owner)->get(route('print-provider.requests.files', [$order, $file]))->assertNotFound();

    $this->actingAs($this->admin)
        ->post(route('admin.payment-notices.approve', $payment), ['branch_id' => $this->branch->id])
        ->assertSessionHasNoErrors();

    // the notice links straight to this order, and following it lists the order with its file for the dialog
    $notice = Notification::where('user_id', $this->owner->id)->where('type', 'order.new_for_provider')->sole();
    expect($notice->link)->toBe(route('print-provider.requests', ['order' => $order->id]));

    $this->actingAs($this->owner)->get($notice->link)
        ->assertOk()
        ->assertViewHas('orders', function ($orders) use ($order) {
            $row = $orders->firstWhere('id', $order->id);

            return $row['number'] === 'JOURNEY-1' && count($row['items'][0]['files']) === 1;
        });

    // the shop downloads the customer's file, the other shop cannot
    $this->actingAs($this->owner)->get(route('print-provider.requests.files', [$order, $file]))->assertOk();
    $this->actingAs($this->otherOwner)->get(route('print-provider.requests.files', [$order, $file]))->assertNotFound();

    // accept, then ready; the customer is told at each step
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.accept', $order))->assertOk();
    $this->actingAs($this->owner)->postJson(route('print-provider.requests.ready', $order))->assertOk();
    expect($order->fresh()->status)->toBe('ready')
        ->and(Notification::where('user_id', $this->customer->id)->whereIn('type', ['order.confirmed', 'order.ready'])->count())->toBe(2);
});
