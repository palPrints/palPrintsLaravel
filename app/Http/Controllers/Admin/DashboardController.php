<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\Design;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const CHART = ['left' => 30, 'right' => 930, 'top' => 28, 'bottom' => 205];

    public function __invoke(Request $request): View
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $lastMonthStart->copy()->endOfMonth();

        $usersTotal = User::count();
        $usersLastMonth = User::where('created_at', '<=', $lastMonthEnd)->count();

        // The orders tables are not part of the current schema yet, so every order figure falls back to zero.
        $hasOrders = Schema::hasTable('orders');

        $ordersToday = $hasOrders ? DB::table('orders')->whereDate('created_at', $now->toDateString())->count() : 0;
        $ordersYesterday = $hasOrders ? DB::table('orders')->whereDate('created_at', $now->copy()->subDay()->toDateString())->count() : 0;

        $revenueThisMonth = $hasOrders ? $this->revenueBetween($monthStart, $now) : 0;
        $revenueLastMonth = $hasOrders ? $this->revenueBetween($lastMonthStart, $lastMonthEnd) : 0;

        $pendingApprovals = ApprovalRequest::whereIn('status', ['submitted', 'under_review']);
        $pendingApprovalsCount = (clone $pendingApprovals)->count();

        $pendingDesigns = Design::whereIn('status', ['review', 'submitted']);
        $pendingDesignsCount = (clone $pendingDesigns)->count();

        $userGroups = collect([
            'customer' => ['label' => 'عملاء', 'color' => 'is-blue'],
            'designer' => ['label' => 'مصممون', 'color' => 'is-orange'],
            'print_provider' => ['label' => 'مطابع', 'color' => 'is-green'],
        ])->map(function (array $group, string $role) use ($usersTotal) {
            $count = User::role($role)->count();

            return $group + [
                'count' => $count,
                'percent' => $usersTotal > 0 ? (int) round($count / $usersTotal * 100) : 0,
            ];
        });

        $chart = $this->salesChart($now, $hasOrders);

        return view('admin.dashboard', [
            'today' => $now->copy()->locale('ar')->translatedFormat('l، j F Y'),
            'metrics' => [
                'users' => ['value' => $usersTotal, 'trend' => $this->trend($usersTotal, $usersLastMonth), 'note' => 'مقارنة بالشهر الماضي'],
                'orders' => ['value' => $ordersToday, 'trend' => $this->trend($ordersToday, $ordersYesterday), 'note' => 'مقارنة بالأمس'],
                'revenue' => ['value' => (int) round($revenueThisMonth), 'trend' => $this->trend($revenueThisMonth, $revenueLastMonth), 'note' => 'مقارنة بالشهر الماضي'],
                'pending' => ['value' => $pendingApprovalsCount],
            ],
            'chart' => $chart,
            'userGroups' => $userGroups,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'approvalRequests' => $pendingApprovals->with('user:id,name')->latest('submitted_at')->limit(4)->get(),
            'pendingDesignsCount' => $pendingDesignsCount,
            'pendingDesigns' => $pendingDesigns->with(['designer:id,name', 'product:id,name'])->latest()->limit(3)->get(),
            'latestOrders' => $hasOrders ? $this->latestOrders() : collect(),
        ]);
    }

    private function revenueBetween(Carbon $from, Carbon $to): float
    {
        return (float) DB::table('orders')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');
    }

    /** @return array{value: int, direction: string}|null */
    private function trend(float|int $current, float|int $previous): ?array
    {
        if ($previous <= 0) {
            return null;
        }

        $percent = (int) round(($current - $previous) / $previous * 100);

        return ['value' => abs($percent), 'direction' => $percent >= 0 ? 'up' : 'down'];
    }

    private function latestOrders()
    {
        $firstItemTitle = '(select d.title from order_items oi '
            .'join designs d on d.id = oi.design_id '
            .'where oi.order_id = orders.id order by oi.id limit 1)';

        return DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->select('orders.id', 'orders.order_number', 'orders.total_amount', 'orders.status', 'orders.created_at', 'users.name as customer')
            ->selectRaw($firstItemTitle.' as product')
            ->latest('orders.created_at')
            ->limit(6)
            ->get()
            ->map(function ($order) {
                $order->created_at = Carbon::parse($order->created_at);

                return $order;
            });
    }

    /** Monthly order counts for the last 12 months, plus SVG geometry for the line chart. */
    private function salesChart(Carbon $now, bool $hasOrders): array
    {
        $months = collect(range(11, 0))->map(function (int $ago) use ($now, $hasOrders) {
            $start = $now->copy()->subMonthsNoOverflow($ago)->startOfMonth();

            return [
                'label' => $start->copy()->locale('ar')->translatedFormat('F'),
                'count' => $hasOrders
                    ? DB::table('orders')
                        ->where('status', '!=', 'cancelled')
                        ->whereBetween('created_at', [$start, $start->copy()->endOfMonth()])
                        ->count()
                    : 0,
            ];
        });

        $counts = $months->pluck('count');
        $max = max($counts->max(), 4);
        ['left' => $left, 'right' => $right, 'top' => $top, 'bottom' => $bottom] = self::CHART;
        $step = ($right - $left) / 11;

        $points = $counts->values()->map(fn (int $count, int $i) => [
            'x' => round($left + $i * $step, 1),
            'y' => round($bottom - ($count / $max) * ($bottom - $top), 1),
            'count' => $count,
        ]);

        $line = 'M'.$points[0]['x'].' '.$points[0]['y'];
        for ($i = 1; $i < $points->count(); $i++) {
            $mid = round(($points[$i - 1]['x'] + $points[$i]['x']) / 2, 1);
            $line .= ' C'.$mid.' '.$points[$i - 1]['y'].' '.$mid.' '.$points[$i]['y'].' '.$points[$i]['x'].' '.$points[$i]['y'];
        }

        $last = $counts->last();
        $previous = $counts->count() > 1 ? $counts[$counts->count() - 2] : 0;

        return [
            'months' => $months->pluck('label'),
            'points' => $points,
            'line' => $line,
            'area' => $line.' L'.$right.' '.($bottom + 20).' L'.$left.' '.($bottom + 20).' Z',
            'ticks' => collect(range(0, 4))->map(fn (int $i) => [
                'y' => round($bottom - $i * ($bottom - $top) / 4, 1),
                'label' => (int) round($max * $i / 4),
            ]),
            'last' => $last,
            'growth' => $this->trend($last, $previous),
            'hasData' => $counts->sum() > 0,
        ];
    }
}
