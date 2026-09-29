@extends('designer.layouts.app')

@section('title', 'لوحة التحكم')
@section('body-class', 'dashboard-page')

@push('styles')
    <link rel='stylesheet' href='{{ asset('front/designer/css/dashboard.css') }}?v={{ filemtime(public_path('front/designer/css/dashboard.css')) }}'>
@endpush

@php
    $total = max((int) $stats['total_designs'], 1);
    $publishedPct = round($stats['published_count'] / $total * 100, 2);
    $reviewPct = round($stats['review_count'] / $total * 100, 2);
    $draftPct = round($stats['draft_count'] / $total * 100, 2);
    $c1 = $publishedPct;
    $c2 = $c1 + $reviewPct;
    $c3 = $c2 + $draftPct;
    $donutGradient = $stats['total_designs'] > 0
        ? "conic-gradient(var(--pp-blue-600) 0 {$c1}%, var(--pp-warning) {$c1}% {$c2}%, var(--pp-navy-400) {$c2}% {$c3}%, var(--pp-danger) {$c3}% 100%)"
        : 'conic-gradient(var(--pp-navy-200) 0 100%)';
    $donutLabel = "{$stats['published_count']} منشور، {$stats['review_count']} قيد المراجعة، {$stats['draft_count']} مسودة، {$stats['rejected_count']} مرفوض";

    $statusMeta = [
        'published' => ['label' => 'منشور', 'class' => 'is-published'],
        'review' => ['label' => 'قيد المراجعة', 'class' => 'is-review'],
        'draft' => ['label' => 'مسودة', 'class' => 'is-draft'],
        'rejected' => ['label' => 'مرفوض', 'class' => 'is-rejected'],
    ];
@endphp

@section('content')
<main class='designer-content-main dashboard-stack' id='designerMain'>
    @include('designer.partials.flash')

    @if ($accountNotice && ! session('warning'))
        <section class='designer-flash designer-flash-notice' role='status' aria-live='polite'>
            <i class='bi {{ $accountNotice['icon'] }}' aria-hidden='true'></i>
            <div class='designer-flash-copy'>
                <strong>{{ $accountNotice['title'] }}</strong>
                <small>{{ $accountNotice['message'] }}</small>
            </div>
            <a href='{{ route('designer.profile') }}'>{{ $accountNotice['action'] }}</a>
        </section>
    @endif

    <div class="designer-breadcrumb">
        <span>الرئيسية</span>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span>لوحة التحكم</span>
    </div>

    <header class="dashboard-page-heading">
        <div class="dashboard-heading-group">
            <span class="dashboard-heading-icon"><i class="bi bi-grid" aria-hidden="true"></i></span>
            <div>
                <h1>لوحة تحكم المصمم</h1>
                <p>تابع تصاميمك وطلباتك ومبيعاتك من مكان واحد.</p>
            </div>
        </div>
        @if (auth()->user()->hasApprovedBusinessAccount())
            <a class="dashboard-primary-action" href="{{ route('designer.designs.create') }}">
                <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                <span>رفع تصميم جديد</span>
            </a>
        @else
            <a class="dashboard-primary-action" href="{{ route('designer.profile') }}">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <span>أكمل التوثيق أولًا</span>
            </a>
        @endif
    </header>

    <section class="stats-grid designer-summary-grid" aria-label="ملخص أداء المصمم">
        <article class="stat-card is-blue">
            <span class="stat-icon"><i class="bi bi-images" aria-hidden="true"></i></span>
            <div class="stat-body">
                <h2 class="stat-title">إجمالي التصاميم</h2>
                <strong class="stat-value counter" data-target="{{ $stats['total_designs'] }}">0</strong>
                <p class="stat-trend neutral">{{ $stats['published_count'] }} منشورًا، {{ $stats['review_count'] }} قيد المراجعة</p>
            </div>
        </article>
        <article class="stat-card is-green">
            <span class="stat-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
            <div class="stat-body">
                <h2 class="stat-title">إجمالي المبيعات</h2>
                <strong class="stat-value counter" data-target="{{ $stats['total_sales'] }}">0</strong>
                <p class="stat-trend neutral">من جميع تصاميمك</p>
            </div>
        </article>
        <article class="stat-card is-blue">
            <span class="stat-icon"><i class="bi bi-currency-dollar" aria-hidden="true"></i></span>
            <div class="stat-body">
                <h2 class="stat-title">إجمالي الأرباح</h2>
                <strong class="stat-value"><span class="counter" data-target="{{ (int) round($stats['total_earnings']) }}">0</span> ₪</strong>
                <p class="stat-trend positive"><i class="bi bi-arrow-up" aria-hidden="true"></i> {{ number_format($stats['earnings_this_month'], 2) }} ₪ هذا الشهر</p>
            </div>
        </article>
        <article class="stat-card is-amber">
            <span class="stat-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
            <div class="stat-body">
                <h2 class="stat-title">الرصيد المتاح</h2>
                <strong class="stat-value"><span class="counter" data-target="{{ (int) round($stats['available_balance']) }}">0</span> ₪</strong>
                <a class="stat-card-link" href="{{ route('designer.earnings') }}">إدارة الأرباح</a>
            </div>
        </article>
    </section>

    <div class="dashboard-insights-grid">
        <section class="dashboard-panel performance-panel" aria-labelledby="performanceTitle">
            <header class="dashboard-panel-head">
                <div><h2 id="performanceTitle">أداء المبيعات والأرباح</h2><p>ملخص الأداء خلال آخر 6 أشهر</p></div>
                <div class="chart-periods" role="group" aria-label="الفترة الزمنية">
                    <button type="button" data-chart-period="week">أسبوع</button>
                    <button type="button" class="active" data-chart-period="month">6 أشهر</button>
                    <button type="button" data-chart-period="year">سنة</button>
                </div>
            </header>
            <div class="chart-legend" aria-hidden="true"><span><i class="is-sales"></i> المبيعات</span><span><i class="is-profit"></i> الأرباح</span></div>
            <div class="designer-performance-chart" role="img" aria-label="رسم بياني يوضح نمو المبيعات والأرباح خلال ستة أشهر">
                <div class="chart-y-axis" aria-hidden="true"><span>100</span><span>75</span><span>50</span><span>25</span><span>0</span></div>
                <div class="chart-canvas">
                    <span class="chart-grid-line" style="--line: 0"></span><span class="chart-grid-line" style="--line: 1"></span><span class="chart-grid-line" style="--line: 2"></span><span class="chart-grid-line" style="--line: 3"></span><span class="chart-grid-line" style="--line: 4"></span>
                    <svg viewBox="0 0 720 220" preserveAspectRatio="none" aria-hidden="true">
                        <defs><linearGradient id="salesArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1677ff" stop-opacity=".22"/><stop offset="1" stop-color="#1677ff" stop-opacity="0"/></linearGradient></defs>
                        <path class="chart-area" d="M10 185 C80 176,110 158,150 164 S240 130,290 138 S380 99,430 112 S520 70,575 82 S660 38,710 46 L710 218 L10 218 Z"></path>
                        <path class="chart-line is-sales" d="M10 185 C80 176,110 158,150 164 S240 130,290 138 S380 99,430 112 S520 70,575 82 S660 38,710 46"></path>
                        <path class="chart-line is-profit" d="M10 203 C80 196,110 185,150 190 S240 165,290 171 S380 139,430 148 S520 112,575 122 S660 86,710 94"></path>
                        <circle class="chart-sales-marker" id="salesChartMarker" cx="10" cy="185" r="6"></circle>
                    </svg>
                    <div class="chart-months" aria-hidden="true"><span>أبريل</span><span>مايو</span><span>يونيو</span><span>يوليو</span><span>أغسطس</span><span>سبتمبر</span></div>
                </div>
            </div>
        </section>

        <section class="dashboard-panel design-status-panel" aria-labelledby="designStatusTitle">
            <header class="dashboard-panel-head"><div><h2 id="designStatusTitle">حالة التصاميم</h2><p>توزيع جميع تصاميمك</p></div></header>
            <div class="design-status-visual">
                <div class="design-donut" role="img" aria-label="{{ $donutLabel }}" style="--design-donut-gradient: {{ $donutGradient }}">
                    <span><strong class="counter" data-target="{{ $stats['total_designs'] }}">0</strong><small>تصميمًا</small></span>
                </div>
                <ul class="design-status-list">
                    <li><span><i class="is-published"></i> منشور</span><strong class="counter" data-target="{{ $stats['published_count'] }}">0</strong></li>
                    <li><span><i class="is-review"></i> قيد المراجعة</span><strong class="counter" data-target="{{ $stats['review_count'] }}">0</strong></li>
                    <li><span><i class="is-draft"></i> مسودة</span><strong class="counter" data-target="{{ $stats['draft_count'] }}">0</strong></li>
                    <li><span><i class="is-rejected"></i> مرفوض</span><strong class="counter" data-target="{{ $stats['rejected_count'] }}">0</strong></li>
                </ul>
            </div>
        </section>
    </div>

    <section class="dashboard-panel designer-designs-panel" aria-labelledby="myDesignsTitle">
        <header class="dashboard-panel-head dashboard-panel-head--split">
            <div><h2 id="myDesignsTitle">تصاميمي</h2><p>أحدث التصاميم وحالة النشر الخاصة بكل تصميم</p></div>
            <a href="{{ route('designer.designs.index') }}">عرض كل التصاميم <i class="bi bi-arrow-left" aria-hidden="true"></i></a>
        </header>
        <div class="designer-table-wrap" tabindex="0" aria-label="جدول تصاميم المصمم، قابل للتمرير أفقيًا">
            <table class="designer-data-table">
                <thead><tr><th>التصميم</th><th>رقم التصميم</th><th>المنتج</th><th>تاريخ الإنشاء</th><th>الحالة</th><th><span class="visually-hidden">الإجراءات</span></th></tr></thead>
                <tbody>
                    @forelse ($recentDesigns as $design)
                        @php $meta = $statusMeta[$design->status] ?? $statusMeta['draft']; @endphp
                        <tr>
                            <td>
                                <div class="design-cell">
                                    <img src="{{ $design->image ?: (filled($design->product?->image) ? (preg_match('#^(https?:)?//#', $design->product->image) ? $design->product->image : asset($design->product->image)) : asset('front/designer/assets/images/file.png')) }}" alt="معاينة تصميم {{ $design->title }}">
                                    <span><strong>{{ $design->title }}</strong><small>{{ $design->product?->name }}</small></span>
                                </div>
                            </td>
                            <td dir="ltr">#DSG-{{ $design->id }}</td>
                            <td>{{ $design->product?->name ?? '—' }}</td>
                            <td><time>{{ $design->created_at?->locale('ar')->translatedFormat('d F Y') }}</time></td>
                            <td><span class="dashboard-status {{ $meta['class'] }}"><i></i> {{ $meta['label'] }}</span></td>
                            <td><a class="table-action" href="{{ route('designer.designs.review', ['id' => $design->id]) }}" aria-label="إدارة تصميم {{ $design->title }}"><i class="bi bi-three-dots" aria-hidden="true"></i></a></td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
            @if ($recentDesigns->isEmpty())
                <div class="dashboard-empty-state"><i class="bi bi-images" aria-hidden="true"></i><strong> لا يوجد تصاميم بعد</strong><span>ابدأ برفع أول تصميم إلك.</span></div>
            @endif
        </div>
    </section>

    <section class="dashboard-panel gaza-trends-panel" aria-labelledby="gazaTrendsTitle">
        <header class="dashboard-panel-head dashboard-panel-head--split gaza-trends-head">
            <div class="gaza-trends-heading">
                <div>
                    <h2 id="gazaTrendsTitle">الأكثر طلبًا</h2>
                    <p>استلهم منتجك القادم من حركة السوق.</p>
                </div>
            </div>
            <span class="trends-live-badge"><i aria-hidden="true"></i> يُحدّث الآن</span>
        </header>

        <div class="trending-products-grid">
            <article class="trend-card trending-product-card">
                <div class="trending-product-media">
                    <span class="trend-rank">#1</span>
                    <span class="trend-growth"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> +38%</span>
                    <img src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&q=80" alt="تيشيرت بطباعة مخصصة" loading="lazy">
                </div>
                <div class="trending-product-body">
                    <div class="trending-product-title"><h3>تيشيرت أوفر سايز</h3><span>الأكثر رواجًا</span></div>
                    <p>التصاميم الوطنية والخط العربي تتصدر الطلب هذا الأسبوع.</p>
                    <a href="{{ route('designer.designs.create') }}"><i class="bi bi-palette" aria-hidden="true"></i> صمّم لهذا المنتج</a>
                </div>
            </article>

            <article class="trend-card trending-product-card">
                <div class="trending-product-media">
                    <span class="trend-rank">#2</span>
                    <span class="trend-growth"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> +29%</span>
                    <img src="https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=600&q=80" alt="هودي بطباعة مخصصة" loading="lazy">
                </div>
                <div class="trending-product-body">
                    <div class="trending-product-title"><h3>هودي شتوي</h3><span>صاعد بسرعة</span></div>
                    <p>رسومات غزة البسيطة والألوان الدافئة تحقق أفضل تفاعل.</p>
                    <a href="{{ route('designer.designs.create') }}"><i class="bi bi-palette" aria-hidden="true"></i> صمّم لهذا المنتج</a>
                </div>
            </article>

            <article class="trend-card trending-product-card">
                <div class="trending-product-media">
                    <span class="trend-rank">#3</span>
                    <span class="trend-growth"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> +24%</span>
                    <img src="https://images.unsplash.com/photo-1544816155-12df9643f363?w=600&q=80" alt="حقيبة قماش بطباعة مخصصة" loading="lazy">
                </div>
                <div class="trending-product-body">
                    <div class="trending-product-title"><h3>حقيبة قماش</h3><span>اختيار الجمهور</span></div>
                    <p>الزخارف التراثية والعبارات القصيرة هي الأكثر حفظًا ومشاركة.</p>
                    <a href="{{ route('designer.designs.create') }}"><i class="bi bi-palette" aria-hidden="true"></i> صمّم لهذا المنتج</a>
                </div>
            </article>

            <article class="trend-card trending-product-card">
                <div class="trending-product-media">
                    <span class="trend-rank">#4</span>
                    <span class="trend-growth"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> +17%</span>
                    <img src="https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=600&q=80" alt="كوب بطباعة مخصصة" loading="lazy">
                </div>
                <div class="trending-product-body">
                    <div class="trending-product-title"><h3>كوب مطبوع</h3><span>هدية رائجة</span></div>
                    <p>عبارات الأمل ورسومات المعالم الفلسطينية تتقدم في الهدايا.</p>
                    <a href="{{ route('designer.designs.create') }}"><i class="bi bi-palette" aria-hidden="true"></i> صمّم لهذا المنتج</a>
                </div>
            </article>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script src='{{ asset('front/designer/js/dashboard.js') }}?v={{ filemtime(public_path('front/designer/js/dashboard.js')) }}'></script>
@endpush
