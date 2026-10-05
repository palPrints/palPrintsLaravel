<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PrintFile;
use App\Support\OrderNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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

    /** The admin approving the payment leaves the order "processing": it is new to the shop until the shop accepts it. */
    private const NEW_STATUSES = ['pending', 'processing'];

    private const PROGRESS_STATUSES = ['confirmed', 'in_production'];

    /** Human labels for the options saved on an order item. */
    private const OPTION_LABELS = [
        'paper_size' => 'حجم الورق',
        'paper_type' => 'نوع الورق',
        'color_mode' => 'لون الطباعة',
        'sides' => 'جوانب الطباعة',
        'layout' => 'تخطيط الصفحة',
        'grouping' => 'طريقة الملفات',
        'binding' => 'التغليف',
        'size' => 'القياس',
        'color' => 'اللون',
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
        'combined' => 'دمج الملفات',
        'separate' => 'فصل الملفات',
        'none' => 'بدون تغليف',
    ];

    public function index(Request $request): View
    {
        $orders = $this->shopOrders($request)
            ->with(['items' => fn ($items) => $this->shopItems($items, $request)->with(['product', 'design', 'printFiles'])])
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

    public function accept(Request $request, Order $order): JsonResponse
    {
        $order = $this->ownedOrder($request, $order);

        return $this->transition($request, $order, self::NEW_STATUSES, 'confirmed', 'تم قبول الطلب وبدأ تنفيذه.');
    }

    public function reject(Request $request, Order $order): JsonResponse
    {
        $order = $this->ownedOrder($request, $order);
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        return $this->transition($request, $order, self::NEW_STATUSES, 'rejected', 'تم رفض الطلب.', $data['reason']);
    }

    public function ready(Request $request, Order $order): JsonResponse
    {
        $order = $this->ownedOrder($request, $order);

        return $this->transition($request, $order, self::PROGRESS_STATUSES, 'ready', 'تم تحديد الطلب كجاهز للتسليم.');
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

    private function transition(Request $request, Order $order, array $from, string $to, string $message, ?string $note = null): JsonResponse
    {
        $previous = $order->status;

        // One conditional update, so a double click (or two people) cannot move the same order twice.
        $moved = Order::query()->whereKey($order->id)->whereIn('status', $from)->update(['status' => $to, 'updated_at' => now()]);

        if ($moved === 0) {
            return response()->json(['message' => 'لا يمكن تنفيذ هذا الإجراء على الطلب بحالته الحالية.'], 422);
        }

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'changed_by' => $request->user()->id,
            'from_status' => $previous,
            'to_status' => $to,
            'note' => $note,
        ]);

        if ($order->user_id) {
            OrderNotifier::statusChanged((int) $order->user_id, $order->order_number, $to, $note);
        }

        if ($to === 'rejected') {
            OrderNotifier::rejectedByShop($order->order_number, (string) $request->user()->printProvider?->company_name, (string) $note);
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
        $waitingDays = $tab === 'new' ? (int) $order->created_at->diffInDays(now()) : 0;

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
                'accept' => in_array($order->status, self::NEW_STATUSES, true),
                'reject' => in_array($order->status, self::NEW_STATUSES, true),
                'ready' => in_array($order->status, self::PROGRESS_STATUSES, true),
            ],
            'items' => $order->items->map(fn (OrderItem $item) => $this->presentItem($order, $item))->values()->all(),
        ];
    }

    private function presentItem(Order $order, OrderItem $item): array
    {
        $options = collect($item->selected_options ?? []);

        $details = $options
            ->filter(fn ($value, $key) => isset(self::OPTION_LABELS[$key]) && (is_scalar($value) || is_array($value)) && $value !== '' && $value !== [])
            ->map(fn ($value, $key) => [
                'label' => self::OPTION_LABELS[$key],
                'value' => collect((array) $value)->map(fn ($v) => self::OPTION_VALUES[(string) $v] ?? (string) $v)->implode('، '),
            ])
            ->values();

        $areas = $options->get('print_areas');
        if (is_array($areas) && $areas !== []) {
            $details->push(['label' => 'مناطق الطباعة', 'value' => implode('، ', array_map('strval', $areas))]);
        }

        $image = $item->design?->image;

        return [
            'name' => $item->product?->name ?? 'منتج',
            'quantity' => (int) $item->quantity,
            'cost' => (float) $item->provider_cost,
            'type' => $item->item_type === OrderItem::TYPE_CUSTOMER_UPLOAD ? 'upload' : 'design',
            'design_title' => $item->design?->title,
            'image' => $image ? (str_starts_with($image, 'http') ? $image : asset($image)) : null,
            'details' => $details->all(),
            'files' => $item->printFiles
                ->where('status', '!=', PrintFile::STATUS_DELETED)
                ->map(fn (PrintFile $file) => [
                    'name' => $file->original_name,
                    'size' => $file->file_size,
                    'pages' => $file->page_count,
                    'url' => route('print-provider.requests.files', [$order, $file]),
                ])->values()->all(),
        ];
    }
}
