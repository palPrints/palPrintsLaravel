<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Design;
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
        ));
    }
}
