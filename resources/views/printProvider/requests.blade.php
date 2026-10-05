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
                <strong class="stat-number">{{ $counts['completed'] }}</strong>
                <p>طلبات تم تسليمها</p>
            </article>

            <article class="stat-card stat-ready">
                <div class="stat-card-head">
                    <span class="stat-label">جاهزة للتسليم</span>
                    <span class="stat-icon" aria-hidden="true"><i class="bi bi-clock"></i></span>
                </div>
                <strong class="stat-number">{{ $counts['ready'] }}</strong>
                <p>بانتظار استلام شركة التوصيل</p>
            </article>

            <article class="stat-card stat-progress">
                <div class="stat-card-head">
                    <span class="stat-label">قيد التنفيذ</span>
                    <span class="stat-icon" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                </div>
                <strong class="stat-number">{{ $counts['progress'] }}</strong>
                <p>طلبات قبلتها وتعمل عليها</p>
            </article>

            <article class="stat-card stat-new">
                <div class="stat-card-head">
                    <span class="stat-label">طلبات جديدة</span>
                    <span class="stat-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                </div>
                <strong class="stat-number">{{ $counts['new'] }}</strong>
                <p>بانتظار قبولك أو رفضك</p>
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
                            <th>مستحقاتك</th>
                            <th>تاريخ الطلب</th>
                            <th>الحالة</th>
                            <th>التنبيه</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        @foreach ($orders as $order)
                            <tr data-status="{{ $order['tab'] }}" data-order-date="{{ $order['date'] }}" data-order-id="{{ $order['id'] }}">
                                <td><span class="order-number">#{{ $order['number'] }}</span></td>
                                <td><strong>{{ $order['product'] }}</strong><small>{{ $order['products_count'] }} منتج</small></td>
                                <td>{{ $order['quantity'] }}</td>
                                <td class="price">{{ number_format($order['value'], 2) }} ₪</td>
                                <td>{{ $order['date_label'] }}</td>
                                <td><span class="order-status {{ $order['status_class'] }}">{{ $order['status_label'] }}</span></td>
                                @if ($order['alert'])
                                    <td><span class="alert-text is-warning"><i class="bi bi-exclamation-triangle"></i> {{ $order['alert'] }}</span></td>
                                @else
                                    <td class="no-alert">—</td>
                                @endif
                                <td><button class="details-button" type="button"><i class="bi bi-eye"></i> عرض التفاصيل</button></td>
                            </tr>
                        @endforeach
                        <tr class="empty-orders-row" @if ($orders->isNotEmpty()) hidden @endif><td colspan="8"><i class="bi bi-inbox" aria-hidden="true"></i><span>{{ $orders->isEmpty() ? 'لا توجد طلبات واردة بعد.' : 'لا توجد طلبات ضمن الفلاتر المحددة.' }}</span></td></tr>
                    </tbody>
                </table>
            </div>
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
        <footer id="orderDialogFooter"><button class="dialog-close-button" type="button" data-dialog-close>إغلاق</button></footer>
    </dialog>
@endsection

@push('scripts')
    <script>
        window.printProviderOrders = @json($orders->keyBy('id'));
        window.printProviderRoutes = {
            accept: @json(route('print-provider.requests.accept', ['order' => '__ID__'])),
            reject: @json(route('print-provider.requests.reject', ['order' => '__ID__'])),
            ready: @json(route('print-provider.requests.ready', ['order' => '__ID__']))
        };
    </script>
    <script src="{{ asset('front/js/printProvider/requests.js') }}"></script>
@endpush
