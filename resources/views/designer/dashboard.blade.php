@extends('designer.layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    <section class='designer-intro' aria-label='ابدأ تصميمًا جديدًا' data-i18n-aria='designerIntroLabel'>
        <div class='designer-intro-copy'>
            <h1 class='designer-intro-text'>
                <span class='designer-intro-title-main' data-i18n='heroTitleLine1'>حوّل أفكارك المبتكرة إلى تصاميم مذهلة</span>
                <span class='designer-intro-title-secondary' data-i18n='heroTitleLine2'>وابدأ الربح الآن!</span>
            </h1>

            <p class='designer-intro-subtitle' data-i18n='heroSubtitle'>
                أنشئ تصميمك القادم وشاركه مع عملاء يبحثون عن أفكار مميزة.
            </p>
        </div>

        <a href='{{ route('designer.designs.create') }}' class='designer-intro-action'>
            <i class='bi bi-palette' aria-hidden='true'></i>
            <span data-i18n='ctaAction'>ابدأ التصميم</span>
        </a>
    </section>

    <div class='stats-grid' aria-label='ملخص لوحة التحكم' data-i18n-aria='dashboardSummary'>
        <div class='stat-card'>
            <div class='stat-icon icon-purple'>
                <i class='bi bi-palette-fill' aria-hidden='true'></i>
            </div>

            <div class='stat-body'>
                <h2 class='stat-title' data-i18n='totalDesigns'>إجمالي التصاميم</h2>
                <strong class='stat-value counter' data-target='{{ $stats['total_designs'] }}'>0</strong>
                <div class='stat-trend positive'>
                    <i class='bi bi-arrow-up' aria-hidden='true'></i>
                    <span
                        data-copy-ar='+{{ number_format($stats['designs_this_month']) }} هذا الشهر'
                        data-copy-en='+{{ number_format($stats['designs_this_month']) }} this month'
                    >+{{ number_format($stats['designs_this_month']) }} هذا الشهر</span>
                </div>
            </div>
        </div>

        <div class='stat-card'>
            <div class='stat-icon icon-blue'>
                <i class='bi bi-cart-check-fill' aria-hidden='true'></i>
            </div>

            <div class='stat-body'>
                <h2 class='stat-title' data-i18n='totalSales'>إجمالي المبيعات</h2>
                <strong class='stat-value counter' data-target='{{ $stats['total_sales'] }}'>0</strong>
                <div class='stat-trend neutral'>
                    <span
                        data-copy-ar='من جميع تصاميمك'
                        data-copy-en='Across all your designs'
                    >من جميع تصاميمك</span>
                </div>
            </div>
        </div>

        <div class='stat-card'>
            <div class='stat-icon icon-indigo'>
                <i class='bi bi-wallet2' aria-hidden='true'></i>
            </div>

            <div class='stat-body'>
                <h2 class='stat-title' data-i18n='totalEarnings'>إجمالي الأرباح</h2>
                <strong class='stat-value'>
                    <span class='counter' data-target='{{ (int) round($stats['total_earnings']) }}'>0</span>
                    <span aria-label='شيكل'>₪</span>
                </strong>
                <div class='stat-trend positive'>
                    <i class='bi bi-arrow-up' aria-hidden='true'></i>
                    <span
                        data-copy-ar='+{{ number_format($stats['earnings_this_month'], 2) }} ₪ هذا الشهر'
                        data-copy-en='+₪{{ number_format($stats['earnings_this_month'], 2) }} this month'
                    >+{{ number_format($stats['earnings_this_month'], 2) }} ₪ هذا الشهر</span>
                </div>
            </div>
        </div>

        <div class='stat-card'>
            <div class='stat-icon icon-star'>
                <i class='bi bi-star-fill' aria-hidden='true'></i>
            </div>

            <div class='stat-body'>
                <h2 class='stat-title' data-i18n='averageRating'>متوسط التقييم</h2>
                <strong
                    class='stat-value'
                    data-copy-ar='{{ number_format($stats['average_rating'], 1) }} / 5'
                    data-copy-en='{{ number_format($stats['average_rating'], 1) }} / 5'
                >{{ number_format($stats['average_rating'], 1) }} / 5</strong>
                <div class='stat-trend neutral'>
                    <span
                        data-copy-ar='{{ number_format($stats['ratings_count']) }} تقييم'
                        data-copy-en='{{ number_format($stats['ratings_count']) }} ratings'
                    >{{ number_format($stats['ratings_count']) }} تقييم</span>
                </div>
            </div>
        </div>
    </div>

    <section class='trending-section'>
        <div class='section-header'>
            <div class='section-title-group'>
                <h2 class='section-title'>
                    <i class='bi bi-fire fire-icon' aria-hidden='true'></i>
                    <span data-i18n='gazaTrends'>ترند غزة</span>
                </h2>
                <p class='section-subtitle' data-i18n='trendsIntro'>
                    اكتشف الأفكار الرائجة الآن وحوّلها إلى تصاميم تلفت الانتباه
                </p>
            </div>

            <a href='{{ route('designer.trends') }}' class='view-more-btn'>
                <span data-i18n='viewMore'>عرض المزيد</span>
                <i class='bi bi-arrow-left' data-back-icon aria-hidden='true'></i>
            </a>
        </div>

        <div class='trending-grid'>
            <div class='dashboard-empty-state'>لا توجد ترندات مضافة حاليًا.</div>
        </div>
    </section>

    <section class='activity-section'>
        <div class='activity-header'>
            <h2 class='activity-title'>
                <i class='bi bi-lightning-charge-fill' aria-hidden='true'></i>
                <span data-i18n='recentActivity'>آخر النشاطات</span>
            </h2>
        </div>

        <div
            class='activity-table-wrapper'
            tabindex='0'
            aria-label='جدول آخر النشاطات، قابل للتمرير أفقياً'
            data-i18n-aria='activityTableLabel'
        >
            <table class='activity-table'>
                <thead>
                    <tr>
                        <th data-i18n='activity'>النشاط</th>
                        <th data-i18n='details'>التفاصيل</th>
                        <th data-i18n='time'>الوقت</th>
                        <th data-i18n='status'>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentActivities as $activity)
                        <tr>
                            <td class='activity-name'>{{ $activity->title }}</td>
                            <td>{{ $activity->message }}</td>
                            <td class='time'>{{ $activity->created_at->locale('ar')->diffForHumans() }}</td>
                            <td>
                                <span @class([
                                    'status-badge',
                                    'completed' => $activity->is_read,
                                    'processing' => ! $activity->is_read,
                                ])>
                                    <i @class([
                                        'bi',
                                        'bi-check-circle-fill' => $activity->is_read,
                                        'bi-clock' => ! $activity->is_read,
                                    ]) aria-hidden='true'></i>
                                    <span>{{ $activity->is_read ? 'مكتمل' : 'جديد' }}</span>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan='4'
                                class='activity-name'
                                data-copy-ar='لا توجد نشاطات حديثة حتى الآن.'
                                data-copy-en='No recent activity yet.'
                            >
                                لا توجد نشاطات حديثة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
