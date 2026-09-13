{{--
    Ported from palPrintFront/orders.html. The original page had its own
    standalone sidebar/topbar shell (shared with custProfile.html); here it's
    fitted into the site's shared storefront header/sidebar (customer.layouts.app)
    instead, to stay visually consistent with the rest of the store.
    The orders (current + previous) are still hardcoded demo data — same
    "demo data" situation the other product pages have before this is wired
    to a real orders table.
--}}
@extends('customer.layouts.app')

@section('title', 'طلباتي')
@section('meta-description', 'متابعة طلبات عميل PalPrints الحالية والسابقة')
@section('body-class', 'storefront-page orders-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/orders.css') }}?v={{ filemtime(public_path('front/css/customer/orders.css')) }}">
@endpush

@php
    $order1048Items = [
        ['image' => asset('front/assets/images/customer/orders/icons8-sticker-48.png'), 'title' => 'ستيكرات خطوط', 'desc' => 'حزمة ملصقات', 'qty' => 1, 'price' => '10 شيكل'],
    ];
    $order1047Items = [
        ['image' => asset('front/assets/images/customer/orders/t-shirt.png'), 'title' => 'تيشيرت فلسطين', 'desc' => 'طباعة مخصصة', 'qty' => 1, 'price' => '30 شيكل'],
        ['image' => asset('front/assets/images/customer/orders/icons8-sticker-48.png'), 'title' => 'ستيكرات خطوط', 'desc' => 'حزمة ملصقات', 'qty' => 1, 'price' => '15 شيكل'],
    ];
    $order1046Items = [
        ['image' => asset('front/assets/images/customer/orders/cup.webp'), 'title' => 'كوب مطبوع', 'desc' => 'كوب خزفي', 'qty' => 1, 'price' => '25 شيكل'],
    ];
    $order1045Items = [
        ['image' => asset('front/assets/images/customer/orders/posterposter.png'), 'title' => 'ستيكرات فيكتور', 'desc' => 'قص مخصص', 'qty' => 1, 'price' => '20 شيكل'],
    ];
    $order1040Items = [
        ['image' => asset('front/assets/images/customer/orders/hoodie-black.png'), 'title' => 'هودي مطبوع', 'desc' => 'أسود، مقاس M', 'qty' => 1, 'price' => '60 شيكل'],
        ['image' => asset('front/assets/images/customer/orders/notebookicon.png'), 'title' => 'دفتر مخصص', 'desc' => 'غلاف مطبوع', 'qty' => 1, 'price' => '20 شيكل'],
    ];
    $order1036Items = [
        ['image' => asset('front/assets/images/customer/orders/icons8-sticker-48.png'), 'title' => 'ستيكرات عبارات', 'desc' => 'حزمة ملصقات', 'qty' => 1, 'price' => '10 شيكل'],
    ];
    $order1029Items = [
        ['image' => asset('front/assets/images/customer/orders/notebookicon.png'), 'title' => 'دفتر مخصص', 'desc' => 'غلاف مطبوع', 'qty' => 1, 'price' => '30 شيكل'],
    ];
    $order1021Items = [
        ['image' => asset('front/assets/images/customer/orders/tshirt.webp'), 'title' => 'تيشيرت مطبوع', 'desc' => 'أبيض، مقاس L', 'qty' => 1, 'price' => '45 شيكل'],
    ];
@endphp

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
        <span class="orders-total"><i class="bi bi-receipt"></i> 8 طلبات</span>
    </div>

    <section class="orders-content" aria-labelledby="pageTitle">
        <section class="orders-section" aria-labelledby="currentOrdersTitle">
            <div class="section-header"><div><span class="section-kicker">قيد المعالجة</span><h2 id="currentOrdersTitle">الطلبات الحالية</h2></div><span class="section-count">4 طلبات</span></div>
            <div class="orders-table-wrap">
                <table class="orders-table">
                    <thead><tr><th>رقم الطلب</th><th>المنتج</th><th>تاريخ الطلب</th><th>الإجمالي</th><th>حالة الطلب</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                        <tr><td data-label="رقم الطلب"><a href="#" class="order-number">#1048</a></td><td data-label="المنتج"><div class="product-cell"><img src="{{ asset('front/assets/images/customer/orders/icons8-sticker-48.png') }}" alt="ملصقات خطوط"><div><strong>ستيكرات خطوط</strong><span>حزمة ملصقات</span></div></div></td><td data-label="تاريخ الطلب"><time datetime="2026-09-10">10 سبتمبر 2026</time></td><td data-label="الإجمالي"><strong class="price">10 شيكل</strong></td><td data-label="حالة الطلب"><span class="status status-review"><i class="bi bi-clock"></i>قيد المراجعة</span></td><td data-label="الإجراءات"><div class="actions-group"><button class="details-button" type="button" data-order="1048" data-date="10 سبتمبر 2026" data-total="10 شيكل" data-status="قيد المراجعة" data-status-class="status-review" data-status-icon="bi-clock" data-address="غزة، الرمال، شارع الجلاء، بناية الخير" data-payment="PalPay" data-items='@json($order1048Items)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button><button class="cancel-order" type="button" data-order="1048"><i class="bi bi-trash3"></i>إلغاء الطلب</button></div></td></tr>
                        <tr><td data-label="رقم الطلب"><a href="#" class="order-number">#1047</a></td><td data-label="المنتج"><div class="product-cell"><img src="{{ asset('front/assets/images/customer/orders/t-shirt.png') }}" alt="تيشيرت فلسطين"><div><strong>تيشيرت فلسطين</strong><span>ومنتج آخر</span></div></div></td><td data-label="تاريخ الطلب"><time datetime="2026-09-09">9 سبتمبر 2026</time></td><td data-label="الإجمالي"><strong class="price">45 شيكل</strong></td><td data-label="حالة الطلب"><span class="status status-progress"><i class="bi bi-gear"></i>قيد التنفيذ</span></td><td data-label="الإجراءات"><div class="actions-group"><button class="details-button" type="button" data-order="1047" data-date="9 سبتمبر 2026" data-total="45 شيكل" data-status="قيد التنفيذ" data-status-class="status-progress" data-status-icon="bi-gear" data-address="غزة، الرمال، شارع الجلاء، بناية الخير" data-payment="بنك فلسطين" data-items='@json($order1047Items)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button><button class="cancel-order" type="button" data-order="1047"><i class="bi bi-trash3"></i>إلغاء الطلب</button></div></td></tr>
                        <tr><td data-label="رقم الطلب"><a href="#" class="order-number">#1046</a></td><td data-label="المنتج"><div class="product-cell"><img src="{{ asset('front/assets/images/customer/orders/cup.webp') }}" alt="كوب مطبوع"><div><strong>كوب مطبوع</strong><span>كوب خزفي</span></div></div></td><td data-label="تاريخ الطلب"><time datetime="2026-09-08">8 سبتمبر 2026</time></td><td data-label="الإجمالي"><strong class="price">25 شيكل</strong></td><td data-label="حالة الطلب"><span class="status status-print"><i class="bi bi-printer"></i>قيد الطباعة</span></td><td data-label="الإجراءات"><div class="actions-group"><button class="details-button" type="button" data-order="1046" data-date="8 سبتمبر 2026" data-total="25 شيكل" data-status="قيد الطباعة" data-status-class="status-print" data-status-icon="bi-printer" data-address="غزة، الرمال، شارع الجلاء، بناية الخير" data-payment="جوال بي" data-items='@json($order1046Items)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button><button class="cancel-order" type="button" data-order="1046"><i class="bi bi-trash3"></i>إلغاء الطلب</button></div></td></tr>
                        <tr><td data-label="رقم الطلب"><a href="#" class="order-number">#1045</a></td><td data-label="المنتج"><div class="product-cell"><img src="{{ asset('front/assets/images/customer/orders/posterposter.png') }}" alt="ستيكرات فيكتور"><div><strong>ستيكرات فيكتور</strong><span>قص مخصص</span></div></div></td><td data-label="تاريخ الطلب"><time datetime="2026-09-07">7 سبتمبر 2026</time></td><td data-label="الإجمالي"><strong class="price">20 شيكل</strong></td><td data-label="حالة الطلب"><span class="status status-shipping"><i class="bi bi-truck"></i>قيد التوصيل</span></td><td data-label="الإجراءات"><div class="actions-group"><button class="details-button" type="button" data-order="1045" data-date="7 سبتمبر 2026" data-total="20 شيكل" data-status="قيد التوصيل" data-status-class="status-shipping" data-status-icon="bi-truck" data-address="غزة، الرمال، شارع الجلاء، بناية الخير" data-payment="PalPay" data-items='@json($order1045Items)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button><button class="cancel-order" type="button" data-order="1045"><i class="bi bi-trash3"></i>إلغاء الطلب</button></div></td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="orders-section" aria-labelledby="previousOrdersTitle">
            <div class="section-header"><div><span class="section-kicker">سجل الطلبات</span><h2 id="previousOrdersTitle">الطلبات السابقة</h2></div><span class="section-count">4 طلبات</span></div>
            <div class="orders-table-wrap">
                <table class="orders-table previous-orders">
                    <thead><tr><th>رقم الطلب</th><th>المنتج</th><th>تاريخ الطلب</th><th>الإجمالي</th><th>حالة الطلب</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                        <tr><td data-label="رقم الطلب"><a href="#" class="order-number">#1040</a></td><td data-label="المنتج"><div class="product-cell"><img src="{{ asset('front/assets/images/customer/orders/hoodie-black.png') }}" alt="هودي مطبوع"><div><strong>هودي مطبوع</strong><span>وقطعة أخرى</span></div></div></td><td data-label="تاريخ الطلب"><time datetime="2026-09-02">2 سبتمبر 2026</time></td><td data-label="الإجمالي"><strong class="price">80 شيكل</strong></td><td data-label="حالة الطلب"><span class="status status-done"><i class="bi bi-check-circle"></i>تم التنفيذ</span></td><td data-label="الإجراءات"><button class="details-button" type="button" data-order="1040" data-date="2 سبتمبر 2026" data-total="80 شيكل" data-status="تم التنفيذ" data-status-class="status-done" data-status-icon="bi-check-circle" data-address="غزة، الرمال، شارع الجلاء، بناية الخير" data-payment="PalPay" data-items='@json($order1040Items)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button></td></tr>
                        <tr><td data-label="رقم الطلب"><a href="#" class="order-number">#1036</a></td><td data-label="المنتج"><div class="product-cell"><img src="{{ asset('front/assets/images/customer/orders/icons8-sticker-48.png') }}" alt="ستيكرات عبارات"><div><strong>ستيكرات عبارات</strong><span>حزمة ملصقات</span></div></div></td><td data-label="تاريخ الطلب"><time datetime="2026-08-28">28 أغسطس 2026</time></td><td data-label="الإجمالي"><strong class="price">10 شيكل</strong></td><td data-label="حالة الطلب"><span class="status status-cancelled"><i class="bi bi-x-circle"></i>ملغي</span></td><td data-label="الإجراءات"><button class="details-button" type="button" data-order="1036" data-date="28 أغسطس 2026" data-total="10 شيكل" data-status="ملغي" data-status-class="status-cancelled" data-status-icon="bi-x-circle" data-address="رام الله، الماصيون، شارع الإرسال" data-payment="جوال بي" data-items='@json($order1036Items)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button></td></tr>
                        <tr><td data-label="رقم الطلب"><a href="#" class="order-number">#1029</a></td><td data-label="المنتج"><div class="product-cell"><img src="{{ asset('front/assets/images/customer/orders/notebookicon.png') }}" alt="دفتر مخصص"><div><strong>دفتر مخصص</strong><span>غلاف مطبوع</span></div></div></td><td data-label="تاريخ الطلب"><time datetime="2026-08-22">22 أغسطس 2026</time></td><td data-label="الإجمالي"><strong class="price">30 شيكل</strong></td><td data-label="حالة الطلب"><span class="status status-done"><i class="bi bi-check-circle"></i>تم التنفيذ</span></td><td data-label="الإجراءات"><button class="details-button" type="button" data-order="1029" data-date="22 أغسطس 2026" data-total="30 شيكل" data-status="تم التنفيذ" data-status-class="status-done" data-status-icon="bi-check-circle" data-address="نابلس، رفيديا، شارع الجامعة" data-payment="بنك فلسطين" data-items='@json($order1029Items)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button></td></tr>
                        <tr><td data-label="رقم الطلب"><a href="#" class="order-number">#1021</a></td><td data-label="المنتج"><div class="product-cell"><img src="{{ asset('front/assets/images/customer/orders/tshirt.webp') }}" alt="تيشيرت مطبوع"><div><strong>تيشيرت مطبوع</strong><span>أبيض، مقاس L</span></div></div></td><td data-label="تاريخ الطلب"><time datetime="2026-08-15">15 أغسطس 2026</time></td><td data-label="الإجمالي"><strong class="price">45 شيكل</strong></td><td data-label="حالة الطلب"><span class="status status-cancelled"><i class="bi bi-x-circle"></i>ملغي</span></td><td data-label="الإجراءات"><button class="details-button" type="button" data-order="1021" data-date="15 أغسطس 2026" data-total="45 شيكل" data-status="ملغي" data-status-class="status-cancelled" data-status-icon="bi-x-circle" data-address="الخليل، البلدة القديمة، شارع السوق" data-payment="PalPay" data-items='@json($order1021Items)'>عرض التفاصيل<i class="bi bi-arrow-left"></i></button></td></tr>
                    </tbody>
                </table>
            </div>
            <nav class="pagination" aria-label="صفحات الطلبات"><button type="button" aria-label="الصفحة السابقة" disabled><i class="bi bi-chevron-right"></i></button><button type="button" class="active" aria-current="page">1</button><button type="button">2</button><button type="button" aria-label="الصفحة التالية"><i class="bi bi-chevron-left"></i></button></nav>
        </section>
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
