@extends('printProvider.layouts.app')

@section('title', 'خدمات الطباعة')

@section('bodyClass', 'print-services-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/services.css') }}?v={{ filemtime(public_path('front/css/printProvider/services.css')) }}">
@endpush

@section('content')
    <nav class="breadcrumb services-breadcrumb" aria-label="مسار التنقل">
        <a href="{{ route('print-provider.dashboard') }}">الرئيسية</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">خدمات الطباعة</span>
    </nav>

    <section class="services-heading" aria-labelledby="servicesTitle">
        <div>
            <h1 id="servicesTitle"><i class="bi bi-box-seam" aria-hidden="true"></i> خدمات الطباعة</h1>
            <p>أدر منتجاتك وخدمات الطباعة التي تقدمها لعملائك، وكل الإعدادات بما يناسب مطبعتك.</p>
            <div class="services-summary"><i class="bi bi-box" aria-hidden="true"></i><span id="servicesSummary" aria-live="polite"></span></div>
        </div>
        <button class="services-button services-button--primary" id="openCatalog" type="button"><i class="bi bi-plus-lg" aria-hidden="true"></i> إضافة منتج من الكتالوج</button>
    </section>

    <p class="services-storage-warning" id="storageWarning" role="status" hidden></p>

    <section class="services-panel" aria-labelledby="myProductsTitle">
        <header>
            <h2 id="myProductsTitle"><i class="bi bi-grid" aria-hidden="true"></i> منتجاتي المفعلة (<span id="addedCount">0</span>)</h2>
            <p>هذه المنتجات مفعلة وتظهر لعملائك في المنصة عند تشغيلها.</p>
        </header>
        <div class="services-products" id="servicesProducts"></div>
        <div class="services-empty" id="servicesEmpty" role="status" hidden>
            <i class="bi bi-search" aria-hidden="true"></i>
            <h3>لا توجد منتجات مطابقة للبحث</h3>
            <p>جرّب اسم منتج أو تصنيف آخر.</p>
        </div>
        <noscript><p>يرجى تفعيل JavaScript لعرض المنتجات وإدارة إعداداتها.</p></noscript>
    </section>

    <dialog class="services-dialog services-catalog-dialog" id="catalogDialog" aria-labelledby="catalogTitle" aria-describedby="catalogDescription">
        <header class="services-dialog-heading">
            <div>
                <span class="services-dialog-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                <div><h2 id="catalogTitle">كتالوج منتجات المنصة</h2><p id="catalogDescription">اختر المنتجات التي تريد تفعيلها في مطبعتك.</p></div>
            </div>
            <button class="services-close" type="button" data-dialog-close aria-label="إغلاق الكتالوج"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </header>
        <div class="services-catalog-grid" id="catalogProducts"></div>
    </dialog>

    <dialog class="services-dialog services-settings-dialog" id="settingsDialog" aria-labelledby="settingsTitle">
        <form id="productSettingsForm">
            <header class="services-dialog-heading">
                <div><span class="services-dialog-icon"><i class="bi bi-gear" aria-hidden="true"></i></span><h2 id="settingsTitle">إعدادات المنتج</h2></div>
                <button class="services-close" type="button" data-dialog-close aria-label="إغلاق إعدادات المنتج"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </header>
            <div class="services-settings-body">
                <article class="services-product-preview" id="settingsPreview"></article>
                <div class="services-settings-fields">
                    <div class="services-number-fields">
                        <label>السعة الإنتاجية اليومية <span class="services-required">*</span><input id="productCapacity" name="capacity" type="number" min="1" step="1" max="1000000" required /></label>
                        <label>مدة الإنتاج المتوقعة (أيام) <span class="services-required">*</span><input id="productDays" name="days" type="number" min="1" step="1" max="365" required /></label>
                        <label>سعر الطباعة <span class="services-required">*</span><span class="services-price-field"><input id="productPrice" name="price" type="number" min="0.01" step="0.01" max="1000000" required /><b aria-hidden="true">$</b></span></label>
                    </div>
                    <div id="settingsOptions"></div>
                    <p class="services-form-error" id="settingsError" role="alert" hidden></p>
                </div>
            </div>
            <footer class="services-dialog-footer">
                <button class="services-button services-button--primary" type="submit"><i class="bi bi-lock" aria-hidden="true"></i> حفظ</button>
                <button class="services-button services-button--secondary" type="button" data-dialog-close>إلغاء</button>
            </footer>
        </form>
    </dialog>

    <div class="services-toast" id="servicesToast" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/printProvider/services.js') }}"></script>
@endpush
