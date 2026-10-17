<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PrintProvider;
use App\Services\OrderShopChoices;
use App\Support\OrderNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The printer shown for an order comes from its items (order_items ->
 * print_provider_branches -> print_providers): orders are fulfilled per branch
 * and there is no orders.print_provider_id any more. Assigning a printer by
 * hand is read-only until the team defines how orders are routed to branches.
 */
class OrderController extends Controller
{
    /** Database status => [UI state, label, css class, shipment text]. */
    private const STATES = [
        'pending' => ['pending', 'معلق', 'is-pending', 'بانتظار التجهيز'],
        'awaiting_payment_review' => ['pending', 'بانتظار مراجعة الدفع', 'is-pending', 'بانتظار اعتماد الدفع'],
        'confirmed' => ['processing', 'قيد التنفيذ', 'is-processing', 'قيد التجهيز'],
        'processing' => ['processing', 'قيد التنفيذ', 'is-processing', 'قيد التجهيز'],
        'in_production' => ['processing', 'قيد التنفيذ', 'is-processing', 'قيد التجهيز'],
        'ready' => ['processing', 'جاهز للتسليم', 'is-processing', 'جاهز للاستلام'],
        'rejected' => ['cancelled', 'مرفوض من المطبعة', 'is-cancelled', 'أُلغيت الشحنة'],
        'shipped' => ['shipped', 'تم الشحن', 'is-shipped', 'في الطريق إلى العميل'],
        'delivered' => ['completed', 'مكتمل', 'is-completed', 'تم التسليم'],
        'completed' => ['completed', 'مكتمل', 'is-completed', 'تم التسليم'],
        'cancelled' => ['cancelled', 'ملغي', 'is-cancelled', 'أُلغيت الشحنة'],
    ];

    /** UI state => database status written back when the admin changes it. The admin only ever moves an order forward or cancels it. */
    private const WRITE_STATUS = [
        'shipped' => 'shipped',
        'completed' => 'delivered',
        'cancelled' => 'cancelled',
    ];

    /**
     * Database status => the UI states the admin may move the order to. Everything before "ready" belongs to the customer,
     * the payment review page and the print shop; "awaiting_payment_review" is decided only on the payment notices page.
     */
    private const NEXT = [
        'pending' => ['cancelled'],
        'processing' => ['cancelled'],
        'confirmed' => ['cancelled'],
        'in_production' => ['cancelled'],
        'ready' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'rejected' => ['cancelled'],
    ];

    public function index(): View
    {
        $available = Schema::hasTable('orders');

        $orders = $available ? $this->orders() : collect();

        return view('admin.orders', [
            'available' => $available,
            'orders' => $orders,
            'counts' => [
                'all' => $orders->count(),
                'processing' => $orders->where('state', 'processing')->count(),
                'shipped' => $orders->where('state', 'shipped')->count(),
                'completed' => $orders->where('state', 'completed')->count(),
                'pending' => $orders->where('state', 'pending')->count(),
                'cancelled' => $orders->where('state', 'cancelled')->count(),
            ],
            'revenue' => (float) $orders->where('state', 'completed')->sum('amount'),
            'printers' => Schema::hasTable('print_providers')
                ? PrintProvider::query()->where('is_active', true)->orderBy('company_name')->get(['id', 'company_name'])
                : collect(),
        ]);
    }

    public function update(Request $request, int $order): JsonResponse
    {
        abort_unless(Schema::hasTable('orders'), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::WRITE_STATUS))],
        ]);

        $result = DB::transaction(function () use ($order, $validated, $request) {
            // Locked, so two admins (or an admin and the print shop) cannot move the same order at once.
            $record = DB::table('orders')->where('id', $order)->lockForUpdate()->first();
            abort_if($record === null, 404);

            $allowed = self::NEXT[$record->status] ?? [];
            if (! in_array($validated['status'], $allowed, true)) {
                return ['error' => $this->refusal($record->status, $validated['status'])];
            }

            $changes = ['status' => self::WRITE_STATUS[$validated['status']], 'updated_at' => now()];
            DB::table('orders')->where('id', $order)->update($changes);

            if (Schema::hasTable('order_status_history')) {
                DB::table('order_status_history')->insert([
                    'order_id' => $order,
                    'changed_by' => $request->user()->id,
                    'from_status' => $record->status,
                    'to_status' => $changes['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Delivery/cancellation times live on the shipment, when one exists.
            if (Schema::hasTable('shipments') && in_array($validated['status'], ['completed', 'cancelled'], true)) {
                DB::table('shipments')->where('order_id', $order)->update([
                    ($validated['status'] === 'completed' ? 'delivered_at' : 'cancelled_at') => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['record' => $record, 'status' => $changes['status']];
        });

        if (isset($result['error'])) {
            return response()->json(['ok' => false, 'message' => $result['error']], 422);
        }

        ['record' => $record, 'status' => $newStatus] = $result;

        if ($record->user_id) {
            OrderNotifier::statusChanged((int) $record->user_id, (string) $record->order_number, $newStatus);
        }

        if ($newStatus === 'cancelled') {
            $this->afterCancellation($record);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'admin.order_updated',
            'description' => 'تحديث الطلب '.$record->order_number,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['order_id' => $order, 'from' => $record->status, 'status' => $newStatus],
        ]);

        return response()->json(['ok' => true, 'message' => 'تم حفظ حالة الطلب بنجاح.']);
    }

    /**
     * The shop turned the order down: send it to another shop. Only an order in "rejected" can be sent again, and never
     * to a shop that already turned it down. The new shop gets it as a new order, the customer is told it is being made.
     */
    public function reroute(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate(['branch_id' => ['required', 'integer']]);

        $result = DB::transaction(function () use ($order, $validated, $request) {
            $locked = Order::query()->with('items.product', 'user')->lockForUpdate()->findOrFail($order->id);

            if ($locked->status !== 'rejected') {
                return ['error' => 'هذا الطلب ليس بحالة «المطبعة رفضت»، فلا يمكن إعادة توجيهه.'];
            }

            $branch = app(OrderShopChoices::class)->eligibleBranches($locked, $this->rejectedBranchIds($locked))->firstWhere('id', (int) $validated['branch_id']);
            if (! $branch) {
                return ['error' => 'هذه المطبعة لا تقدّم كل منتجات الطلب، أو سبق أن رفضته.'];
            }

            foreach ($locked->items as $item) {
                $item->update([
                    'print_provider_branch_id' => $branch->id,
                    'branch_product_offering_id' => $branch->branchProductOfferings->firstWhere('product_id', $item->product_id)->id,
                ]);
            }

            $locked->update(['status' => 'processing']);
            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'changed_by' => $request->user()->id,
                'from_status' => 'rejected',
                'to_status' => 'processing',
                'note' => 'أُعيد توجيه الطلب بعد رفض المطبعة إلى '.app(OrderShopChoices::class)->label($branch),
                'metadata' => ['branch_id' => $branch->id],
            ]);

            return ['order' => $locked, 'branch' => $branch];
        });

        if (isset($result['error'])) {
            return response()->json(['ok' => false, 'message' => $result['error']], 422);
        }

        ['order' => $locked, 'branch' => $branch] = $result;

        if ($providerUserId = $branch->printProvider?->user_id) {
            OrderNotifier::newOrderForShop((int) $providerUserId, $locked->order_number, $locked->items->count());
        }
        if ($locked->user_id) {
            OrderNotifier::statusChanged((int) $locked->user_id, $locked->order_number, 'processing');
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'admin.order_rerouted',
            'description' => 'إعادة توجيه الطلب '.$locked->order_number.' بعد رفض المطبعة',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['order_id' => $locked->id, 'branch_id' => $branch->id],
        ]);

        return response()->json(['ok' => true, 'message' => 'تم توجيه الطلب إلى '.app(OrderShopChoices::class)->label($branch).'.']);
    }

    /** The branches that already turned this order down: the one holding it now, and the ones recorded earlier. */
    private function rejectedBranchIds(Order $order): array
    {
        $earlier = OrderStatusHistory::query()->where('order_id', $order->id)->where('to_status', 'rejected')->get()
            ->flatMap(fn ($row) => (array) ($row->metadata['rejected_branch_ids'] ?? []));

        return $order->items->pluck('print_provider_branch_id')->merge($earlier)->filter()->unique()->map(fn ($id) => (int) $id)->values()->all();
    }

    /** What the orders page shows for an order a shop turned down: the shops it can go to now. */
    private function rerouteData(int $orderId): array
    {
        $order = Order::query()->with('items.product', 'user')->find($orderId);
        $choices = app(OrderShopChoices::class)->choices($order, $this->rejectedBranchIds($order));

        return $choices + ['url' => route('admin.orders.reroute', $orderId)];
    }

    /** Why the move is not allowed, in words for the admin. */
    private function refusal(string $from, string $to): string
    {
        $current = self::STATES[$from][1] ?? $from;

        return match (true) {
            $from === 'awaiting_payment_review' => 'هذا الطلب بانتظار مراجعة الدفع: وافق عليه أو ارفضه من صفحة إشعارات الدفع.',
            in_array($from, ['delivered', 'completed', 'cancelled'], true) => 'الطلب «'.$current.'» ولا يمكن تغيير حالته.',
            $to === 'shipped' => 'لا يمكن شحن الطلب قبل أن تُجهّزه المطبعة وتُعلّمه «جاهز للتسليم».',
            $to === 'completed' => 'لا يمكن إكمال الطلب قبل شحنه.',
            default => 'لا يمكن نقل الطلب من «'.$current.'» إلى هذه الحالة.',
        };
    }

    /** A cancelled order that was paid needs its money returned, and a shop that already had it must stop working on it. */
    private function afterCancellation(object $record): void
    {
        if ($record->payment_status === 'paid') {
            \App\Support\AdminNotifier::toAdmins(
                'order.refund_needed',
                'طلب ملغى مدفوع: يجب إرجاع المبلغ',
                sprintf('أُلغي الطلب رقم %s بعد اعتماد دفعه. أرجِع المبلغ للعميل %s.', $record->order_number, DB::table('users')->where('id', $record->user_id)->value('name') ?? ''),
                route('admin.orders'),
            );
        }

        // The shops that had it (not one that already turned it down).
        if (in_array($record->status, ['processing', 'confirmed', 'in_production', 'ready'], true)) {
            DB::table('order_items')
                ->join('print_provider_branches', 'print_provider_branches.id', '=', 'order_items.print_provider_branch_id')
                ->join('print_providers', 'print_providers.id', '=', 'print_provider_branches.print_provider_id')
                ->where('order_items.order_id', $record->id)
                ->distinct()->pluck('print_providers.user_id')
                ->each(fn ($userId) => OrderNotifier::cancelledForShop((int) $userId, (string) $record->order_number));
        }
    }
    public function paymentReceipt(int $order)
    {
        abort_unless(Schema::hasTable('payments'), 404);

        $payment = DB::table('payments')->where('order_id', $order)->orderByDesc('id')->first();
        abort_if($payment === null, 404);

        $payload = json_decode($payment->gateway_payload ?? '[]', true) ?: [];
        $path = $payload['receipt_path'] ?? null;
        $disk = $payload['receipt_disk'] ?? 'local';
        $name = $payload['receipt_original_name'] ?? basename((string) $path);

        abort_if(! $path || ! Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, $name);
    }
    /** SQL for one column of the print provider/branch of the order's first item. */
    private function firstItemProvider(string $expression): string
    {
        return '(select '.$expression.' from order_items oi '
            .'join print_provider_branches b on b.id = oi.print_provider_branch_id '
            .'join print_providers pp on pp.id = b.print_provider_id '
            .'where oi.order_id = orders.id order by oi.id limit 1)';
    }

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
    ];

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

    /** The products of an order, for the details dialog. */
    private function presentItems(?Order $order): array
    {
        if ($order === null) {
            return [];
        }

        return $order->items->map(function ($item) {
            $details = collect($item->selected_options ?? [])
                ->filter(fn ($value, $key) => isset(self::OPTION_LABELS[$key]) && is_scalar($value) && $value !== '')
                ->map(fn ($value, $key) => self::OPTION_LABELS[$key].': '.(self::OPTION_VALUES[(string) $value] ?? $value))
                ->values();

            $areas = collect($item->selected_options ?? [])->get('print_areas');
            if (is_array($areas) && $areas !== []) {
                $details->push('مناطق الطباعة: '.implode('، ', array_map('strval', $areas)));
            }

            return [
                'name' => $item->product?->name ?? 'منتج',
                'design' => $item->design?->title,
                'quantity' => (int) $item->quantity,
                'total' => number_format((float) $item->total_price, 2).' ₪',
                'details' => $details->all(),
            ];
        })->values()->all();
    }

    private function presentAddress(?\App\Models\Address $address): ?string
    {
        if ($address === null) {
            return null;
        }

        return collect([$address->city, $address->region, $address->street, $address->building ? 'مبنى '.$address->building : null])
            ->filter()->implode('، ') ?: null;
    }

    private function orders()
    {
        $rows = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->select([
                'orders.id', 'orders.order_number', 'orders.total_amount', 'orders.status', 'orders.payment_method',
                'orders.payment_status', 'orders.notes', 'orders.created_at',
                'users.name as customer', 'users.phone',
            ])
            ->selectRaw($this->firstItemProvider('pp.id').' as print_provider_id')
            ->selectRaw($this->firstItemProvider('pp.company_name').' as printer_name')
            ->selectRaw($this->firstItemProvider('b.city').' as printer_city')
            ->selectRaw('(select payments.gateway_payload from payments where payments.order_id = orders.id order by payments.id desc limit 1) as payment_payload')
            ->orderByDesc('orders.created_at')
            ->limit(200)
            ->get();

        // The full order (items, delivery address) is loaded in two queries for the details dialog.
        $models = Order::query()
            ->with(['items.product', 'items.design', 'shippingAddress'])
            ->whereIn('id', $rows->pluck('id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function ($order) use ($models) {
                [$state, $label, $class, $shipment] = self::STATES[$order->status] ?? ['pending', $order->status, 'is-pending', '—'];
                $date = Carbon::parse($order->created_at);

                $paymentPayload = json_decode($order->payment_payload ?? '[]', true) ?: [];
                $receiptPath = $paymentPayload['receipt_path'] ?? null;

                return [
                    'id' => $order->id,
                    'number' => $order->order_number,
                    'state' => $state,
                    'label' => $label,
                    'class' => $class,
                    'shipment' => $shipment,
                    'amount' => (float) $order->total_amount,
                    'customer' => $order->customer,
                    'phone' => $order->phone,
                    'printer' => $order->printer_name
                        ? $order->printer_name.($order->printer_city ? ' ('.$order->printer_city.')' : '')
                        : null,
                    'printerId' => $order->print_provider_id,
                    'payment' => $order->payment_method,
                    'paymentStatus' => $order->payment_status,
                    'paid' => $order->payment_status === 'paid',
                    'receiptUrl' => $receiptPath ? route('admin.orders.payment-receipt', $order->id) : null,
                    // Where the order can go from the orders page; the payment decision lives on the payment notices page.
                    'next' => self::NEXT[$order->status] ?? [],
                    'awaitingPayment' => $order->status === 'awaiting_payment_review',
                    'reroute' => $order->status === 'rejected' ? $this->rerouteData((int) $order->id) : null,
                    'notes' => $order->notes,
                    'items' => $this->presentItems($models->get($order->id)),
                    'address' => $this->presentAddress($models->get($order->id)?->shippingAddress),
                    'iso' => $date->toDateString(),
                    'date' => $date->locale('ar')->translatedFormat('j F Y'),
                    'updateUrl' => route('admin.orders.update', $order->id),
                ];
            });
    }
}
