<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->hasRole('designer'), 403);

        $profile = $user->designerProfile;
        $designs = Design::query()->where('designer_id', $user->id);
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $averageRating = 0.0;
        $ratingsCount = 0;

        if (Schema::hasColumn('designs', 'avg_rating')) {
            $averageRating = (float) ($designs->clone()->avg('avg_rating') ?? 0);
            $ratingsCount = (int) ($designs->clone()->sum('total_reviews') ?? 0);
        }

        $statusCounts = (clone $designs)->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total_designs' => (clone $designs)->count(),
            'designs_this_month' => (clone $designs)->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            'total_sales' => (int) ($profile?->total_sales ?? 0),
            'total_earnings' => (float) ($profile?->total_earnings ?? 0),
            'earnings_this_month' => (float) ($user->wallet?->walletTransactions()
                ->where('type', 'design_profit')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount') ?? 0),
            'available_balance' => (float) ($user->wallet?->available_balance ?? 0),
            'average_rating' => $averageRating,
            'ratings_count' => $ratingsCount,
            'published_count' => (int) ($statusCounts['published'] ?? 0),
            'review_count' => (int) ($statusCounts['review'] ?? 0),
            'draft_count' => (int) ($statusCounts['draft'] ?? 0),
            'rejected_count' => (int) ($statusCounts['rejected'] ?? 0),
        ];

        $chartData = $this->performanceChart($user->id);

        $recentDesigns = (clone $designs)->with('product')->latest()->limit(6)->get();

        $recentActivities = $user->userNotifications()->latest()->limit(5)->get();
        $unreadNotificationsCount = $user->userNotifications()->where('is_read', false)->count();
        $accountNotice = match ($user->approvalStatus()) {
            'draft' => [
                'icon' => 'bi-person-vcard',
                'title' => 'أكمل ملفك الشخصي لتوثيق حسابك',
                'message' => 'أضف بياناتك المهنية ثم أرسل الملف إلى الإدارة للمراجعة.',
                'action' => 'إكمال الملف الشخصي',
            ],
            'rejected' => [
                'icon' => 'bi-exclamation-triangle',
                'title' => 'يحتاج ملفك الشخصي إلى تعديل',
                'message' => 'حدّث البيانات المطلوبة ثم أرسلها للمراجعة مرة أخرى.',
                'action' => 'تعديل الملف الشخصي',
            ],
            'submitted', 'under_review' => [
                'icon' => 'bi-hourglass-split',
                'title' => 'حسابك قيد مراجعة الإدارة',
                'message' => 'انتظر موافقة الإدارة قبل إنشاء التصاميم أو عرض الأرباح.',
                'action' => 'عرض الملف الشخصي',
            ],
            default => null,
        };

        return view('designer.dashboard', compact(
            'recentActivities',
            'recentDesigns',
            'stats',
            'unreadNotificationsCount',
            'accountNotice',
            'chartData',
        ));
    }

    /**
     * Sales and designer profit per bucket for the dashboard chart.
     * Cancelled orders are ignored.
     *
     * @return array<string, array{label: string, labels: list<string>, sales: list<float>, profit: list<float>}>
     */
    private function performanceChart(int $designerId): array
    {
        $now = now();
        $items = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.designer_id', $designerId)
            ->where('orders.status', '!=', 'cancelled')
            ->where('order_items.created_at', '>=', $now->copy()->subMonths(11)->startOfMonth())
            ->get(['order_items.created_at', 'order_items.total_price', 'order_items.designer_profit']);

        $build = function (array $buckets, callable $key) use ($items): array {
            $sales = array_fill_keys(array_keys($buckets), 0.0);
            $profit = $sales;

            foreach ($items as $item) {
                $bucket = $key(Carbon::parse($item->created_at));

                if (array_key_exists($bucket, $sales)) {
                    $sales[$bucket] += (float) $item->total_price;
                    $profit[$bucket] += (float) $item->designer_profit;
                }
            }

            return [
                'labels' => array_values($buckets),
                'sales' => array_map(fn ($value) => round($value, 2), array_values($sales)),
                'profit' => array_map(fn ($value) => round($value, 2), array_values($profit)),
            ];
        };

        $days = [];
        foreach (range(6, 0) as $ago) {
            $day = $now->copy()->subDays($ago);
            $days[$day->toDateString()] = $day->locale('ar')->translatedFormat('l');
        }

        $months = function (int $count) use ($now): array {
            $buckets = [];
            foreach (range($count - 1, 0) as $ago) {
                $month = $now->copy()->startOfMonth()->subMonths($ago);
                $buckets[$month->format('Y-m')] = $month->locale('ar')->translatedFormat('F');
            }

            return $buckets;
        };

        return [
            'week' => $build($days, fn (Carbon $date) => $date->toDateString()) + ['label' => 'ملخص الأداء خلال آخر 7 أيام'],
            'month' => $build($months(6), fn (Carbon $date) => $date->format('Y-m')) + ['label' => 'ملخص الأداء خلال آخر 6 أشهر'],
            'year' => $build($months(12), fn (Carbon $date) => $date->format('Y-m')) + ['label' => 'ملخص الأداء خلال آخر 12 شهرًا'],
        ];
    }
}
