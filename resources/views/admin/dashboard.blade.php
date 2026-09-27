@extends('admin.layouts.app')

@section('title', 'لوحة تحكم الإدارة')
@section('body-class', 'admin-dashboard-page')
@section('main-id', 'adminDashboardMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminDashboard.css').'?v='.filemtime(public_path('front/css/admin/adminDashboard.css')) }}">
@endpush

@php
    $roleLabels = [
        'designer' => ['label' => 'مصمم', 'class' => 'is-designer'],
        'print_provider' => ['label' => 'مطبعة', 'class' => 'is-printshop'],
        'delivery_partner' => ['label' => 'شريك توصيل', 'class' => 'is-designer'],
    ];
    $orderStatuses = [
        'pending' => ['label' => 'بانتظار التأكيد', 'class' => 'is-waiting'],
        'confirmed' => ['label' => 'قيد التنفيذ', 'class' => 'is-progress'],
        'processing' => ['label' => 'قيد التنفيذ', 'class' => 'is-progress'],
        'in_production' => ['label' => 'قيد التنفيذ', 'class' => 'is-progress'],
        'shipped' => ['label' => 'تم الشحن', 'class' => 'is-shipped'],
        'delivered' => ['label' => 'تم التسليم', 'class' => 'is-delivered'],
        'completed' => ['label' => 'تم التسليم', 'class' => 'is-delivered'],
        'cancelled' => ['label' => 'ملغي', 'class' => 'is-cancelled'],
    ];
    $designIcons = [['is-blue', 'bi-geo-alt'], ['is-orange', 'bi-star'], ['is-green', 'bi-vector-pen']];
    $avatarLetter = fn (?string $name): string => mb_substr(trim((string) $name), 0, 1) ?: '؟';
    $number = fn ($value): string => number_format((float) $value, 0, '.', ',');
@endphp

@section('content')
<main class="admin-main admin-reference-main" id="adminDashboardMain">
    <section class="admin-reference-heading" id="overview" aria-labelledby="dashboardTitle">
        <div>
            <h1 id="dashboardTitle">لوحة التحكم</h1>
            <p><time datetime="{{ now()->toDateString() }}">{{ $today }}</time></p>
        </div>
        <button class="admin-add-product" type="button" data-action="add-product">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>إضافة منتج جديد</span>
        </button>
    </section>

    <section class="admin-metric-grid" aria-label="ملخص أداء المنصة">
        @php
            $cards = [
                ['key' => 'users', 'icon' => 'bi-people', 'color' => 'is-blue', 'title' => 'إجمالي المستخدمين'],
                ['key' => 'orders', 'icon' => 'bi-cart3', 'color' => 'is-orange', 'title' => 'طلبات اليوم'],
                ['key' => 'revenue', 'icon' => 'bi-cash-coin', 'color' => 'is-green', 'title' => 'الإيرادات الشهرية'],
                ['key' => 'pending', 'icon' => 'bi-clock', 'color' => 'is-red', 'title' => 'بانتظار الاعتماد'],
            ];
        @endphp
        @foreach ($cards as $card)
            @php($metric = $metrics[$card['key']])
            <article class="admin-metric-card">
                <span class="admin-metric-icon {{ $card['color'] }}" aria-hidden="true"><i class="bi {{ $card['icon'] }}"></i></span>
                @if ($card['key'] === 'revenue')
                    <strong dir="ltr"><span data-admin-counter="{{ $metric['value'] }}">{{ $number($metric['value']) }}</span> ₪</strong>
                @else
                    <strong data-admin-counter="{{ $metric['value'] }}">{{ $number($metric['value']) }}</strong>
                @endif
                <h2>{{ $card['title'] }}</h2>
                @if ($card['key'] === 'pending')
                    <p>طلبات تحتاج إلى مراجعة</p>
                @elseif ($metric['trend'])
                    <p class="{{ $metric['trend']['direction'] === 'up' ? 'is-positive' : 'is-negative' }}">
                        <i class="bi bi-caret-{{ $metric['trend']['direction'] }}-fill" aria-hidden="true"></i>
                        {{ $metric['trend']['value'] }}%{{ $metric['trend']['direction'] === 'up' ? '+' : '-' }} {{ $metric['note'] }}
                    </p>
                @else
                    <p>لا توجد بيانات للمقارنة بعد</p>
                @endif
            </article>
        @endforeach
    </section>

    <div class="admin-analytics-grid">
        <section class="admin-reference-card admin-sales-card" id="reportsOverview" aria-labelledby="salesChartTitle">
            <header class="admin-reference-card__header">
                <div><h2 id="salesChartTitle">مبيعات آخر 12 شهر</h2><p>إجمالي الطلبات غير الملغية شهرياً</p></div>
                @if ($chart['growth'])
                    <span class="admin-growth-pill"><i class="bi bi-arrow-{{ $chart['growth']['direction'] }}" aria-hidden="true"></i> {{ $chart['growth']['direction'] === 'up' ? '+' : '-' }}{{ $chart['growth']['value'] }}%</span>
                @endif
            </header>
            <div class="admin-line-chart" role="img" aria-label="عدد الطلبات في الشهر الأخير {{ $chart['last'] }} طلباً">
                <svg viewBox="0 0 960 250" aria-hidden="true" focusable="false">
                    <g class="admin-line-chart__grid">
                        @foreach ($chart['ticks'] as $tick)
                            <line x1="30" y1="{{ $tick['y'] }}" x2="930" y2="{{ $tick['y'] }}"></line>
                        @endforeach
                    </g>
                    <g class="admin-line-chart__axis">
                        @foreach ($chart['ticks'] as $tick)
                            <text x="4" y="{{ $tick['y'] + 5 }}">{{ $tick['label'] }}</text>
                        @endforeach
                    </g>
                    <path class="admin-line-chart__area" d="{{ $chart['area'] }}"></path>
                    <path class="admin-line-chart__line" d="{{ $chart['line'] }}"></path>
                    <g class="admin-line-chart__points">
                        @foreach ($chart['points'] as $point)
                            <circle @class(['is-last' => $loop->last]) cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ $loop->last ? 7 : 5 }}"></circle>
                        @endforeach
                    </g>
                    @php($lastPoint = $chart['points']->last())
                    <g class="admin-line-chart__value"><rect x="{{ $lastPoint['x'] - 37 }}" y="{{ max($lastPoint['y'] - 30, -2) }}" width="74" height="32" rx="7"></rect><text x="{{ $lastPoint['x'] }}" y="{{ max($lastPoint['y'] - 9, 19) }}">{{ $lastPoint['count'] }}</text></g>
                </svg>
                <div class="admin-line-chart__months" aria-hidden="true">
                    @foreach ($chart['months'] as $month)
                        <span>{{ $month }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="admin-reference-card admin-users-distribution" id="recentUsers" aria-labelledby="usersDistributionTitle">
            <header class="admin-reference-card__header"><h2 id="usersDistributionTitle">توزيع المستخدمين</h2></header>
            <div class="admin-distribution-list">
                @foreach ($userGroups as $group)
                    <div class="admin-distribution-item">
                        <div><span>{{ $group['label'] }}</span><b>{{ $number($group['count']) }}</b></div>
                        <div class="admin-progress"><span class="{{ $group['color'] }}" style="--progress: {{ $group['percent'] }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="admin-review-grid" id="pendingTasks">
        <section class="admin-reference-card admin-review-panel" aria-labelledby="approvalRequestsTitle">
            <header class="admin-reference-card__header"><h2 id="approvalRequestsTitle">طلبات الاعتماد</h2><span class="admin-count-pill is-red">{{ $pendingApprovalsCount }} معلق</span></header>
            <div class="admin-review-list">
                @forelse ($approvalRequests as $approval)
                    @php($role = $roleLabels[$approval->role] ?? ['label' => $approval->role, 'class' => 'is-designer'])
                    <article class="admin-approval-item">
                        <span class="admin-review-avatar">{{ $avatarLetter($approval->user?->name) }}</span>
                        <div>
                            <strong>{{ $approval->user?->name ?? 'مستخدم محذوف' }}</strong>
                            <p><span class="admin-type-pill {{ $role['class'] }}">{{ $role['label'] }}</span> {{ optional($approval->submitted_at)->locale('ar')->diffForHumans() }}</p>
                        </div>
                        <button type="button" data-review="طلب {{ $approval->user?->name }}">مراجعة الطلب <i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                    </article>
                @empty
                    <p class="admin-empty-note">لا توجد طلبات اعتماد معلقة.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-reference-card admin-review-panel" aria-labelledby="designReviewTitle">
            <header class="admin-reference-card__header"><h2 id="designReviewTitle">تصاميم بانتظار المراجعة</h2><span class="admin-count-pill is-orange">{{ $pendingDesignsCount }} تصميم</span></header>
            <div class="admin-review-list">
                @forelse ($pendingDesigns as $design)
                    @php([$iconColor, $icon] = $designIcons[$loop->index % count($designIcons)])
                    <article class="admin-design-item">
                        <span class="admin-design-icon {{ $iconColor }}"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                        <div>
                            <strong>{{ $design->title }}</strong>
                            <p>{{ $design->designer?->name }}@if ($design->product) · {{ $design->product->name }}@endif</p>
                        </div>
                        <button type="button" data-review="{{ $design->title }}">مراجعة <i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                    </article>
                @empty
                    <p class="admin-empty-note">لا توجد تصاميم بانتظار المراجعة.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="admin-reference-card admin-orders-card" id="financialOverview" aria-labelledby="latestOrdersTitle">
        <header class="admin-reference-card__header"><h2 id="latestOrdersTitle">أحدث الطلبات</h2><button class="admin-view-all" type="button" data-action="view-all-orders">عرض الكل <i class="bi bi-chevron-left" aria-hidden="true"></i></button></header>
        <div class="admin-orders-table-wrap" tabindex="0" aria-label="جدول أحدث الطلبات، قابل للتمرير أفقياً">
            <table class="admin-orders-table">
                <thead><tr><th scope="col">رقم الطلب</th><th scope="col">العميل</th><th scope="col">المنتج</th><th scope="col">المبلغ</th><th scope="col">الحالة</th><th scope="col">التاريخ</th></tr></thead>
                <tbody id="ordersTableBody">
                    @foreach ($latestOrders as $order)
                        @php($status = $orderStatuses[$order->status] ?? ['label' => $order->status, 'class' => 'is-waiting'])
                        <tr data-order-row data-search="{{ $order->order_number }} {{ $order->customer }} {{ $order->product }} {{ $status['label'] }}">
                            <td><a href="#" dir="ltr">#{{ $order->order_number }}</a></td>
                            <td>{{ $order->customer }}</td>
                            <td>{{ $order->product ?: '—' }}</td>
                            <td><b dir="ltr">{{ number_format((float) $order->final_amount, 2) }} ₪</b></td>
                            <td><span class="admin-order-status {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                            <td><time datetime="{{ $order->created_at->toDateString() }}">{{ $order->created_at->locale('ar')->translatedFormat('j F Y') }}</time></td>
                        </tr>
                    @endforeach
                    <tr class="admin-orders-empty" id="emptyOrdersRow" @if ($latestOrders->isNotEmpty()) hidden @endif><td colspan="6"><i class="bi bi-cart-x" aria-hidden="true"></i><strong>{{ $latestOrders->isEmpty() ? 'لا توجد طلبات بعد' : 'لا توجد طلبات مطابقة' }}</strong><span>{{ $latestOrders->isEmpty() ? 'ستظهر الطلبات هنا فور وصولها.' : 'جرّب البحث باسم عميل أو رقم طلب آخر.' }}</span></td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminDashboard.js').'?v='.filemtime(public_path('front/js/admin/adminDashboard.js')) }}"></script>
@endpush
