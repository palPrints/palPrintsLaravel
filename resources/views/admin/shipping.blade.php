@extends('admin.layouts.app')

@section('title', 'إدارة الشحن')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminShippingManagement.css').'?v='.filemtime(public_path('front/css/admin/adminShippingManagement.css')) }}">
@endpush

@php
    $filters = [
        'all' => 'الكل',
        'failed' => 'فشل التوصيل',
        'delivered' => 'تم التوصيل',
        'transit' => 'في الطريق',
        'pending' => 'بانتظار الاستلام',
    ];
@endphp

@section('content')
<main class="admin-main admin-shipping-main" id="adminShippingMain">
    <section class="shipping-overview" aria-labelledby="shippingTitle">
        @include('admin.partials.breadcrumb', ['label' => 'إدارة الشحن'])
        <h1 id="shippingTitle">إدارة الشحن</h1>

        <div class="shipping-summary-grid" aria-label="ملخص حالات الشحن">
            <article class="shipping-summary-card is-failed">
                <div><p>فشل التوصيل</p><strong data-shipping-counter="{{ $counts['failed'] }}">{{ $counts['failed'] }}</strong></div>
                <span class="shipping-summary-icon" aria-hidden="true"><i class="bi bi-file-earmark-x"></i></span>
            </article>
            <article class="shipping-summary-card is-delivered">
                <div><p>تم التوصيل</p><strong data-shipping-counter="{{ $counts['delivered'] }}">{{ $counts['delivered'] }}</strong></div>
                <span class="shipping-summary-icon" aria-hidden="true"><i class="bi bi-check-circle"></i></span>
            </article>
            <article class="shipping-summary-card is-transit">
                <div><p>في الطريق</p><strong data-shipping-counter="{{ $counts['transit'] }}">{{ $counts['transit'] }}</strong></div>
                <span class="shipping-summary-icon" aria-hidden="true"><i class="bi bi-clock"></i></span>
            </article>
            <article class="shipping-summary-card is-total">
                <div><p>إجمالي الشحنات</p><strong data-shipping-counter="{{ $counts['all'] }}">{{ $counts['all'] }}</strong></div>
                <span class="shipping-summary-icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
            </article>
        </div>

        <section class="shipping-records" aria-label="قائمة الشحنات">
            <div class="shipping-records-toolbar">
                <div class="shipping-status-filters" role="group" aria-label="تصفية الشحنات حسب الحالة">
                    @foreach ($filters as $key => $label)
                        <button @class(['active' => $key === 'all']) type="button" data-shipping-filter="{{ $key }}">{{ $label }} ({{ $counts[$key] }})</button>
                    @endforeach
                </div>
            </div>

            <div class="shipping-table-wrap" tabindex="0" aria-label="جدول الشحنات، قابل للتمرير أفقيًا">
                <table class="shipping-table">
                    <thead>
                        <tr><th>رقم الشحنة</th><th>الطلب</th><th>العميل</th><th>المنتج</th><th>شركة الشحن</th><th>رقم التتبع</th><th>التسليم المتوقع</th><th>الحالة</th></tr>
                    </thead>
                    <tbody id="shippingTableBody">
                        @foreach ($shipments as $shipment)
                            <tr data-shipping-row data-status="{{ $shipment['state'] }}" data-search="{{ $shipment['number'] }} {{ $shipment['orderNumber'] }} {{ $shipment['customer'] }} {{ $shipment['product'] }} {{ $shipment['carrier'] }} {{ $shipment['trackingNumber'] }}">
                                <td>{{ $shipment['number'] }}</td>
                                <td>#{{ $shipment['orderNumber'] }}</td>
                                <td><strong>{{ $shipment['customer'] }}</strong><small>{{ $shipment['city'] }}</small></td>
                                <td>{{ $shipment['product'] }}</td>
                                <td>{{ $shipment['carrier'] }}</td>
                                <td>{{ $shipment['trackingNumber'] }}</td>
                                <td>{{ $shipment['estimatedDelivery'] }}</td>
                                <td><span class="shipping-status {{ $shipment['class'] }}">{{ $shipment['label'] }}</span></td>
                            </tr>
                        @endforeach
                        <tr class="shipping-empty-row" id="shippingEmptyRow" @if ($shipments->isNotEmpty()) hidden @endif>
                            <td colspan="8">
                                <i class="bi bi-truck" aria-hidden="true"></i>
                                <strong>{{ $available ? ($shipments->isEmpty() ? 'لا توجد شحنات بعد' : 'لا توجد شحنات مطابقة') : 'بيانات الشحن غير متاحة حاليًا' }}</strong>
                                <span>{{ $available ? ($shipments->isEmpty() ? 'ستظهر الشحنات هنا فور تجهيزها.' : 'جرّب اختيار حالة أخرى.') : 'تُضاف بيانات الشحن عند تفعيل نظام الطلبات.' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="shipping-results-count" id="shippingResultsCount" data-total="{{ $counts['all'] }}">عرض {{ $counts['all'] }} من أصل {{ $counts['all'] }} شحنات</p>
        </section>
    </section>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminShippingManagement.js').'?v='.filemtime(public_path('front/js/admin/adminShippingManagement.js')) }}"></script>
@endpush
