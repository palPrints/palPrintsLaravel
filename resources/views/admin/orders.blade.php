@extends('admin.layouts.app')

@section('title', 'إدارة الطلبات')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminOrders.css').'?v='.filemtime(public_path('front/css/admin/adminOrders.css')) }}">
@endpush

@php
    $dash = '—';
    $filters = [
        'all' => 'الكل',
        'processing' => 'قيد التنفيذ',
        'shipped' => 'تم الشحن',
        'completed' => 'مكتمل',
        'pending' => 'معلق',
        'cancelled' => 'ملغي',
    ];
@endphp

@section('content')
<main id="adminMain">
    <section class="orders-overview" aria-labelledby="ordersTitle">
        <h1 id="ordersTitle">إدارة الطلبات</h1>

        <div class="orders-summary" aria-label="ملخص الطلبات">
            <article class="order-stat-card">
                <span class="order-stat-icon is-blue" aria-hidden="true"><i class="bi bi-cart3"></i></span>
                <div class="order-stat-copy"><span>إجمالي الطلبات</span><strong><span data-stat-counter="{{ $counts['all'] }}">{{ $counts['all'] }}</span></strong></div>
            </article>
            <article class="order-stat-card">
                <span class="order-stat-icon is-orange" aria-hidden="true"><i class="bi bi-clock"></i></span>
                <div class="order-stat-copy"><span>قيد التنفيذ</span><strong><span data-stat-counter="{{ $counts['processing'] }}">{{ $counts['processing'] }}</span></strong></div>
            </article>
            <article class="order-stat-card">
                <span class="order-stat-icon is-green" aria-hidden="true"><i class="bi bi-check-circle"></i></span>
                <div class="order-stat-copy"><span>تم التسليم</span><strong><span data-stat-counter="{{ $counts['completed'] }}">{{ $counts['completed'] }}</span></strong></div>
            </article>
            <article class="order-stat-card">
                <span class="order-stat-icon is-gold" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                <div class="order-stat-copy"><span>إيرادات محصلة</span><strong><bdi><span data-stat-counter="{{ (int) round($revenue) }}">{{ (int) round($revenue) }}</span> ₪</bdi></strong></div>
            </article>
        </div>
        <section class="orders-section" aria-label="قائمة الطلبات">
            <div class="orders-toolbar">
                <div class="orders-filters" role="group" aria-label="تصفية الطلبات حسب الحالة">
                    @foreach ($filters as $key => $label)
                        <button @class(['active' => $key === 'all']) type="button" data-order-filter="{{ $key }}">{{ $label }} <span>{{ $counts[$key] }}</span></button>
                    @endforeach
                </div>
            </div>

            <div class="orders-table-card">
                <div class="orders-table-wrap" tabindex="0" aria-label="جدول الطلبات، قابل للتمرير أفقياً">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th scope="col">الإجراء</th>
                                <th scope="col">الحالة</th>
                                <th scope="col">التاريخ</th>
                                <th scope="col">المبلغ</th>
                                <th scope="col">المطبعة</th>
                                <th scope="col">العميل</th>
                                <th scope="col">رقم الطلب</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            @foreach ($orders as $order)
                                <tr data-order-row
                                    data-status="{{ $order['state'] }}"
                                    data-search="{{ $order['number'] }} {{ $order['customer'] }}"
                                    data-number="{{ $order['number'] }}"
                                    data-customer="{{ $order['customer'] }}"
                                    data-phone="{{ $order['phone'] }}"
                                    data-printer-id="{{ $order['printerId'] }}"
                                    data-payment="{{ $order['payment'] }}"
                                    data-paid="{{ $order['paid'] ? '1' : '0' }}"
                                    data-notes="{{ $order['notes'] }}"
                                    data-update-url="{{ $order['updateUrl'] }}">
                                    <td><button class="order-details" type="button">عرض التفاصيل</button></td>
                                    <td><span class="order-status {{ $order['class'] }}">{{ $order['label'] }}</span></td>
                                    <td><time datetime="{{ $order['iso'] }}">{{ $order['date'] }}</time></td>
                                    <td><bdi class="order-price">{{ number_format($order['amount'], 2) }} ₪</bdi></td>
                                    <td>{{ $order['printer'] ?: $dash }}</td>
                                    <td><strong>{{ $order['customer'] }}</strong></td>
                                    <td><bdi>{{ $order['number'] }}</bdi></td>
                                </tr>
                            @endforeach
                            <tr class="orders-empty-row" id="ordersEmptyRow" @if ($orders->isNotEmpty()) hidden @endif>
                                <td colspan="7">
                                    <div class="orders-empty">
                                        <span class="orders-empty-icon"><i class="bi bi-inbox"></i></span>
                                        <strong>{{ $orders->isEmpty() ? 'لا توجد طلبات بعد' : 'لا توجد نتائج مطابقة' }}</strong>
                                        <p>{{ $orders->isEmpty() ? 'ستظهر الطلبات هنا فور وصولها.' : 'جرّب اختيار حالة أخرى.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="orders-result-count" id="ordersResultCount" aria-live="polite">عرض {{ $orders->count() }} من أصل {{ $orders->count() }} طلبًا</p>
            </div>
        </section>
    </section>
</main>

<dialog class="order-dialog" id="orderDetailsDialog" aria-labelledby="orderDialogTitle">
    <div class="order-dialog-header">
        <div>
            <h2 id="orderDialogTitle">تفاصيل الطلب</h2>
            <p id="orderDialogSubtitle"></p>
        </div>
        <button class="order-dialog-close" type="button" data-order-dialog-close aria-label="إغلاق النافذة"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="order-dialog-body">
        <section class="order-dialog-section" aria-labelledby="orderInfoTitle">
            <h3 id="orderInfoTitle"><i class="bi bi-receipt"></i> معلومات الطلب</h3>
            <dl class="order-details-list" id="orderDetailsList"></dl>
        </section>

        <section class="order-dialog-section" aria-labelledby="deliveryInfoTitle">
            <h3 id="deliveryInfoTitle"><i class="bi bi-geo-alt"></i> العميل والدفع</h3>
            <dl class="order-details-list dialog-customer-details">
                <div><dt>رقم الهاتف</dt><dd id="dialogPhone"></dd></div>
                <div><dt>طريقة الدفع</dt><dd id="dialogPayment"></dd></div>
                <div><dt>حالة الدفع</dt><dd id="dialogPaid"></dd></div>
                <div><dt>حالة الشحنة</dt><dd id="dialogShipment"></dd></div>
            </dl>
            <div class="dialog-note"><span>ملاحظات العميل</span><p id="dialogNotes"></p></div>
        </section>

        <section class="order-dialog-section order-management" aria-labelledby="orderManagementTitle">
            <h3 id="orderManagementTitle"><i class="bi bi-sliders"></i> إدارة الطلب</h3>
            <div class="order-management-fields">
                <label>حالة الطلب
                    <select id="dialogStatusSelect">
                        <option value="processing">قيد التنفيذ</option>
                        <option value="shipped">تم الشحن</option>
                        <option value="completed">مكتمل</option>
                        <option value="pending">معلق</option>
                        <option value="cancelled">ملغي</option>
                    </select>
                </label>
                <label>المطبعة المنفذة
                    <select id="dialogPrinterSelect">
                        <option value="">غير محددة</option>
                        @foreach ($printers as $printer)
                            <option value="{{ $printer->id }}">{{ $printer->company_name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <p class="order-management-hint" id="orderManagementHint"></p>
            <p class="order-dialog-feedback" id="orderDialogFeedback" role="status" aria-live="polite"></p>
        </section>
    </div>
    <div class="order-dialog-footer">
        <button class="dialog-secondary-button" type="button" data-order-dialog-close>إغلاق</button>
        <button class="dialog-save-button" id="saveOrderChanges" type="button"><i class="bi bi-check2"></i> حفظ التعديلات</button>
    </div>
</dialog>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminOrders.js').'?v='.filemtime(public_path('front/js/admin/adminOrders.js')) }}"></script>
@endpush
