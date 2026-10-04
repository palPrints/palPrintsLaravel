<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PrintProvider;
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
        'shipped' => ['shipped', 'تم الشحن', 'is-shipped', 'في الطريق إلى العميل'],
        'delivered' => ['completed', 'مكتمل', 'is-completed', 'تم التسليم'],
        'completed' => ['completed', 'مكتمل', 'is-completed', 'تم التسليم'],
        'cancelled' => ['cancelled', 'ملغي', 'is-cancelled', 'أُلغيت الشحنة'],
    ];

    /** UI state => database status written back when the admin changes it. */
    private const WRITE_STATUS = [
        'pending' => 'pending',
        'processing' => 'processing',
        'shipped' => 'shipped',
        'completed' => 'delivered',
        'cancelled' => 'cancelled',
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

        $record = DB::table('orders')->where('id', $order)->first();
        abort_if($record === null, 404);

        $changes = ['status' => self::WRITE_STATUS[$validated['status']], 'updated_at' => now()];

        DB::transaction(function () use ($order, $record, $changes, $validated, $request) {
            DB::table('orders')->where('id', $order)->update($changes);

            if ($record->status !== $changes['status'] && Schema::hasTable('order_status_history')) {
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
        });

        if ($record->status !== $changes['status'] && $record->user_id) {
            OrderNotifier::statusChanged((int) $record->user_id, (string) $record->order_number, $changes['status']);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'admin.order_updated',
            'description' => 'تحديث الطلب '.$record->order_number,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['order_id' => $order, 'status' => $changes['status']],
        ]);

        return response()->json(['ok' => true, 'message' => 'تم حفظ حالة الطلب بنجاح.']);
    }


    public function approvePayment(Request $request, int $order): JsonResponse
    {
        abort_unless(Schema::hasTable('orders') && Schema::hasTable('payments'), 404);

        [$record, $payment] = DB::transaction(function () use ($order, $request) {
            $record = DB::table('orders')->where('id', $order)->lockForUpdate()->first();
            abort_if($record === null, 404);

            $payment = DB::table('payments')
                ->where('order_id', $order)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            abort_if($payment === null, 422, 'لا يوجد سجل دفع مرتبط بهذا الطلب.');
            abort_if($record->payment_status === 'paid', 422, 'هذا الطلب مدفوع مسبقاً.');
            abort_if($payment->status !== 'pending_review', 422, 'هذا الدفع ليس بانتظار المراجعة.');

            $payload = json_decode($payment->gateway_payload ?? '[]', true) ?: [];
            $payload['reviewed_by'] = $request->user()->id;
            $payload['reviewed_at'] = now()->toISOString();
            $payload['review_result'] = 'approved';

            DB::table('orders')->where('id', $order)->update([
                'status' => 'processing',
                'payment_status' => 'paid',
                'updated_at' => now(),
            ]);

            DB::table('payments')->where('id', $payment->id)->update([
                'status' => 'paid',
                'paid_at' => now(),
                'gateway_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

            if ($record->status !== 'processing' && Schema::hasTable('order_status_history')) {
                DB::table('order_status_history')->insert([
                    'order_id' => $order,
                    'changed_by' => $request->user()->id,
                    'from_status' => $record->status,
                    'to_status' => 'processing',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return [$record, $payment];
        });

        if ($record->user_id) {
            OrderNotifier::statusChanged((int) $record->user_id, (string) $record->order_number, 'processing');
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'admin.payment_approved',
            'description' => 'اعتماد دفع الطلب '.$record->order_number,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['order_id' => $order, 'payment_id' => $payment->id, 'payment_status' => 'paid'],
        ]);

        return response()->json(['ok' => true, 'message' => 'تم اعتماد الدفع وتحويل الطلب إلى قيد التنفيذ.']);
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

    private function orders()
    {
        return DB::table('orders')
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
            ->get()
            ->map(function ($order) {
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
                    'approvePaymentUrl' => $order->payment_status === 'pending_review' ? route('admin.orders.payment.approve', $order->id) : null,
                    'notes' => $order->notes,
                    'iso' => $date->toDateString(),
                    'date' => $date->locale('ar')->translatedFormat('j F Y'),
                    'updateUrl' => route('admin.orders.update', $order->id),
                ];
            });
    }
}
