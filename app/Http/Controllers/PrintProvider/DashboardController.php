<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\BranchProductOffering;
use App\Models\Order;
use App\Support\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** The admin approving the payment leaves the order "processing": it is new to the shop until the shop accepts it. */
    private const NEW_STATUSES = ['pending', 'processing'];

    private const PROGRESS_STATUSES = ['confirmed', 'in_production'];

    private const COMPLETED_STATUSES = ['delivered', 'completed'];

    /** Database `orders.status` => [label, CSS class]. */
    private const STATES = [
        'pending' => ['جديد', 'is-new'],
        'confirmed' => ['قيد التنفيذ', 'is-progress'],
        'processing' => ['قيد التنفيذ', 'is-progress'],
        'in_production' => ['قيد التنفيذ', 'is-progress'],
        'shipped' => ['قيد التوصيل', 'is-progress'],
        'delivered' => ['مكتمل', 'is-complete'],
        'completed' => ['مكتمل', 'is-complete'],
        'cancelled' => ['ملغي', 'is-cancelled'],
    ];

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole('print_provider'), 403);

        $provider = $user->printProvider;
        $branchIds = $provider?->branches()->pluck('id') ?? collect();

        // Orders that contain at least one item routed to one of this provider's branches.
        // Orders whose payment notice the admin has not approved yet (or rejected) never reach the shop.
        $orders = fn () => Order::query()->whereNotIn('payment_status', ['pending', 'pending_review', 'failed'])->whereHas(
            'items',
            fn ($items) => $items->whereIn('print_provider_branch_id', $branchIds)
        );

        $now = now();
        $weekStart = $now->copy()->subDays(7);
        $prevWeekStart = $now->copy()->subDays(14);
        $wallet = $user->wallet;

        $earningsBetween = fn (Carbon $from, Carbon $to) => (float) ($wallet?->walletTransactions()
            ->where('type', '!=', 'withdrawal')
            ->where('amount', '>', 0)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount') ?? 0);

        $countBetween = fn (array $statuses, string $column, Carbon $from, Carbon $to) => $orders()
            ->whereIn('status', $statuses)
            ->whereBetween($column, [$from, $to])
            ->count();

        $stats = [
            'earnings' => [
                'value' => (float) ($wallet?->available_balance ?? 0),
                'trend' => $this->trend($earningsBetween($weekStart, $now), $earningsBetween($prevWeekStart, $weekStart)),
            ],
            'completed' => [
                'value' => $orders()->whereIn('status', self::COMPLETED_STATUSES)
                    ->whereBetween('updated_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                    ->count(),
                'trend' => $this->trend(
                    $countBetween(self::COMPLETED_STATUSES, 'updated_at', $weekStart, $now),
                    $countBetween(self::COMPLETED_STATUSES, 'updated_at', $prevWeekStart, $weekStart),
                ),
            ],
            'progress' => [
                'value' => $orders()->whereIn('status', self::PROGRESS_STATUSES)->count(),
                'trend' => $this->trend(
                    $countBetween(self::PROGRESS_STATUSES, 'updated_at', $weekStart, $now),
                    $countBetween(self::PROGRESS_STATUSES, 'updated_at', $prevWeekStart, $weekStart),
                ),
            ],
            'new' => [
                'value' => $orders()->whereIn('status', self::NEW_STATUSES)->count(),
                'trend' => $this->trend(
                    $countBetween(self::NEW_STATUSES, 'created_at', $weekStart, $now),
                    $countBetween(self::NEW_STATUSES, 'created_at', $prevWeekStart, $weekStart),
                ),
            ],
        ];

        $recentOrders = $orders()
            ->with(['items' => fn ($items) => $items->whereIn('print_provider_branch_id', $branchIds)->with('product')])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Order $order): array {
                [$label, $class] = self::STATES[$order->status] ?? [$order->status, 'is-new'];
                $first = $order->items->first()?->product?->name ?? 'منتج';
                $extra = $order->items->count() - 1;

                return [
                    'number' => $order->order_number,
                    'product' => $extra > 0 ? $first.' +'.$extra : $first,
                    'status_label' => $label,
                    'status_class' => $class,
                    'date' => $order->created_at,
                ];
            });

        return view('printProvider.dashboard', [
            'stats' => $stats,
            'alerts' => $this->alerts($branchIds, $orders, (float) ($wallet?->available_balance ?? 0)),
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Week-over-week change: [direction (up|down|flat), label] or null when there is nothing to compare.
     *
     * @return array{0: string, 1: string}|null
     */
    private function trend(float|int $current, float|int $previous): ?array
    {
        if ($current == 0 && $previous == 0) {
            return null;
        }

        if ($previous == 0) {
            return ['up', 'جديد'];
        }

        $change = (int) round((($current - $previous) / $previous) * 100);

        return [$change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'), abs($change).'%'];
    }

    /**
     * @return array<int, array{icon: string, tone: string, title: string, text: string, action: string, url: string}>
     */
    private function alerts($branchIds, \Closure $orders, float $available): array
    {
        $alerts = [];

        $inactive = BranchProductOffering::query()
            ->whereIn('print_provider_branch_id', $branchIds)
            ->where('is_active', false)
            ->with('product')
            ->orderBy('updated_at')
            ->limit(3)
            ->get();

        foreach ($inactive as $offering) {
            $days = (int) $offering->updated_at?->diffInDays(now());
            $alerts[] = [
                'icon' => 'bi-exclamation-triangle',
                'tone' => 'is-warning',
                'title' => 'المنتج "'.($offering->product?->name ?? 'منتج').'" معطل'.($days > 0 ? ' من '.$days.' أيام' : ''),
                'text' => 'فعّل المنتج ليتاح للعملاء مجددًا.',
                'action' => 'تفعيل الآن',
                'url' => route('print-provider.services'),
            ];
        }

        $stale = $orders()
            ->whereIn('status', self::NEW_STATUSES)
            ->where('created_at', '<=', now()->subDays(2))
            ->oldest()
            ->limit(3)
            ->get();

        foreach ($stale as $order) {
            $alerts[] = [
                'icon' => 'bi-truck',
                'tone' => 'is-danger',
                'title' => 'الطلب #'.$order->order_number.' ينتظر المعالجة منذ '.(int) $order->created_at->diffInDays(now()).' أيام',
                'text' => 'راجع الطلب وابدأ التنفيذ.',
                'action' => 'عرض الطلب',
                'url' => route('print-provider.requests'),
            ];
        }

        $minimum = (float) PlatformSettings::get('fees', 'minimum_withdrawal', 100);

        if ($available > 0 && $available >= $minimum) {
            $alerts[] = [
                'icon' => 'bi-cash-coin',
                'tone' => 'is-success',
                'title' => 'رصيدك القابل للسحب وصل '.number_format($available, 2).' ₪',
                'text' => 'يمكنك الآن طلب سحب الأرباح.',
                'action' => 'اطلب سحب',
                'url' => route('print-provider.earnings'),
            ];
        }

        return $alerts;
    }
}
