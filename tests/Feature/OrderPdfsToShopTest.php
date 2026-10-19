<?php

use App\Jobs\SendOrderToTelegram;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Services\Orders\OrderLineDocuments;
use App\Services\WhatsApp\ShopChatLink;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** Stands in for the PDF writer, so these tests check what is sent where, not how the PDF is drawn. */
class FakeOrderLineDocuments extends OrderLineDocuments
{
    public bool $broken = false;

    public function detailsPdf(Order $order, OrderItem $item): string
    {
        if ($this->broken) {
            throw new RuntimeException('no PDF writer');
        }

        return '%PDF-details-'.$item->id;
    }

    public function designPdf(Order $order, OrderItem $item): ?string
    {
        if ($this->broken) {
            throw new RuntimeException('no PDF writer');
        }

        return $this->hasDesign($item) ? '%PDF-design-'.$item->id : null;
    }
}

beforeEach(function () {
    Storage::fake('local');
    config(['telegram.enabled' => true, 'telegram.bot_token' => 'TESTTOKEN', 'whatsapp.enabled' => false]);
    $this->seed(RoleAndPermissionSeeder::class);
    $this->documents = new FakeOrderLineDocuments;
    $this->app->instance(OrderLineDocuments::class, $this->documents);

    Http::fake([
        'api.telegram.org/botTESTTOKEN/sendDocument' => Http::sequence()->push(['ok' => true, 'result' => ['message_id' => 101]])->push(['ok' => true, 'result' => ['message_id' => 102]])->push(['ok' => true, 'result' => ['message_id' => 103]])->push(['ok' => true, 'result' => ['message_id' => 104]]),
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 200]]),
    ]);

    $category = Category::create(['name' => 'ملابس', 'slug' => 'clothes']);
    $this->shirt = Product::create(['category_id' => $category->id, 'name' => 'تيشيرت كلاسيك', 'code' => 'TSHIRT-CLASSIC', 'is_active' => true]);
    $this->paper = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);
    $variant = Variant::create(['product_id' => $this->shirt->id, 'sku' => 'TS-L', 'is_active' => true]);

    $this->owner = User::factory()->create(['is_active' => true]);
    $this->owner->assignRole('print_provider');
    $this->provider = $this->owner->printProvider()->create(['company_name' => 'مطبعة النور', 'approval_status' => 'approved', 'is_active' => true, 'telegram_chat_id' => '555', 'whatsapp_number' => '970599123456', 'whatsapp_notifications_enabled' => true]);
    $branch = $this->provider->primaryBranch();
    $offering = $branch->branchProductOfferings()->create(['product_id' => $this->shirt->id, 'base_price' => 20, 'currency' => 'ILS', 'production_time_min' => 1, 'production_time_max' => 1, 'daily_capacity' => 5, 'is_active' => true]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $this->order = Order::create(['user_id' => $customer->id, 'order_number' => 'PLP-PDF-1', 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'palpay', 'subtotal' => 90, 'total_amount' => 90]);

    /** A line of the order; files: [stored path => original name]. */
    $this->line = function (Product $product, array $options, array $files) use ($variant, $branch, $offering, $customer): OrderItem {
        $item = OrderItem::create([
            'order_id' => $this->order->id, 'product_id' => $product->id, 'variant_id' => $variant->id, 'item_type' => 'customer_upload',
            'print_provider_branch_id' => $branch->id, 'branch_product_offering_id' => $offering->id,
            'quantity' => 3, 'unit_price' => 30, 'total_price' => 90, 'provider_cost' => 60, 'selected_options' => $options,
        ]);
        foreach ($files as $path => $name) {
            Storage::disk('local')->put($path, 'X');
            PrintFile::create(['user_id' => $customer->id, 'product_id' => $product->id, 'order_item_id' => $item->id, 'original_name' => $name,
                'stored_path' => $path, 'disk' => 'local', 'mime_type' => 'image/png', 'extension' => pathinfo($path, PATHINFO_EXTENSION), 'file_size' => 1, 'status' => 'attached_to_order']);
        }

        return $item;
    };

    $this->documentsSent = fn () => collect(Http::recorded())->map(fn ($pair) => $pair[0])
        ->filter(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendDocument'))
        ->map(fn (HttpRequest $r) => collect($r->data())->firstWhere('name', 'document'))
        ->map(fn ($part) => ['name' => $part['filename'], 'body' => (string) $part['contents']])->values()->all();
});

test('a shirt reaches the shop as two PDFs, details and design, with its size and colour in the message', function () {
    $item = ($this->line)($this->shirt, ['size' => 'L', 'color' => 'كحلي', 'print_areas' => ['أمامي']], [PrintFile::EXPORT_DIR.'1/front--a.png' => 'print-ready-أمامي.png']);

    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    expect(($this->documentsSent)())->toBe([
        ['name' => 'PLP-PDF-1-1-details.pdf', 'body' => '%PDF-details-'.$item->id],
        ['name' => 'PLP-PDF-1-1-design.pdf', 'body' => '%PDF-design-'.$item->id],
    ]);
    Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage') && isset($r['reply_markup'])
        && str_contains($r['text'], 'القياس: L · اللون: كحلي · مناطق الطباعة: أمامي')
        && str_contains($r['text'], 'ملف التفاصيل وملف التصميم'));

    // sent once: a retried job does not repeat the PDFs
    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);
    expect(($this->documentsSent)())->toHaveCount(2);
});

test('a paper job gets the details PDF and the customer files, and no design PDF', function () {
    ($this->line)($this->paper, ['paper_job' => ['grouping' => 'combined', 'files' => [['pages' => 2, 'size' => 'a4']]]], ['customer-print-files/p/thesis.pdf' => 'thesis.pdf']);

    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    expect(collect(($this->documentsSent)())->pluck('name')->all())->toBe(['PLP-PDF-1-1-details.pdf', 'thesis.pdf']);
});

test('when the PDFs cannot be made the shop still gets the files themselves', function () {
    $this->documents->broken = true;
    ($this->line)($this->shirt, ['size' => 'L'], [PrintFile::EXPORT_DIR.'1/front--a.png' => 'print-ready-أمامي.png']);

    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    expect(collect(($this->documentsSent)())->pluck('name')->all())->toBe(['print-ready-أمامي.png']);
});

test('the WhatsApp message links both PDFs, which open without logging in', function () {
    $item = ($this->line)($this->shirt, ['size' => 'L', 'color' => 'كحلي'], [PrintFile::EXPORT_DIR.'1/front--a.png' => 'print-ready-أمامي.png']);

    $text = rawurldecode(explode('?text=', ShopChatLink::forOrder($this->order->fresh())['url'])[1]);
    expect($text)->toContain('القياس: L · اللون: كحلي')->toContain('تفاصيل الطلب (PDF)')->toContain('التصميم والمنتج عليه (PDF)')
        ->not->toContain('print-ready-أمامي.png');

    preg_match_all('#https?://\S+/shared/order-items/'.$item->id.'/(details|design)\?\S+#', $text, $links);
    expect($links[1])->toBe(['details', 'design']);

    $this->get($links[0][0])->assertOk()->assertHeader('content-type', 'application/pdf')->assertDownload('PLP-PDF-1-1-details.pdf');
    $this->get($links[0][1])->assertOk()->assertDownload('PLP-PDF-1-1-design.pdf');
    $this->get($links[0][0].'x')->assertForbidden();
    $this->get(route('order-sheets.shared', ['item' => $item, 'kind' => 'details']))->assertForbidden();
});

test('the orders page offers both PDFs; a paper line has no design PDF', function () {
    $shirt = ($this->line)($this->shirt, ['size' => 'L'], [PrintFile::EXPORT_DIR.'1/front--a.png' => 'print-ready-أمامي.png']);
    $paper = ($this->line)($this->paper, ['paper_job' => ['grouping' => 'combined', 'files' => [['pages' => 2, 'size' => 'a4']]]], ['customer-print-files/p/thesis.pdf' => 'thesis.pdf']);

    $items = collect($this->actingAs($this->owner)->get(route('print-provider.requests'))->viewData('orders'))->first()['items'];
    expect($items[0]['sheet_url'])->not->toBeNull()->and($items[0]['design_url'])->not->toBeNull()
        ->and($items[1]['sheet_url'])->not->toBeNull()->and($items[1]['design_url'])->toBeNull();

    $this->actingAs($this->owner)->get(route('print-provider.requests.design', [$this->order, $shirt]))->assertOk()->assertDownload('PLP-PDF-1-1-design.pdf');
    $this->actingAs($this->owner)->get(route('print-provider.requests.design', [$this->order, $paper]))->assertNotFound();
});

test('the real PDFs are made: details and design', function () {
    $this->app->forgetInstance(OrderLineDocuments::class);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $item = ($this->line)($this->shirt, ['size' => 'L', 'color' => 'كحلي'], []);
    Storage::disk('local')->put(PrintFile::EXPORT_DIR.'1/front--a.png', $png);
    PrintFile::create(['user_id' => $this->order->user_id, 'product_id' => $this->shirt->id, 'order_item_id' => $item->id, 'original_name' => 'print-ready-أمامي.png',
        'stored_path' => PrintFile::EXPORT_DIR.'1/front--a.png', 'disk' => 'local', 'mime_type' => 'image/png', 'extension' => 'png', 'file_size' => strlen($png), 'status' => 'attached_to_order']);

    $documents = app(OrderLineDocuments::class);
    $item->load(['product', 'design', 'printFiles']);
    expect($documents->detailsPdf($this->order, $item))->toStartWith('%PDF')
        ->and($documents->designPdf($this->order, $item))->toStartWith('%PDF');
})->skip(fn () => ! class_exists(\Mpdf\Mpdf::class) || ! extension_loaded('gd'), 'needs mPDF (composer install) and the GD extension');
