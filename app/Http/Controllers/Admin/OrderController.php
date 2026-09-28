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
 * The orders tables are not part of the current schema yet, so every query is
 * guarded and the page falls back to its empty state until the tables return.
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
            'print_provider_id' => ['nullable', Rule::exists('print_providers', 'id')],
        ]);

        $record = DB::table('orders')->where('id', $order)->first();
        abort_if($record === null, 404);

        $changes = ['status' => self::WRITE_STATUS[$validated['status']], 'updated_at' => now()];

        if ($validated['status'] === 'cancelled') {
            $changes['cancelled_at'] = now();
        }

        if ($validated['status'] === 'completed') {
            $changes['delivered_at'] = now();
        }

        if (! empty($validated['print_provider_id']) && ! in_array($record->status, ['delivered', 'completed', 'cancelled'], true)) {
            $changes['print_provider_id'] = $validated['print_provider_id'];
        }

        DB::table('orders')->where('id', $order)->update($changes);

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

    private function orders()
    {
        return DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('print_providers', 'print_providers.id', '=', 'orders.print_provider_id')
            ->select([
                'orders.id', 'orders.order_number', 'orders.final_amount', 'orders.status', 'orders.payment_method',
                'orders.payment_status', 'orders.notes', 'orders.created_at', 'orders.print_provider_id',
                'users.name as customer', 'users.phone', 'print_providers.company_name as printer',
            ])
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
                    'amount' => (float) $order->final_amount,
                    'customer' => $order->customer,
                    'phone' => $order->phone,
                    'printer' => $order->printer,
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
