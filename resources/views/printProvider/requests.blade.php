@extends('printProvider.layouts.app')

@section('title', 'طلبات الطباعة')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/requests.css') }}?v={{ filemtime(public_path('front/css/printProvider/requests.css')) }}">
@endpush

@section('content')
    <section class="requests-overview" aria-labelledby="requestsTitle">
        <nav class="breadcrumb" aria-label="مسار التنقل">
            <a href="{{ route('print-provider.dashboard') }}">لوحة التحكم</a>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span>طلبات الطباعة</span>
        </nav>

        <header class="page-heading">
            <span class="heading-icon" aria-hidden="true"><i class="bi bi-clipboard2"></i></span>
            <div>
                <h1 id="requestsTitle">طلبات الطباعة</h1>
                <p>إدارة ومتابعة جميع طلبات الطباعة الواردة إلى مطبعتك.</p>
            </div>
        </header>

        <div class="request-stats">
            <article class="stat-card stat-completed">
                <div class="stat-card-head">
                    <span class="stat-label">مكتملة</span>
                    <span class="stat-icon" aria-hidden="true"><i class="bi bi-check-circle"></i></span>
                </div>
                <strong class="stat-number">48</strong>
                <p><b><i class="bi bi-graph-up-arrow"></i> 12%</b> مقارنة بالشهر الماضي</p>
            </article>

            <article class="stat-card stat-ready">
                <div class="stat-card-head">
                    <span class="stat-label">جاهزة للتسليم</span>
                    <span class="stat-icon" aria-hidden="true"><i class="bi bi-clock"></i></span>
                </div>
                <strong class="stat-number">15</strong>
                <p><b><i class="bi bi-graph-up-arrow"></i> 8%</b> مقارنة بالشهر الماضي</p>
            </article>

            <article class="stat-card stat-progress">
                <div class="stat-card-head">
                    <span class="stat-label">قيد التنفيذ</span>
                    <span class="stat-icon" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                </div>
                <strong class="stat-number">27</strong>
                <p><b><i class="bi bi-graph-up-arrow"></i> 18%</b> مقارنة بالشهر الماضي</p>
            </article>

            <article class="stat-card stat-new">
                <div class="stat-card-head">
                    <span class="stat-label">طلبات جديدة</span>
                    <span class="stat-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                </div>
                <strong class="stat-number">32</strong>
                <p><b><i class="bi bi-graph-up-arrow"></i> 25%</b> مقارنة بالشهر الماضي</p>
            </article>
        </div>

        <section class="orders-panel" aria-label="قائمة طلبات الطباعة">
            <div class="orders-toolbar">
                <div class="status-tabs" role="tablist" aria-label="تصفية الطلبات حسب الحالة">
                    <button class="active" type="button" role="tab" aria-selected="true" data-status="all">الكل</button>
                    <button type="button" role="tab" aria-selected="false" data-status="new">جديدة</button>
                    <button type="button" role="tab" aria-selected="false" data-status="progress">قيد التنفيذ</button>
                    <button type="button" role="tab" aria-selected="false" data-status="ready">جاهزة للتسليم</button>
                    <button type="button" role="tab" aria-selected="false" data-status="completed">مكتملة</button>
                    <button type="button" role="tab" aria-selected="false" data-status="rejected">مرفوضة</button>
                    <button type="button" role="tab" aria-selected="false" data-status="returned">معادة</button>
                </div>

                <div class="filter-wrap">
                    <button class="filter-button" id="ordersFilterButton" type="button" aria-expanded="false" aria-controls="ordersFilterMenu">
                        <i class="bi bi-sliders" aria-hidden="true"></i>
                        فلترة
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="filter-menu" id="ordersFilterMenu" hidden>
                        <strong>الفترة الزمنية</strong>
                        <label class="date-filter-field">من تاريخ<input id="ordersDateFrom" type="date"></label>
                        <label class="date-filter-field">إلى تاريخ<input id="ordersDateTo" type="date"></label>
                        <p class="date-filter-error" id="dateFilterError" hidden>تاريخ البداية يجب أن يسبق تاريخ النهاية.</p>
                        <div class="filter-actions">
                            <button id="clearDateFilter" type="button">مسح</button>
                            <button class="apply-filter" id="applyDateFilter" type="button">تطبيق</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="orders-table-wrap">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>المنتج</th>
                            <th>الكمية</th>
                            <th>قيمة الطلب</th>
                            <th>تاريخ الطلب</th>
                            <th>موعد التسليم</th>
                            <th>الحالة</th>
                            <th>التنبيه</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr data-status="new" data-order-date="2026-09-12">
                            <td><a class="order-number" href="#">#10358</a></td>
                            <td><strong>تيشيرت قطن</strong><small>2 منتجات</small></td>
                            <td>50</td><td class="price">$120.00</td><td>12 سبتمبر 2026</td><td>15 سبتمبر 2026</td>
                            <td><span class="order-status is-new">جديدة</span></td><td class="no-alert">—</td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="progress" data-order-date="2026-09-11">
                            <td><a class="order-number" href="#">#10357</a></td>
                            <td><strong>هودي</strong><small>4 منتجات</small></td>
                            <td>20</td><td class="price">$240.00</td><td>11 سبتمبر 2026</td><td>14 سبتمبر 2026</td>
                            <td><span class="order-status is-progress">قيد التنفيذ</span></td><td class="no-alert">—</td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="ready" data-order-date="2026-09-10">
                            <td><a class="order-number" href="#">#10356</a></td>
                            <td><strong>أكواب سيراميك</strong><small>1 منتج</small></td>
                            <td>100</td><td class="price">$350.00</td><td>10 سبتمبر 2026</td><td>12 سبتمبر 2026</td>
                            <td><span class="order-status is-ready">جاهزة للتسليم</span></td>
                            <td><span class="alert-text is-warning"><i class="bi bi-exclamation-triangle"></i> موعد التسليم قريب</span></td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="completed" data-order-date="2026-09-09">
                            <td><a class="order-number" href="#">#10355</a></td>
                            <td><strong>ستيكرات</strong><small>1 منتج</small></td>
                            <td>200</td><td class="price">$80.00</td><td>9 سبتمبر 2026</td><td>10 سبتمبر 2026</td>
                            <td><span class="order-status is-completed">مكتملة</span></td><td class="no-alert">—</td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="rejected" data-order-date="2026-09-08">
                            <td><a class="order-number" href="#">#10354</a></td>
                            <td><strong>طباعة ورق</strong><small>3 منتجات</small></td>
                            <td>500</td><td class="price">$150.00</td><td>8 سبتمبر 2026</td><td>11 سبتمبر 2026</td>
                            <td><span class="order-status is-rejected">مرفوضة</span></td><td class="no-alert">—</td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="progress" data-order-date="2026-09-07">
                            <td><a class="order-number" href="#">#10353</a></td>
                            <td><strong>تيشيرت قطن</strong><small>2 منتجات</small></td>
                            <td>30</td><td class="price">$75.00</td><td>7 سبتمبر 2026</td><td>9 سبتمبر 2026</td>
                            <td><span class="order-status is-progress">قيد التنفيذ</span></td><td class="no-alert">—</td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="new" data-order-date="2026-09-06">
                            <td><a class="order-number" href="#">#10352</a></td>
                            <td><strong>أكواب سيراميك</strong><small>1 منتج</small></td>
                            <td>80</td><td class="price">$280.00</td><td>6 سبتمبر 2026</td><td>8 سبتمبر 2026</td>
                            <td><span class="order-status is-new">جديدة</span></td><td class="no-alert">—</td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="ready" data-order-date="2026-09-05">
                            <td><a class="order-number" href="#">#10351</a></td>
                            <td><strong>هودي</strong><small>1 منتج</small></td>
                            <td>25</td><td class="price">$300.00</td><td>5 سبتمبر 2026</td><td>5 سبتمبر 2026</td>
                            <td><span class="order-status is-ready">جاهزة للتسليم</span></td>
                            <td><span class="alert-text is-late"><i class="bi bi-exclamation-circle"></i> متأخر عن موعد التسليم</span></td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="completed" data-order-date="2026-09-04">
                            <td><a class="order-number" href="#">#10350</a></td>
                            <td><strong>ستيكرات</strong><small>1 منتج</small></td>
                            <td>150</td><td class="price">$60.00</td><td>4 سبتمبر 2026</td><td>6 سبتمبر 2026</td>
                            <td><span class="order-status is-completed">مكتملة</span></td><td class="no-alert">—</td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr data-status="progress" data-order-date="2026-09-03">
                            <td><a class="order-number" href="#">#10349</a></td>
                            <td><strong>طباعة ورق</strong><small>1 منتج</small></td>
                            <td>300</td><td class="price">$210.00</td><td>3 سبتمبر 2026</td><td>5 سبتمبر 2026</td>
                            <td><span class="order-status is-progress">قيد التنفيذ</span></td><td class="no-alert">—</td>
                            <td><button class="request-details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                        </tr>
                        <tr class="empty-orders-row" hidden><td colspan="9">لا توجد طلبات ضمن الفلاتر المحددة.</td></tr>
                    </tbody>
                </table>
            </div>

            <nav class="table-pagination" aria-label="صفحات الطلبات">
                <button type="button" aria-label="الصفحة السابقة"><i class="bi bi-chevron-right"></i></button>
                <button class="active" type="button" aria-current="page">1</button>
                <button type="button">2</button>
                <button type="button">3</button>
                <button type="button" aria-label="الصفحة التالية"><i class="bi bi-chevron-left"></i></button>
            </nav>
        </section>
    </section>

    <dialog class="order-dialog" id="orderDetailsDialog" aria-labelledby="orderDialogTitle">
        <header class="order-dialog-head">
            <div>
                <span><i class="bi bi-receipt"></i></span>
                <div><small>تفاصيل الطلب</small><h2 id="orderDialogTitle"></h2></div>
            </div>
            <button type="button" data-dialog-close aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
        </header>
        <div class="order-dialog-content" id="orderDialogContent"></div>
        <footer><button class="dialog-close-button" type="button" data-dialog-close>إغلاق</button></footer>
    </dialog>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/printProvider/requests.js') }}"></script>
@endpush
