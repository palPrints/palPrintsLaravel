@extends('admin.layouts.app')

@section('title', 'إدارة التصاميم')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminEarningsPayments.css').'?v='.filemtime(public_path('front/css/admin/adminEarningsPayments.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminDesigns.css').'?v='.filemtime(public_path('front/css/admin/adminDesigns.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminDesignAreas.css').'?v='.filemtime(public_path('front/css/admin/adminDesignAreas.css')) }}">
@endpush

@php
    $stateLabels = ['pending' => 'قيد المراجعة', 'approved' => 'معتمد', 'rejected' => 'مرفوض'];
    $letter = fn (?string $name): string => mb_substr(trim((string) $name), 0, 1) ?: '؟';
@endphp

@section('content')
<main id="adminMain">
    <section class="designs-page" aria-labelledby="designsTitle">
        @include('admin.partials.breadcrumb', ['label' => 'إدارة التصاميم'])
        <h1 id="designsTitle">إدارة التصاميم</h1>

        <div class="design-summary" aria-label="ملخص التصاميم">
            <article><span class="summary-icon is-blue"><i class="bi bi-file-earmark-text"></i></span><div><strong id="totalDesigns">{{ $counts['total'] }}</strong><p>إجمالي التصاميم</p></div></article>
            <article><span class="summary-icon is-orange"><i class="bi bi-clock"></i></span><div><strong id="pendingDesigns">{{ $counts['pending'] }}</strong><p>بانتظار المراجعة</p></div></article>
            <article><span class="summary-icon is-green"><i class="bi bi-check-circle"></i></span><div><strong id="approvedDesigns">{{ $counts['approved'] }}</strong><p>تصاميم معتمدة</p></div></article>
            <article><span class="summary-icon is-red"><i class="bi bi-palette"></i></span><div><strong id="rejectedDesigns">{{ $counts['rejected'] }}</strong><p>تصاميم مرفوضة</p></div></article>
        </div>

        <div class="design-toolbar">
            <div class="category-filters" role="group" aria-label="فلترة التصاميم حسب الفئة">
                <button type="button" data-category-filter="all" aria-pressed="true">كل الفئات</button>
                @foreach ($categories as $slug => $label)
                    <button type="button" data-category-filter="{{ $slug }}" aria-pressed="false">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <section class="design-table-card" aria-label="قائمة التصاميم">
            <div class="status-filters" role="group" aria-label="فلترة التصاميم حسب الحالة">
                <button type="button" data-status-filter="all" aria-pressed="true">الكل <span id="totalTabCount">{{ $counts['total'] }}</span></button>
                <button type="button" data-status-filter="pending" aria-pressed="false">بانتظار المراجعة <span id="pendingTabCount">{{ $counts['pending'] }}</span></button>
                <button type="button" data-status-filter="approved" aria-pressed="false">معتمدة <span id="approvedTabCount">{{ $counts['approved'] }}</span></button>
                <button type="button" data-status-filter="rejected" aria-pressed="false">مرفوضة <span id="rejectedTabCount">{{ $counts['rejected'] }}</span></button>
            </div>
            <div class="design-table-wrap">
                <table class="design-table">
                    <thead><tr><th>الرقم</th><th>التصميم</th><th>الفئة</th><th>المصمم</th><th>تاريخ الرفع</th><th>الحالة</th><th><span class="visually-hidden">الإجراء</span></th></tr></thead>
                    <tbody id="designTableBody">
                        @foreach ($designs as $design)
                            <tr data-design-row
                                data-id="{{ $design['id'] }}"
                                data-code="{{ $design['code'] }}"
                                data-title="{{ $design['title'] }}"
                                data-image="{{ $design['image'] }}"
                                data-category="{{ $design['category'] }}"
                                data-category-label="{{ $design['categoryLabel'] }}"
                                data-category-class="{{ $design['categoryClass'] }}"
                                data-status="{{ $design['state'] }}"
                                data-designer="{{ $design['designer'] }}"
                                data-product="{{ $design['product'] }}"
                                data-date="{{ $design['date'] }}"
                                data-base-price="{{ $design['basePrice'] }}"
                                data-selling-price="{{ $design['sellingPrice'] }}"
                                data-profit="{{ $design['profit'] }}"
                                data-audience="{{ $design['audience'] }}"
                                data-sizes="{{ $design['sizes'] }}"
                                data-colors="{{ $design['colors'] }}"
                                data-preview-color="{{ $design['previewColor'] }}"
                                data-reason="{{ $design['rejectionReason'] }}"
                                data-details="{{ json_encode($design['details'], JSON_UNESCAPED_UNICODE) }}"
                                data-review-url="{{ route('admin.designs.review', $design['id']) }}">
                                <td><code>{{ $design['code'] }}</code></td>
                                <td><strong>{{ $design['title'] }}</strong></td>
                                <td><span class="category {{ $design['categoryClass'] }}">{{ $design['categoryLabel'] }}</span></td>
                                <td><span class="designer"><b>{{ $letter($design['designer']) }}</b>{{ $design['designer'] }}</span></td>
                                <td><time>{{ $design['date'] }}</time></td>
                                <td><span class="design-status {{ $design['state'] }}">{{ $stateLabels[$design['state']] }}</span></td>
                                <td><button class="details-button" type="button" data-details>عرض التفاصيل</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-empty" id="tableEmpty" @if ($designs->isNotEmpty()) hidden @endif>
                <i class="bi bi-palette"></i>
                <strong>{{ $designs->isEmpty() ? 'لا توجد تصاميم مرفوعة بعد' : 'لا توجد تصاميم مطابقة' }}</strong>
                <span>{{ $designs->isEmpty() ? 'ستظهر التصاميم هنا فور رفعها من المصممين.' : 'جرّب تغيير البحث أو الفلاتر المحددة.' }}</span>
            </div>
        </section>
    </section>
</main>

<dialog class="design-dialog" id="designDialog" aria-labelledby="designDialogTitle">
    <div class="design-dialog-content">
        <header><h2 id="designDialogTitle">تفاصيل التصميم</h2><button type="button" class="dialog-close" data-dialog-close aria-label="إغلاق"><i class="bi bi-x-lg"></i></button></header>
        <div class="design-preview" id="dialogDesignPreview" role="img" aria-label="معاينة التصميم"><img id="dialogDesignImage" alt="" hidden><i class="bi bi-image design-preview-empty" id="dialogDesignEmpty" aria-hidden="true"></i><strong id="dialogCategoryPreview"></strong></div>
        <div class="dialog-design-heading"><div><h3 id="dialogDesignTitle"></h3><div><span class="category" id="dialogCategory"></span><code id="dialogDesignId"></code></div></div><span class="design-status pending" id="dialogStatus"></span></div>
        <dl class="dialog-metadata">
            <div><dt>المصمم</dt><dd id="dialogDesigner"></dd></div>
            <div><dt>المنتج</dt><dd id="dialogProduct"></dd></div>
            <div><dt>تاريخ الرفع</dt><dd id="dialogDate"></dd></div>
            <div data-detail-row="audience"><dt>الفئة</dt><dd id="dialogAudience"></dd></div>
            <div data-detail-row="previewColor"><dt>لون المصمم</dt><dd id="dialogPreviewColor"></dd></div>
            <div data-detail-row="sizes"><dt>المقاسات المناسبة</dt><dd id="dialogSizes"></dd></div>
            <div><dt>تكلفة المنتج</dt><dd id="dialogBasePrice"></dd></div>
            <div><dt>سعر البيع</dt><dd id="dialogSellingPrice"></dd></div>
            <div><dt>ربح المصمم من كل قطعة</dt><dd id="dialogProfit"></dd></div>
            <div id="dialogReasonRow" hidden><dt>سبب الرفض</dt><dd id="dialogReason"></dd></div>
        </dl>
        <section class="design-areas-section" id="dialogAreasSection" hidden>
            <h3>التصميم على مناطق الطباعة</h3>
            <p class="design-areas-note" id="dialogAreasNote"></p>
            <div class="design-colors" id="dialogColorPicker" role="radiogroup" aria-label="معاينة التصميم على لون" hidden></div>
            <div class="design-areas" id="dialogAreas"></div>
        </section>
        <footer id="dialogActions"><button class="approve-design" id="approveDesign" type="button"><i class="bi bi-check-circle"></i>اعتماد التصميم</button><button class="reject-design" id="rejectDesign" type="button">رفض التصميم</button></footer>
    </div>
</dialog>
<dialog class="reject-dialog" id="rejectDialog" aria-labelledby="rejectDialogTitle">
    <form class="reject-dialog-card" id="rejectForm" method="dialog">
        <span class="reject-dialog-mark" aria-hidden="true"><i class="bi bi-x-octagon"></i></span>
        <h2 id="rejectDialogTitle">رفض التصميم</h2>
        <p>اكتب سبب الرفض ليصل إلى المصمم ويعرف ما يحتاج تعديله. يمكنك تركه فارغًا.</p>
        <label for="rejectReason">سبب الرفض <small>(اختياري)</small></label>
        <textarea id="rejectReason" rows="4" maxlength="500" placeholder="مثال: جودة الصورة منخفضة، أو التصميم يخالف حقوق الملكية."></textarea>
        <small class="reject-count"><span id="rejectCount">0</span>/500</small>
        <div class="reject-dialog-actions">
            <button type="button" class="reject-cancel" id="rejectCancel">إلغاء</button>
            <button type="submit" class="reject-confirm" id="rejectConfirm"><i class="bi bi-x-circle"></i>تأكيد الرفض</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
    {{-- The studio's product pictures and print zones, and its clip-art list: the dialog redraws the design on every print area. --}}
    <script>window.palPrintsStudioBase = @json(asset('front/studio').'/');</script>
    <script src="{{ asset('front/studio/assets/js/pages/design-studio-product-catalog.js') }}"></script>
    <script src="{{ asset('front/studio/assets/js/pages/design-studio-graphics.js') }}"></script>
    <script src="{{ asset('front/js/admin/adminDesigns.js').'?v='.filemtime(public_path('front/js/admin/adminDesigns.js')) }}"></script>
@endpush
