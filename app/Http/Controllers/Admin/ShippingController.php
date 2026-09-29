<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Shipments come from the shipments table (tracking number, carrier and
 * estimated delivery live there); the page falls back to its empty state when
 * the tables are missing or there are no shipments yet.
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
        $available = Schema::hasTable('shipments') && Schema::hasTable('orders') && Schema::hasTable('addresses') && Schema::hasTable('delivery_partners');

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
            .'join designs d on d.id = oi.design_id '
            .'where oi.order_id = orders.id order by oi.id limit 1)';

        return DB::table('shipments')
            ->join('orders', 'orders.id', '=', 'shipments.order_id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.shipping_address_id')
            ->leftJoin('delivery_partners', 'delivery_partners.id', '=', 'shipments.delivery_partner_id')
            ->select([
                'shipments.id', 'shipments.shipment_number', 'shipments.tracking_number',
                'shipments.estimated_delivery_at', 'orders.order_number', 'orders.status',
                'orders.shipping_address_snapshot', 'users.name as customer', 'addresses.city',
                'delivery_partners.company_name as carrier',
            ])
            ->selectRaw($firstItemTitle.' as product')
            ->orderByDesc('shipments.id')
            ->limit(200)
            ->get()
            ->map(function ($shipment) {
                [$state, $label, $class] = self::STATES[$shipment->status] ?? ['pending', $shipment->status, 'is-pending'];
                $date = $shipment->estimated_delivery_at ? Carbon::parse($shipment->estimated_delivery_at) : null;
                $snapshot = json_decode((string) $shipment->shipping_address_snapshot, true);

                return [
                    'number' => $shipment->shipment_number ?: 'SHP-'.str_pad((string) $shipment->id, 3, '0', STR_PAD_LEFT),
                    'orderNumber' => $shipment->order_number,
                    'customer' => $shipment->customer,
                    'city' => $shipment->city ?: ($snapshot['city'] ?? '—'),
                    'product' => $shipment->product ?: '—',
                    'carrier' => $shipment->carrier ?: '—',
                    'trackingNumber' => $shipment->tracking_number ?: '—',
                    'estimatedDelivery' => $date?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'state' => $state,
                    'label' => $label,
                    'class' => $class,
                ];
            });
    }
}
