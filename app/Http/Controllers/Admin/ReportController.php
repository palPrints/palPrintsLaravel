<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Only the "users" report is backed by real data right now: it needs just the
 * users table. Sales/orders/profits need the orders tables, which are not
 * part of the current schema yet, so those tabs fall back to an unavailable
 * state until the tables return.
 */
class ReportController extends Controller
{
    private const PERIOD_DAYS = ['week' => 7, 'month' => 30, 'quarter' => 90, 'year' => 365];

    public function index(): View
    {
        return view('admin.reports', [
            'ordersAvailable' => Schema::hasTable('orders'),
            'userStats' => $this->userStats(),
        ]);
    }

    private function userStats(): array
    {
        $now = now();
        $totalNow = User::count();

        $stats = [];

        foreach (self::PERIOD_DAYS as $period => $days) {
            $cutoff = $now->copy()->subDays($days);
            $prevCutoff = $now->copy()->subDays($days * 2);

            $totalAtCutoff = User::where('created_at', '<=', $cutoff)->count();
            $newInPeriod = User::where('created_at', '>', $cutoff)->count();
            $newInPrevPeriod = User::whereBetween('created_at', [$prevCutoff, $cutoff])->count();
            $activeInPeriod = User::where('last_login_at', '>=', $cutoff)->count();
            $activeInPrevPeriod = User::whereBetween('last_login_at', [$prevCutoff, $cutoff])->count();

            $stats[$period] = [
                'total' => $totalNow,
                'totalChange' => $this->percentChange($totalNow, $totalAtCutoff),
                'newUsers' => $newInPeriod,
                'newUsersChange' => $this->percentChange($newInPeriod, $newInPrevPeriod),
                'activeUsers' => $activeInPeriod,
                'activeUsersChange' => $this->percentChange($activeInPeriod, $activeInPrevPeriod),
                'growthRate' => $totalAtCutoff > 0
                    ? (int) round($newInPeriod / $totalAtCutoff * 100)
                    : ($newInPeriod > 0 ? 100 : 0),
            ];
        }

        return $stats;
    }

    private function percentChange(int $current, int $previous): ?int
    {
        if ($previous <= 0) {
            return null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }
}
