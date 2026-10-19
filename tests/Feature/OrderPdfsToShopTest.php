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
}

beforeEach(function () {
    Storage::fake('local');
    config(['telegram.enabled' => true, 'telegram.bot_token' => 'TESTTOKEN', 'whatsapp.enabled' => false]);
    $this->seed(RoleAndPermissionSeeder::class);
    $this->documents = new FakeOrderLineDocuments;
    $this->app->instance(OrderLineDocuments::class, $this->documents);

    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 200]])]);

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

    $this->shirtFiles = [
        PrintFile::EXPORT_DIR.'1/front--a.png' => 'print-ready-أمامي.png',
        PrintFile::EXPORT_DIR.'1/back--b.png' => 'print-ready-خلفي.png',
        'customer-print-files/orders/1/logo.ai' => 'logo.ai',
    ];

    $this->documentsSent = fn () => collect(Http::recorded())->map(fn ($pair) => $pair[0])
        ->filter(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendDocument'))
        ->map(fn (HttpRequest $r) => collect($r->data())->firstWhere('name', 'document'))
        ->map(fn ($part) => $part['filename'])->values()->all();
});

test('a shirt reaches the shop as the details PDF and one picture per side, named by the side', function () {
    ($this->line)($this->shirt, ['size' => 'L', 'color' => 'كحلي', 'print_areas' => ['أمامي', 'خلفي']], $this->shirtFiles);

    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    // the designer's original (logo.ai) is already inside the print-ready pictures, so it is not sent
    expect(($this->documentsSent)())->toBe(['PLP-PDF-1-1-details.pdf', 'أمامي.png', 'خلفي.png']);
    Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage') && isset($r['reply_markup'])
        && str_contains($r['text'], 'القياس: L · اللون: كحلي · مناطق الطباعة: أمامي، خلفي'));

    // sent once: a retried job sends nothing again
    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);
    expect(($this->documentsSent)())->toHaveCount(3);
});

test('a paper job gets the details PDF and the customer files under their own names', function () {
    ($this->line)($this->paper, ['paper_job' => ['grouping' => 'combined', 'files' => [['pages' => 2, 'size' => 'a4']]]], ['customer-print-files/p/thesis.pdf' => 'thesis.pdf']);

    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    expect(($this->documentsSent)())->toBe(['PLP-PDF-1-1-details.pdf', 'thesis.pdf']);
});

test('when the details PDF cannot be made the shop still gets the pictures', function () {
    $this->documents->broken = true;
    ($this->line)($this->shirt, ['size' => 'L'], $this->shirtFiles);

    SendOrderToTelegram::dispatchSync($this->order->id, $this->provider->id);

    expect(($this->documentsSent)())->toBe(['أمامي.png', 'خلفي.png']);
});

test('the WhatsApp message links the details PDF and each picture by its side', function () {
    $item = ($this->line)($this->shirt, ['size' => 'L', 'color' => 'كحلي'], $this->shirtFiles);

    $text = rawurldecode(explode('?text=', ShopChatLink::forOrder($this->order->fresh())['url'])[1]);
    expect($text)->toContain('القياس: L · اللون: كحلي')->toContain('تفاصيل الطلب (PDF)')
        ->toContain('🖼️ أمامي.png')->toContain('🖼️ خلفي.png')->not->toContain('logo.ai');

    preg_match('#https?://\S+/shared/order-items/'.$item->id.'/details\?\S+#', $text, $details);
    $this->get($details[0])->assertOk()->assertHeader('content-type', 'application/pdf')->assertDownload('PLP-PDF-1-1-details.pdf');
    $this->get($details[0].'x')->assertForbidden();
    $this->get(route('order-sheets.shared', ['item' => $item]))->assertForbidden();

    // the picture link downloads under the side's name
    preg_match('#https?://\S+/shared/print-files/\d+\?\S+#', $text, $picture);
    expect($this->get($picture[0])->assertOk()->headers->get('content-disposition'))->toContain("filename*=utf-8''".rawurlencode('أمامي.png'));
});

test('on the orders page each picture downloads under its side name', function () {
    $item = ($this->line)($this->shirt, ['size' => 'L'], $this->shirtFiles);
    $front = PrintFile::where('original_name', 'print-ready-أمامي.png')->firstOrFail();

    $line = collect($this->actingAs($this->owner)->get(route('print-provider.requests'))->viewData('orders'))->first()['items'][0];
    expect($line['sheet_url'])->not->toBeNull()->and(collect($line['print_files'])->pluck('name')->all())->toBe(['أمامي', 'خلفي']);

    expect($this->actingAs($this->owner)->get(route('print-provider.requests.files', [$this->order, $front]))->assertOk()->headers->get('content-disposition'))->toContain("filename*=utf-8''".rawurlencode('أمامي.png'));
    $this->actingAs($this->owner)->get(route('print-provider.requests.sheet', [$this->order, $item]))->assertOk()->assertDownload('PLP-PDF-1-1-details.pdf');
});

test('the real details PDF is made', function () {
    $this->app->forgetInstance(OrderLineDocuments::class);
    $item = ($this->line)($this->shirt, ['size' => 'L', 'color' => 'كحلي'], $this->shirtFiles);
    $item->load(['product', 'design', 'printFiles']);

    expect(app(OrderLineDocuments::class)->detailsPdf($this->order, $item))->toStartWith('%PDF');
})->skip(fn () => ! class_exists(\Mpdf\Mpdf::class), 'needs mPDF (composer install)');
