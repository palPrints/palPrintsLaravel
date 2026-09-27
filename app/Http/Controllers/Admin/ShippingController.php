<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * The orders tables are not part of the current schema yet, so the query is
 * guarded and the page falls back to its empty state until the tables return.
 */
class ShippingController extends Controller
{
    /** Order status => [shipping state, label, css class]. */
    private const STATES = [
        'pending' => ['pending', 'بانتظار الاستلام', 'is-pending'],
        'confirmed' => ['pending', 'بانتظار الاستلام', 'is-pending'],
        'processing' => ['pending', 'بانتظار الاستلام', 'is-pending'],
        'in_production' => ['pending', 'بانتظار الاستلام', 'is-pending'],
        'shipped' => ['transit', 'في الطريق', 'is-transit'],
        'delivered' => ['delivered', 'تم التوصيل', 'is-delivered'],
        'completed' => ['delivered', 'تم التوصيل', 'is-delivered'],
        'cancelled' => ['failed', 'فشل التوصيل', 'is-failed'],
    ];

    public function index(): View
    {
        $available = Schema::hasTable('orders') && Schema::hasTable('addresses');

        $shipments = $available ? $this->shipments() : collect();

        return view('admin.shipping', [
            'available' => $available,
            'shipments' => $shipments,
            'counts' => [
                'all' => $shipments->count(),
                'failed' => $shipments->where('state', 'failed')->count(),
                'delivered' => $shipments->where('state', 'delivered')->count(),
                'transit' => $shipments->where('state', 'transit')->count(),
                'pending' => $shipments->where('state', 'pending')->count(),
            ],
        ]);
    }

    private function shipments()
    {
        $firstItemTitle = '(select d.title from order_items oi '
            .'join design_products dp on dp.id = oi.design_product_id '
            .'join designs d on d.id = dp.design_id '
            .'where oi.order_id = orders.id order by oi.id limit 1)';

        return DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.shipping_address_id')
            ->leftJoin('users as delivery_partners', 'delivery_partners.id', '=', 'orders.delivery_partner_id')
            ->whereNotNull('orders.tracking_number')
            ->select([
                'orders.id', 'orders.order_number', 'orders.status', 'orders.tracking_number',
                'orders.estimated_delivery_date', 'users.name as customer', 'addresses.city',
                'delivery_partners.name as carrier',
            ])
            ->selectRaw($firstItemTitle.' as product')
            ->orderByDesc('orders.id')
            ->limit(200)
            ->get()
            ->map(function ($order) {
                [$state, $label, $class] = self::STATES[$order->status] ?? ['pending', $order->status, 'is-pending'];
                $date = $order->estimated_delivery_date ? Carbon::parse($order->estimated_delivery_date) : null;

                return [
                    'number' => 'SHP-'.str_pad((string) $order->id, 3, '0', STR_PAD_LEFT),
                    'orderNumber' => $order->order_number,
                    'customer' => $order->customer,
                    'city' => $order->city ?: '—',
                    'product' => $order->product ?: '—',
                    'carrier' => $order->carrier ?: '—',
                    'trackingNumber' => $order->tracking_number,
                    'estimatedDelivery' => $date?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'state' => $state,
                    'label' => $label,
                    'class' => $class,
                ];
            });
    }
}
