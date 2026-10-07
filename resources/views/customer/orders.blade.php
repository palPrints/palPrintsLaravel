{{--
    Ported from palPrintFront/orders.html. The original page had its own
    standalone sidebar/topbar shell (shared with custProfile.html); here it's
    fitted into the site's shared storefront header/sidebar (customer.layouts.app)
    instead, to stay visually consistent with the rest of the store.
    Wired to the real `orders`/`order_items` tables via CustomerOrdersController
    (no more hardcoded demo rows). The reference's ".orders-section" also
    carries an "app-card" class for its card background/border/shadow — that
    was missing here and is restored below.
--}}
@extends('customer.layouts.app')

@section('title', 'طلباتي')
@section('meta-description', 'متابعة طلبات عميل PalPrints الحالية والسابقة')
@section('body-class', 'storefront-page orders-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/orders.css') }}?v={{ filemtime(public_path('front/css/customer/orders.css')) }}">
@endpush

@section('content')
    <div class="page-heading orders-heading">
        <div>
            <div class="breadcrumbs">
                <a href="{{ route('home') }}">الرئيسية</a>
                <span>/</span>
                <span>طلباتي</span>
            </div>
            <h1 id="pageTitle">طلباتي</h1>
            <p>تابع طلباتك الحالية واستعرض سجل طلباتك السابقة.</p>
        </div>
        <span class="orders-total"><i class="bi bi-receipt"></i> {{ $totalCount }} طلبات</span>
    </div>

    @if(session('status') === 'order-cancelled')
        <div class="alert-success page-alert">تم إلغاء الطلب بنجاح.</div>
    @elseif(session('status') === 'order-not-cancellable')
        <div class="alert-danger page-alert">لا يمكن إلغاء هذا الطلب لأنه دخل حيز التنفيذ بالفعل.</div>
    @endif

    <section class="orders-content" aria-labelledby="pageTitle">
        @if($totalCount === 0)
            <section class="orders-section app-card orders-empty-state">
                <div class="orders-empty-state__inner">
                    <span class="orders-empty-state__icon"><i class="bi bi-receipt"></i></span>
                    <h2>لا يوجد لديك أي طلبات بعد</h2>
                    <p>عندما تطلبي منتجًا من المتجر، راح يظهر هون.</p>
                    <a class="btn-brand" href="{{ route('customer.store') }}">تصفحي المتجر <i class="bi bi-arrow-left" aria-hidden="true"></i></a>
                </div>
            </section>
        @else
            <section class="orders-section app-card" aria-labelledby="currentOrdersTitle">
                <div class="section-header"><div><span class="section-kicker">قيد المعالجة</span><h2 id="currentOrdersTitle">الطلبات الحالية</h2></div><span class="section-count">{{ $currentOrders->count() }} طلبات</span></div>
                <div class="orders-table-wrap">
                    <table class="orders-table">
                        <thead><tr><th>رقم الطلب</th><th>المنتج</th><th>تاريخ الطلب</th><th>الإجمالي</th><th>حالة الطلب</th><th>الإجراءات</th></tr></thead>
                        <tbody>
                            @forelse($currentOrders as $order)
                                <tr>
                                    <td data-label="رقم الطلب"><span class="order-number">#{{ $order['number'] }}</span></td>
                                    <td data-label="المنتج"><div class="product-cell">@if(!empty($order['mockup']))<span class="product-thumb">@include('customer.partials.cart-mockup', ['mockup' => $order['mockup'], 'alt' => $order['title']])</span>@else<img src="{{ $order['thumbnail'] }}" alt="{{ $order['title'] }}">@endif<div><strong>{{ $order['title'] }}</strong><span>{{ $order['subtitle'] }}</span></div></div></td>
                                    <td data-label="تاريخ الطلب"><time datetime="{{ $order['date_iso'] }}">{{ $order['date_human'] }}</time></td>
                                    <td data-label="الإجمالي"><strong class="price">{{ $order['total'] }}</strong></td>
                                    <td data-label="حالة الطلب"><span class="status {{ $order['status_class'] }}"><i class="bi {{ $order['status_icon'] }}"></i>{{ $order['status_label'] }}</span></td>
                                    <td data-label="الإجراءات">
                                        <div class="actions-group">
                                            <button class="details-button" type="button" data-order="{{ $order['number'] }}" data-date="{{ $order['date_human'] }}" data-total="{{ $order['total'] }}" data-status="{{ $order['status_label'] }}" data-status-class="{{ $order['status_class'] }}" data-status-icon="{{ $order['status_icon'] }}" data-address="{{ $order['address'] }}" data-payment="{{ $order['payment'] }}" data-items='@json($order['items'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button>
                                            @if($order['cancellable'])
                                                <button class="cancel-order" type="button" data-order="{{ $order['number'] }}" data-form="cancel-form-{{ $order['id'] }}"><i class="bi bi-trash3"></i>إلغاء الطلب</button>
                                                <form id="cancel-form-{{ $order['id'] }}" method="POST" action="{{ route('customer.orders.cancel', $order['id']) }}" hidden>
                                                    @csrf
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="orders-table-empty"><i class="bi bi-inbox" aria-hidden="true"></i> لا يوجد طلبات قيد المعالجة حاليًا.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="orders-section app-card" aria-labelledby="previousOrdersTitle">
                <div class="section-header"><div><span class="section-kicker">سجل الطلبات</span><h2 id="previousOrdersTitle">الطلبات السابقة</h2></div><span class="section-count">{{ $previousOrders->count() }} طلبات</span></div>
                <div class="orders-table-wrap">
                    <table class="orders-table previous-orders">
                        <thead><tr><th>رقم الطلب</th><th>المنتج</th><th>تاريخ الطلب</th><th>الإجمالي</th><th>حالة الطلب</th><th>الإجراءات</th></tr></thead>
                        <tbody>
                            @forelse($previousOrders as $order)
                                <tr>
                                    <td data-label="رقم الطلب"><span class="order-number">#{{ $order['number'] }}</span></td>
                                    <td data-label="المنتج"><div class="product-cell">@if(!empty($order['mockup']))<span class="product-thumb">@include('customer.partials.cart-mockup', ['mockup' => $order['mockup'], 'alt' => $order['title']])</span>@else<img src="{{ $order['thumbnail'] }}" alt="{{ $order['title'] }}">@endif<div><strong>{{ $order['title'] }}</strong><span>{{ $order['subtitle'] }}</span></div></div></td>
                                    <td data-label="تاريخ الطلب"><time datetime="{{ $order['date_iso'] }}">{{ $order['date_human'] }}</time></td>
                                    <td data-label="الإجمالي"><strong class="price">{{ $order['total'] }}</strong></td>
                                    <td data-label="حالة الطلب"><span class="status {{ $order['status_class'] }}"><i class="bi {{ $order['status_icon'] }}"></i>{{ $order['status_label'] }}</span></td>
                                    <td data-label="الإجراءات"><button class="details-button" type="button" data-order="{{ $order['number'] }}" data-date="{{ $order['date_human'] }}" data-total="{{ $order['total'] }}" data-status="{{ $order['status_label'] }}" data-status-class="{{ $order['status_class'] }}" data-status-icon="{{ $order['status_icon'] }}" data-address="{{ $order['address'] }}" data-payment="{{ $order['payment'] }}" data-items='@json($order['items'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="orders-table-empty"><i class="bi bi-inbox" aria-hidden="true"></i> لا يوجد طلبات سابقة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </section>

    <div class="modal-backdrop" id="cancelModal" hidden>
        <section class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalText">
            <button type="button" class="modal-close" aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
            <span class="modal-icon"><i class="bi bi-trash3"></i></span>
            <h2 id="modalTitle">إلغاء الطلب؟</h2><p id="modalText">هل أنت متأكد من إلغاء الطلب <strong id="modalOrderNumber"></strong>؟ لا يمكن التراجع عن هذا الإجراء.</p>
            <div class="modal-actions"><button type="button" class="btn-brand-outline" id="keepOrder">الاحتفاظ بالطلب</button><button type="button" class="danger-button" id="confirmCancel">نعم، إلغاء الطلب</button></div>
        </section>
    </div>

    <div class="modal-backdrop" id="orderDetailsModal" hidden>
        <section class="details-modal" role="dialog" aria-modal="true" aria-labelledby="detailsModalTitle">
            <button type="button" class="modal-close" aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
            <h2 id="detailsModalTitle">تفاصيل الطلب <span id="detailsOrderNumber"></span></h2>
            <div class="details-modal__items" id="detailsItems"></div>
            <dl class="details-modal__list">
                <div><dt>تاريخ الطلب</dt><dd id="detailsDate"></dd></div>
                <div><dt>عنوان التوصيل</dt><dd id="detailsAddress"></dd></div>
                <div><dt>طريقة الدفع</dt><dd id="detailsPayment"></dd></div>
                <div><dt>الإجمالي</dt><dd id="detailsTotal"></dd></div>
                <div><dt>حالة الطلب</dt><dd><span class="status" id="detailsStatus"></span></dd></div>
            </dl>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/orders.js') }}?v={{ filemtime(public_path('front/js/customer/orders.js')) }}"></script>
@endpush
