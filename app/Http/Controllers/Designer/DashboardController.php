<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->hasRole('designer'), 403);

        $profile = $user->designerProfile;

        $stats = [
            'total_designs' => 0,
            'designs_this_month' => 0,
            'total_sales' => (int) ($profile?->total_sales ?? 0),
            'total_earnings' => (float) ($profile?->total_earnings ?? 0),
            'earnings_this_month' => 0.0,
            'average_rating' => 0.0,
            'ratings_count' => 0,
        ];

        $recentActivities = collect();
        $unreadNotificationsCount = 0;

        return view('designer.dashboard', compact(
            'recentActivities',
            'stats',
            'unreadNotificationsCount',
        ));
    }
}
