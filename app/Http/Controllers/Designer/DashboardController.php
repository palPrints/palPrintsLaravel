<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->hasRole('designer'), 403);

        $profile = $user->designerProfile;

        $designs = Schema::hasTable('designs')
            ? DB::table('designs')->where('designer_id', $user->id)
            : null;
        $designsThisMonth = $designs
            ? (clone $designs)->where('created_at', '>=', now()->startOfMonth())->count()
            : 0;

        $stats = [
            'total_designs' => $designs?->count() ?? 0,
            'designs_this_month' => $designsThisMonth,
            'total_sales' => (int) ($profile?->total_sales ?? 0),
            'total_earnings' => (float) ($profile?->total_earnings ?? 0),
            'earnings_this_month' => 0.0,
            'average_rating' => 0.0,
            'ratings_count' => 0,
        ];

        $recentActivities = Schema::hasTable('user_notifications')
            ? Notification::where('user_id', $user->id)->latest()->take(8)->get()
            : collect();
        $unreadNotificationsCount = $recentActivities->where('is_read', false)->count();

        return view('designer.dashboard', compact(
            'recentActivities',
            'stats',
            'unreadNotificationsCount',
        ));
    }
}
