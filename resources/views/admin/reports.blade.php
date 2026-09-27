@extends('admin.layouts.app')

@section('title', 'التقارير والإحصائيات')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminReportsStatistics.css').'?v='.filemtime(public_path('front/css/admin/adminReportsStatistics.css')) }}">
@endpush

@section('content')
<main class="admin-main admin-reports-main" id="adminReportsMain">
    <section class="reports-overview" aria-labelledby="reportsTitle">
        <div class="reports-heading-row">
            <h1 id="reportsTitle">التقارير والإحصائيات</h1>
        </div>

        <div class="reports-toolbar">
            <div class="report-tabs" role="tablist" aria-label="أنواع التقارير">
                <button type="button" role="tab" aria-selected="false" data-report="sales">إحصائيات المبيعات</button>
                <button type="button" role="tab" aria-selected="false" data-report="orders">إحصائيات الطلبات</button>
                <button type="button" role="tab" aria-selected="false" data-report="profits">الأرباح والعمولات</button>
                <button class="active" type="button" role="tab" aria-selected="true" data-report="users">تقارير المستخدمين</button>
            </div>

            <div class="reports-actions" aria-label="خيارات التقرير">
                <div class="period-switcher" role="group" aria-label="الفترة الزمنية">
                    <button type="button" data-period="week">أسبوع</button>
                    <button class="active" type="button" data-period="month">شهر</button>
                    <button type="button" data-period="quarter">٣ أشهر</button>
                    <button type="button" data-period="year">سنة</button>
                </div>
                <div class="export-menu-wrap">
                    <button class="export-report-button" id="exportReportButton" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="exportFormatMenu">
                        <i class="bi bi-download" aria-hidden="true"></i><span>تصدير</span><i class="bi bi-chevron-down export-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="export-format-menu" id="exportFormatMenu" role="menu" aria-label="اختر صيغة التصدير" hidden>
                        <button type="button" role="menuitem" data-export-format="xlsx"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i><span><strong>Excel</strong><small>ملف جدول قابل للتعديل</small></span></button>
                        <button type="button" role="menuitem" data-export-format="csv"><i class="bi bi-filetype-csv" aria-hidden="true"></i><span><strong>CSV</strong><small>بيانات مفصولة بفواصل</small></span></button>
                        <button type="button" role="menuitem" data-export-format="pdf"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i><span><strong>PDF</strong><small>حفظ من نافذة الطباعة</small></span></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-summary-grid" aria-label="ملخص التقرير">
            <article class="report-stat-card" data-stat-card="0">
                <p data-stat-title></p>
                <div class="report-stat-body">
                    <div>
                        <strong data-stat-value></strong>
                        <span class="report-change is-positive" data-stat-change hidden><i class="bi bi-caret-up-fill" aria-hidden="true"></i></span>
                        <small data-period-label></small>
                    </div>
                </div>
            </article>
            <article class="report-stat-card" data-stat-card="1">
                <p data-stat-title></p>
                <div class="report-stat-body">
                    <div>
                        <strong data-stat-value></strong>
                        <span class="report-change is-positive" data-stat-change hidden><i class="bi bi-caret-up-fill" aria-hidden="true"></i></span>
                        <small data-period-label></small>
                    </div>
                </div>
            </article>
            <article class="report-stat-card" data-stat-card="2">
                <p data-stat-title></p>
                <div class="report-stat-body">
                    <div>
                        <strong data-stat-value></strong>
                        <span class="report-change is-positive" data-stat-change hidden><i class="bi bi-caret-up-fill" aria-hidden="true"></i></span>
                        <small data-period-label></small>
                    </div>
                </div>
            </article>
            <article class="report-stat-card" data-stat-card="3">
                <p data-stat-title></p>
                <div class="report-stat-body">
                    <div>
                        <strong data-stat-value></strong>
                        <span class="report-change is-positive" data-stat-change hidden><i class="bi bi-caret-up-fill" aria-hidden="true"></i></span>
                        <small data-period-label></small>
                    </div>
                </div>
            </article>
        </div>

        @if ($ordersAvailable)
            <div class="reports-charts-grid" id="reportsChartsGrid">
                {{-- Product/monthly sales charts render here once order data is available. --}}
            </div>
            <div class="reports-breakdown-grid" id="reportsBreakdownGrid">
                {{-- City/revenue breakdown charts render here once order data is available. --}}
            </div>
        @else
            <div class="reports-unavailable" role="note">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>
                <strong>مخططات المبيعات غير متاحة حاليًا</strong>
                <span>تُضاف مخططات المبيعات والإيرادات عند تفعيل نظام الطلبات.</span>
            </div>
        @endif
    </section>
</main>
@endsection

@push('scripts')
    <script id="reportsUserStats" type="application/json">@json($userStats)</script>
    <script src="{{ asset('front/js/admin/adminReportsStatistics.js').'?v='.filemtime(public_path('front/js/admin/adminReportsStatistics.js')) }}"></script>
@endpush
