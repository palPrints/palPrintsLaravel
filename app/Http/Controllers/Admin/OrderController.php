<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PrintProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            ->orderByDesc('orders.created_at')
            ->limit(200)
            ->get()
            ->map(function ($order) {
                [$state, $label, $class, $shipment] = self::STATES[$order->status] ?? ['pending', $order->status, 'is-pending', '—'];
                $date = Carbon::parse($order->created_at);

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
                    'paid' => $order->payment_status === 'paid',
                    'notes' => $order->notes,
                    'iso' => $date->toDateString(),
                    'date' => $date->locale('ar')->translatedFormat('j F Y'),
                    'updateUrl' => route('admin.orders.update', $order->id),
                ];
            });
    }
}
