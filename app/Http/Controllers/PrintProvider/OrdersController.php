<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintFile;
use App\Services\PrintShopRouter;
use App\Support\CatalogProductData;
use App\Support\OrderNotifier;
use App\Support\OrderSheetPdf;
use App\Support\PaperJobDetails;
use App\Support\PaperPrices;
use App\Support\SimpleZip;
use App\Services\Orders\ShopOrderTransitions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The shop's orders page: every order that has at least one item routed to one of the shop's branches.
 * The shop decides on a new order (accept / reject) and then marks it ready for delivery; shipping and
 * completion stay with the platform. orders.status is shared by all items of an order.
 */
class OrdersController extends Controller
{
    /** orders.status => [tab key, label, CSS class]. */
    private const STATES = [
        'pending' => ['new', 'جديدة', 'is-new'],
        'processing' => ['new', 'جديدة', 'is-new'],
        'confirmed' => ['progress', 'قيد التنفيذ', 'is-progress'],
        'in_production' => ['progress', 'قيد التنفيذ', 'is-progress'],
        'ready' => ['ready', 'جاهزة للتسليم', 'is-ready'],
        'shipped' => ['ready', 'سُلّمت للشحن', 'is-ready'],
        'delivered' => ['completed', 'مكتملة', 'is-completed'],
        'completed' => ['completed', 'مكتملة', 'is-completed'],
        'rejected' => ['rejected', 'مرفوضة', 'is-rejected'],
        'cancelled' => ['rejected', 'ملغية', 'is-rejected'],
    ];

    /** Human labels for the options saved on an order item. */
    private const OPTION_LABELS = [
        'paper_size' => 'حجم الورق',
        'paper_type' => 'نوع الورق',
        'color_mode' => 'لون الطباعة',
        'sides' => 'جوانب الطباعة',
        'layout' => 'تخطيط الصفحة',
        'grouping' => 'طريقة الملفات',
        'binding' => 'التغليف',
        'printing_method_name' => 'تقنية الطباعة',
        'size' => 'القياس',
        'color' => 'اللون',
        'sticker_quantity' => 'عدد الملصقات',
        'sticker_page_count' => 'عدد صفحات A4',
    ];

    /** Human labels for the option values saved by the cart. */
    private const OPTION_VALUES = [
        'standard' => 'عادي 80 جم',
        'thick' => 'فاخر 120 جم',
        'coated' => 'مصقول 150 جم',
        'bw' => 'أبيض وأسود',
        'color' => 'ملون',
        'single' => 'وجه واحد',
        'double' => 'وجهين',
        '1' => 'صفحة واحدة لكل وجه',
        '2' => 'صفحتان لكل وجه',
        '4' => '4 صفحات لكل وجه',
        '6' => '6 صفحات لكل وجه',
        '9' => '9 صفحات لكل وجه',
        'combined' => 'دمج الملفات',
        'separate' => 'فصل الملفات',
        'none' => 'بدون تغليف',
    ];

    public function index(Request $request): View
    {
        $orders = $this->shopOrders($request)
            ->with(['payments', 'items' => fn ($items) => $this->shopItems($items, $request)->with(['product', 'design', 'printFiles'])])
            ->latest()
            ->limit(300)
            ->get()
            ->map(fn (Order $order) => $this->present($order));

        $count = fn (string $tab) => $orders->where('tab', $tab)->count();

        return view('printProvider.requests', [
            'orders' => $orders->values(),
            'counts' => [
                'new' => $count('new'),
                'progress' => $count('progress'),
                'ready' => $count('ready'),
                'completed' => $count('completed'),
            ],
        ]);
    }

    public function accept(Request $request, Order $order, ShopOrderTransitions $transitions): JsonResponse
    {
        $order = $this->ownedOrder($request, $order);

        return $this->respond($transitions->accept($order, $request->user()), 'تم قبول الطلب وبدأ تنفيذه.');
    }

    public function reject(Request $request, Order $order, ShopOrderTransitions $transitions): JsonResponse
    {
        $order = $this->ownedOrder($request, $order);
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        return $this->respond($transitions->reject($order, $request->user(), $data['reason']), 'تم رفض الطلب.');
    }

    public function ready(Request $request, Order $order, ShopOrderTransitions $transitions): JsonResponse
    {
        $order = $this->ownedOrder($request, $order);

        return $this->respond($transitions->ready($order, $request->user()), 'تم تحديد الطلب كجاهز للتسليم.');
    }

    /** Download one of the customer's print files — only for an item that was routed to this shop. */
    public function downloadFile(Request $request, Order $order, PrintFile $printFile): StreamedResponse
    {
        $order = $this->ownedOrder($request, $order);

        $item = $this->shopItems($order->items(), $request)->whereKey($printFile->order_item_id)->first();
        abort_if($item === null || $printFile->status === PrintFile::STATUS_DELETED, 404);

        $disk = Storage::disk($printFile->disk ?: 'local');
        abort_unless($disk->exists($printFile->stored_path), 404);

        return $disk->download($printFile->stored_path, $printFile->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * The pictures of one order line at full quality, in one zip: the studio's print-ready picture of every area
     * ("print-ready/front.png"...) and the customer's own files ("originals/"). The order's details are in the PDF (see sheet()).
     */
    public function package(Request $request, Order $order, OrderItem $item): BinaryFileResponse
    {
        $item = $this->shopItem($request, $order, $item);
        $disk = Storage::disk('local');
        $files = $this->lineFiles($item);

        // A studio order is described completely by its print-ready pictures; the customer's own uploads only matter when there are none.
        if ($files->contains->isPrintReady()) {
            $files = $files->filter->isPrintReady();
        }

        abort_if($files->isEmpty(), 404);

        $path = tempnam(sys_get_temp_dir(), 'pkg');
        $zip = new SimpleZip($path);
        $used = [];

        foreach ($files->sortBy('id') as $index => $file) {
            $folder = $file->isPrintReady() ? 'print-ready' : 'originals';
            $name = $file->isPrintReady()
                ? ($file->areaSlug() ?? 'area-'.($index + 1)).'.png'
                : $this->safeFileName($file->original_name, 'file-'.($index + 1).($file->extension ? '.'.$file->extension : ''));

            // Two files with one name would overwrite each other when unzipped.
            $entry = $folder.'/'.$name;
            for ($copy = 2; isset($used[$entry]); $copy++) {
                $entry = $folder.'/'.pathinfo($name, PATHINFO_FILENAME).'-'.$copy.($file->isPrintReady() ? '.png' : (pathinfo($name, PATHINFO_EXTENSION) ? '.'.pathinfo($name, PATHINFO_EXTENSION) : ''));
            }
            $used[$entry] = true;

            $zip->addFile($entry, $disk->path($file->stored_path));
        }

        $zip->close();

        return response()->download($path, 'print-'.$order->order_number.'-item-'.$item->id.'.zip', [
            'Content-Type' => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    /**
     * The order sheet of one line as a PDF: the specifications, the product with and without the design, the print
     * pictures and where every element sits.
     */
    public function sheet(Request $request, Order $order, OrderItem $item): BinaryFileResponse
    {
        $order = $this->ownedOrder($request, $order);
        $item = $this->shopItem($request, $order, $item);
        $disk = Storage::disk('local');
        $designFiles = $this->lineFiles($item)->filter->isPrintReady()->sortBy('id')
            ->mapWithKeys(fn (PrintFile $file) => [$file->areaLabel() => $disk->path($file->stored_path)])->all();
        $sizes = $this->designSizes($item);
        $areas = $this->lineFiles($item)->filter->isPrintReady()->sortBy('id')->map(fn (PrintFile $file) => [
            'name' => $file->areaLabel(),
            'file' => $file->original_name,
            'dimensions' => $sizes[$file->areaSlug()] ?? null,
            'size' => 0,
            'src' => '',
        ])->values()->all();
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, (new OrderSheetPdf)->render($this->sheetData($order, $item, $areas), $designFiles));

        return response()->download($path, 'order-'.$order->order_number.'-item-'.$item->id.'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    /** The files of a line that can be handed to the shop (not deleted, not a leftover draft, present on the disk). */
    private function lineFiles(OrderItem $item)
    {
        $disk = Storage::disk('local');

        return $item->printFiles->where('status', '!=', PrintFile::STATUS_DELETED)->reject->isDraftExport()
            ->filter(fn (PrintFile $file) => ($file->disk ?: 'local') === 'local' && $disk->exists($file->stored_path));
    }

    /**
     * How big the customer's design is on the product, per studio area, in centimetres ("11.6 × 10"): the box around all
     * its elements. The studio keeps sizes and positions as fractions of the print area.
     *
     * @return array<string, string>
     */
    private function designSizes(OrderItem $item): array
    {
        $product = $item->product;
        $router = app(PrintShopRouter::class);
        $definitions = $product ? collect(CatalogProductData::areaDefinitions($product->code)) : collect();
        $sizes = [];

        foreach ((array) data_get($item->selected_options, 'layout.areas', []) as $studioId => $area) {
            $zone = $product ? $definitions->firstWhere('code', $router->databaseArea($product, (string) $studioId)) : null;
            $objects = (array) ($area['objects'] ?? []);

            if (! $zone || $objects === []) {
                continue;
            }

            $edges = [];

            foreach ($objects as $object) {
                $isText = ($object['kind'] ?? '') === 'text';
                $width = (float) ($object['width'] ?? 0) * ($isText ? (float) ($object['scaleX'] ?? 1) : 1);
                $lines = max(1, substr_count((string) ($object['text'] ?? ''), "\n") + 1);
                $height = $isText
                    ? (float) ($object['fontSize'] ?? 0) * (float) ($object['scaleY'] ?? 1) * (float) ($object['lineHeight'] ?? 1.2) * $lines
                    : (float) ($object['height'] ?? 0);
                $edges[] = [(float) ($object['x'] ?? 0) - $width / 2, (float) ($object['x'] ?? 0) + $width / 2, (float) ($object['y'] ?? 0) - $height / 2, (float) ($object['y'] ?? 0) + $height / 2];
            }

            $edges = collect($edges);
            $width = ($edges->max(1) - $edges->min(0)) * $zone['width'] / 10;
            $height = ($edges->max(3) - $edges->min(2)) * $zone['height'] / 10;
            $sizes[strtolower((string) $studioId)] = round($width, 1).' × '.round($height, 1);
        }

        return $sizes;
    }

    /** @param  array<int, array{name: string, size: int, src: string}>  $areas  the print-ready pictures of the line, by area name */
    private function sheetData(Order $order, OrderItem $item, array $areas): array
    {
        $presented = $this->presentItem($order, $item);

        $fonts = collect(data_get($item->selected_options, 'layout.areas', []))
            ->flatMap(fn ($area) => collect($area['objects'] ?? [])->where('kind', 'text')->pluck('fontFamily'))
            ->filter()->unique()->values()->all();

        return [
            'order' => $order,
            'item' => $presented,
            'fonts' => $fonts,
            'packageUrl' => $presented['package_url'],
            'areas' => $areas,
            'previews' => $this->previews($item, $areas),
        ];
    }

    /**
     * The product itself in the customer's colour, once bare and once with the print-ready picture of the area placed in
     * its print zone. Orders placed before the studio saved a mockup per area show the one area that was saved.
     *
     * @param  array<int, array{name: string, size: int, src: string}>  $areas
     * @return array<int, array<string, mixed>>
     */
    private function previews(OrderItem $item, array $areas): array
    {
        $options = (array) $item->selected_options;
        $mockups = (array) ($options['mockups'] ?? []);
        $mockups = $mockups !== [] ? $mockups : array_filter([$options['mockup'] ?? null]);
        $previews = [];

        foreach ($mockups as $mockup) {
            $path = ltrim((string) ($mockup['image'] ?? ''), '/');

            if ($path === '' || ! is_file(public_path($path))) {
                continue;
            }

            $overlay = collect($areas)->firstWhere('name', $mockup['area_name'] ?? null);
            $previews[] = [
                'name' => (string) ($mockup['area_name'] ?? ''),
                'garment_path' => $path,
                'garment_src' => asset($path),
                'tint' => $mockup['tint'] ?? null,
                'zone' => (array) ($mockup['zone'] ?? []),
                'overlay_src' => $overlay['src'] ?? null,
            ];
        }

        return $previews;
    }

    /** An order line routed to this shop (a shop can never open a line of another shop). */
    private function shopItem(Request $request, Order $order, OrderItem $item): OrderItem
    {
        $order = $this->ownedOrder($request, $order);

        return $this->shopItems($order->items(), $request)->whereKey($item->id)->with(['product', 'design', 'printFiles'])->firstOrFail();
    }

    private function safeFileName(string $name, string $fallback): string
    {
        $clean = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1f]+/', '-', basename($name)));

        return $clean === '' || $clean === '.' || $clean === '..' ? $fallback : mb_substr($clean, 0, 120);
    }

    private function respond(bool $moved, string $message): JsonResponse
    {
        if (! $moved) {
            return response()->json(['message' => 'لا يمكن تنفيذ هذا الإجراء على الطلب بحالته الحالية.'], 422);
        }

        return response()->json(['message' => $message]);
    }

    private function providerBranchIds(Request $request)
    {
        return $request->user()->printProvider?->branches()->pluck('id') ?? collect();
    }

    /** Orders whose payment the admin approved and that hold at least one item for this shop. */
    private function shopOrders(Request $request)
    {
        $branchIds = $this->providerBranchIds($request);

        return Order::query()
            ->whereNotIn('payment_status', ['pending', 'pending_review', 'failed'])
            ->whereHas('items', fn ($items) => $items->whereIn('print_provider_branch_id', $branchIds));
    }

    private function shopItems($items, Request $request)
    {
        return $items->whereIn('print_provider_branch_id', $this->providerBranchIds($request));
    }

    private function ownedOrder(Request $request, Order $order): Order
    {
        return $this->shopOrders($request)->whereKey($order->id)->firstOrFail();
    }

    private function present(Order $order): array
    {
        [$tab, $label, $class] = self::STATES[$order->status] ?? ['new', $order->status, 'is-new'];

        $names = $order->items->map(fn (OrderItem $item) => $item->product?->name ?? 'منتج')->unique()->values();
        $waitingDays = $tab === 'new' ? (int) $order->reachedShopAt()->diffInDays(now()) : 0;

        return [
            'id' => $order->id,
            'number' => $order->order_number,
            'tab' => $tab,
            'status' => $order->status,
            'status_label' => $label,
            'status_class' => $class,
            'product' => $names->first() ?? 'منتج',
            'products_count' => $order->items->count(),
            'quantity' => (int) $order->items->sum('quantity'),
            'value' => (float) $order->items->sum('provider_cost'),
            'date' => $order->created_at->format('Y-m-d'),
            'date_label' => $order->created_at->translatedFormat('j F Y'),
            'alert' => $waitingDays >= 2 ? 'ينتظر ردك منذ '.$waitingDays.' أيام' : null,
            'notes' => $order->notes,
            'actions' => [
                'accept' => in_array($order->status, ShopOrderTransitions::NEW_STATUSES, true),
                'reject' => in_array($order->status, ShopOrderTransitions::NEW_STATUSES, true),
                'ready' => in_array($order->status, ShopOrderTransitions::PROGRESS_STATUSES, true),
            ],
            'items' => $order->items->map(fn (OrderItem $item) => $this->presentItem($order, $item))->values()->all(),
        ];
    }

    private function presentItem(Order $order, OrderItem $item): array
    {
        $options = collect($item->selected_options ?? []);

        $details = $options
            // A studio design saves its whole layout (nested arrays) under "layout", the paper option's name: not a plain option to list.
            ->filter(fn ($value, $key) => isset(self::OPTION_LABELS[$key]) && (is_scalar($value) || (is_array($value) && collect($value)->every(fn ($item) => is_scalar($item)))) && $value !== '' && $value !== [])
            ->map(fn ($value, $key) => [
                'label' => self::OPTION_LABELS[$key],
                'value' => collect((array) $value)->map(fn ($v) => self::OPTION_VALUES[(string) $v] ?? (string) $v)->implode('، '),
            ])
            ->values();

        $areas = $options->get('print_areas');
        if (is_array($areas) && $areas !== []) {
            $details->push(['label' => 'مناطق الطباعة', 'value' => implode('، ', array_map('strval', $areas))]);
        }

        // A paper job is described file by file (each file can have its own paper, colour, binding and copies).
        $paperFiles = [];
        $job = $options->get('paper_job');
        if (is_array($job) && ! empty($job['files'])) {
            $details = $details->reject(fn (array $row) => in_array($row['label'], ['حجم الورق', 'نوع الورق', 'لون الطباعة', 'جوانب الطباعة', 'تخطيط الصفحة', 'طريقة الملفات', 'التغليف'], true))->values();
            $jobFiles = $item->printFiles->where('status', '!=', PrintFile::STATUS_DELETED)->sortBy('id')->values();
            $names = $jobFiles->pluck('original_name');

            foreach (PaperJobDetails::rows($job) as $row) {
                $details->push($row);
            }

            // Each card carries the download of its own file (the cards follow the order of the files).
            $paperFiles = collect(PaperJobDetails::files($job, $names))
                ->map(fn (array $card, int $index) => $card + ['url' => isset($jobFiles[$index]) ? route('print-provider.requests.files', [$order, $jobFiles[$index]]) : null])
                ->all();
        }

        $image = $item->design?->image;
        $files = $item->printFiles->where('status', '!=', PrintFile::STATUS_DELETED)->reject->isDraftExport();

        return [
            'name' => $item->product?->name ?? 'منتج',
            'quantity' => (int) $item->quantity,
            'cost' => (float) $item->provider_cost,
            'type' => $item->item_type === OrderItem::TYPE_CUSTOMER_UPLOAD ? 'upload' : 'design',
            'design_title' => $item->design?->title,
            'image' => $image ? (str_starts_with($image, 'http') ? $image : asset($image)) : null,
            'details' => $details->all(),
            'paper_files' => $paperFiles,
            // The studio's print-ready pictures are listed apart from what the customer uploaded.
            'print_files' => $files->filter->isPrintReady()
                ->map(fn (PrintFile $file) => [
                    'name' => $file->areaLabel(),
                    'size' => $file->file_size,
                    'url' => route('print-provider.requests.files', [$order, $file]),
                ])->values()->all(),
            'files' => $files->reject->isPrintReady()
                ->map(fn (PrintFile $file) => [
                    'name' => $file->original_name,
                    'size' => $file->file_size,
                    'pages' => $file->page_count,
                    'url' => route('print-provider.requests.files', [$order, $file]),
                ])->values()->all(),
            'package_url' => $files->isNotEmpty() ? route('print-provider.requests.package', [$order, $item]) : null,
            'sheet_url' => route('print-provider.requests.sheet', [$order, $item]),
            'order_number' => $order->order_number,
            'date_label' => $order->reachedShopAt()->translatedFormat('j F Y'),
            'time_label' => $order->reachedShopAt()->translatedFormat('g:i A'),
            'notes' => $order->notes,
        ];
    }
}
