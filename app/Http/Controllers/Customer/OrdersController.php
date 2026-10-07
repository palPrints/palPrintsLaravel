<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    /** Database `orders.status` => [label, CSS class, icon] shown on the page. */
    private const STATES = [
        'pending' => ['قيد الانتظار', 'status-review', 'bi-hourglass-split'],
        'awaiting_payment_review' => ['بانتظار مراجعة الدفع', 'status-review', 'bi-clock-history'],
        'confirmed' => ['قيد التنفيذ', 'status-progress', 'bi-gear'],
        'processing' => ['قيد التنفيذ', 'status-progress', 'bi-gear'],
        'in_production' => ['قيد التنفيذ', 'status-progress', 'bi-gear'],
        'ready' => ['جاهز للتسليم', 'status-shipping', 'bi-box-seam'],
        'rejected' => ['اعتذرت المطبعة', 'status-cancelled', 'bi-x-circle'],
        'shipped' => ['قيد التوصيل', 'status-shipping', 'bi-truck'],
        'delivered' => ['تم التنفيذ', 'status-done', 'bi-check-circle'],
        'completed' => ['تم التنفيذ', 'status-done', 'bi-check-circle'],
        'cancelled' => ['ملغي', 'status-cancelled', 'bi-x-circle'],
    ];

    /** Statuses that move an order from "current" to "previous". */
    private const FINAL_STATUSES = ['delivered', 'completed', 'cancelled', 'rejected'];

    /** Only orders still in these statuses (not yet in production) can be cancelled by the customer. */
    private const CANCELLABLE_STATUSES = ['pending'];

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items.product')
            ->latest()
            ->get()
            ->map(fn (Order $order) => $this->present($order));

        return view('customer.orders', [
            'currentOrders' => $orders->reject(fn (array $order) => in_array($order['raw_status'], self::FINAL_STATUSES, true))->values(),
            'previousOrders' => $orders->filter(fn (array $order) => in_array($order['raw_status'], self::FINAL_STATUSES, true))->values(),
            'totalCount' => $orders->count(),
        ]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! in_array($order->status, self::CANCELLABLE_STATUSES, true)) {
            return back()->with('status', 'order-not-cancellable');
        }

        $order->update(['status' => 'cancelled']);
        OrderNotifier::statusChanged($order->user_id, $order->order_number, 'cancelled');

        return back()->with('status', 'order-cancelled');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Order $order): array
    {
        [$label, $class, $icon] = self::STATES[$order->status] ?? [$order->status, 'status-review', 'bi-clock'];

        $items = $order->items;
        $firstItem = $items->first();

        return [
            'id' => $order->id,
            'number' => $order->order_number,
            'date_human' => $order->created_at?->locale('ar')->translatedFormat('j F Y'),
            'date_iso' => $order->created_at?->toDateString(),
            'total' => number_format((float) $order->total_amount, 0).' شيكل',
            'status_label' => $label,
            'status_class' => $class,
            'status_icon' => $icon,
            'raw_status' => $order->status,
            'cancellable' => in_array($order->status, self::CANCELLABLE_STATUSES, true),
            'address' => $this->formatAddress($order->shipping_address_snapshot),
            'payment' => $order->payment_method ?: 'غير محدد',
            'thumbnail' => $firstItem ? asset($firstItem->product?->image ?: 'front/assets/images/customer/products/1.png') : asset('front/assets/images/customer/products/1.png'),
            'mockup' => $firstItem?->selected_options['mockup'] ?? null,
            'title' => $firstItem?->product?->name ?? 'منتج',
            'subtitle' => $items->count() > 1 ? 'ومنتج آخر' : ($firstItem?->product?->description ?: ''),
            'items' => $items->map(fn ($item) => [
                'image' => asset($item->product?->image ?: 'front/assets/images/customer/products/1.png'),
                'title' => $item->product?->name ?? 'منتج',
                'desc' => $item->product?->description ?: '',
                'qty' => $item->quantity,
                'price' => number_format((float) $item->total_price, 0).' شيكل',
            ])->values(),
        ];
    }

    private function formatAddress(?array $snapshot): string
    {
        if (! $snapshot) {
            return 'لا يوجد عنوان محفوظ';
        }

        $parts = array_filter([
            $snapshot['city'] ?? null,
            $snapshot['region'] ?? null,
            $snapshot['street'] ?? null,
            $snapshot['building'] ?? null,
        ]);

        return $parts ? implode('، ', $parts) : 'لا يوجد عنوان محفوظ';
    }
}
