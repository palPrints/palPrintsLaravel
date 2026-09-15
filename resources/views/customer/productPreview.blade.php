{{--
    Ported from palPrintFront/productPreview.html.
    Reads the chosen product/design from sessionStorage("palprintsCustomerPreview")
    set by the designer/choose-product flow (not yet wired into this Laravel app),
    and falls back to a demo hoodie payload otherwise — same "demo data" situation
    as the other product pages until that flow is ported.
--}}
@extends('customer.layouts.app')

@section('title', 'معاينة المنتج')
@section('meta-description', 'عاين منتجك المخصص واختر اللون والمقاس ومناطق الطباعة قبل إضافته إلى السلة')
@section('body-class', 'storefront-page product-preview-page preview-booting')
@section('main-class', 'product-preview-main')
@section('main-id', 'productPreviewMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/productPreview.css') }}?v={{ filemtime(public_path('front/css/customer/productPreview.css')) }}">
@endpush

@section('content')
    <script>
        window.__previewBootFallback = window.setTimeout(function () {
            document.body.classList.remove("preview-booting");
        }, 2000);
    </script>

    <div class="product-preview-container">
        <header class="preview-page-heading">
            <ol class="preview-breadcrumb" aria-label="مسار التنقل">
                <li><a href="{{ route('home') }}">الرئيسية</a></li>
                <li><a href="{{ route('customer.store') }}#products">المنتجات</a></li>
                <li><a href="{{ route('customer.hoodies') }}">هودي</a></li>
                <li aria-current="page"><span>معاينة المنتج</span></li>
            </ol>
            <div class="preview-title-row">
                <span class="preview-heading-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
                <div>
                    <h1>معاينة المنتج</h1>
                    <p>راجع الشكل النهائي واختياراتك، ثم أضف المنتج إلى سلتك بثقة.</p>
                </div>
            </div>
        </header>

        <div class="preview-layout">
            <section class="product-preview-card" aria-labelledby="visualPreviewTitle">
                <header class="preview-card-heading">
                    <div>
                        <h2 id="visualPreviewTitle">شاهد تصميمك على المنتج</h2>
                    </div>
                </header>

                <div class="product-gallery">
                    <div class="view-tabs" id="viewTabs" role="tablist" aria-label="واجهات المنتج"></div>

                    <div class="product-stage" id="productStage">
                        <span class="current-view-badge" id="currentViewBadge"></span>
                        <div class="product-canvas" id="productCanvas" aria-live="polite">
                            <img class="preview-product-image" id="productImage" alt="">
                            <div class="preview-design-placement" id="designPlacement" aria-hidden="true">
                                <div class="preview-design-art" id="designArt"></div>
                            </div>
                            <div class="preview-image-placeholder" id="imagePlaceholder" hidden>
                                <i class="bi bi-image" aria-hidden="true"></i>
                                <strong>تعذّر تحميل صورة المنتج</strong>
                                <span>جرّب اختيار واجهة أخرى.</span>
                            </div>
                        </div>

                        <div class="preview-controls" aria-label="أدوات تكبير المعاينة">
                            <button type="button" id="zoomOut" aria-label="تصغير المعاينة"><i class="bi bi-zoom-out" aria-hidden="true"></i></button>
                            <output id="zoomValue" aria-live="polite">100%</output>
                            <button type="button" id="zoomIn" aria-label="تكبير المعاينة"><i class="bi bi-zoom-in" aria-hidden="true"></i></button>
                            <span class="controls-divider" aria-hidden="true"></span>
                            <button type="button" id="fitPreview" aria-label="ملاءمة المعاينة"><i class="bi bi-arrows-angle-contract" aria-hidden="true"></i></button>
                            <button type="button" id="openFullscreen" aria-label="فتح المعاينة بكامل الشاشة"><i class="bi bi-fullscreen" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>

                <div class="screen-color-note">
                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                    <div><strong>معاينة قريبة من النتيجة النهائية</strong><span>قد تختلف درجة اللون قليلًا بين الشاشة والمنتج المطبوع.</span></div>
                </div>
                <ul class="customer-warnings" id="customerWarnings" role="status" hidden></ul>
            </section>

            <aside class="product-summary-card" aria-labelledby="summaryTitle">
                <div class="summary-product-head">
                    <span class="summary-product-icon"><i class="bi bi-bag-heart" aria-hidden="true"></i></span>
                    <div><h2 id="summaryTitle"></h2><p id="designMeta"></p></div>
                </div>

                <section class="piece-selector" id="pieceSelector" aria-labelledby="pieceSelectorTitle" hidden>
                    <div class="piece-selector__heading">
                        <span id="pieceSelectorTitle"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> تخصيص القطع</span>
                        <strong id="activePieceLabel">القطعة 1 من 1</strong>
                    </div>
                    <div class="piece-tabs" id="pieceTabs" role="tablist" aria-label="اختر القطعة المراد تخصيصها"></div>
                    <p>يمكنك اختيار لون ومقاس ومناطق طباعة مختلفة لكل قطعة.</p>
                </section>

                <div class="piece-editor" id="pieceEditorPanel" role="tabpanel" aria-label="خيارات القطعة 1" tabindex="0">
                    <fieldset class="preview-option-group">
                        <legend><span><i class="bi bi-palette" aria-hidden="true"></i> اللون</span><strong id="selectedColorName"></strong></legend>
                        <div class="color-options" id="colorOptions"></div>
                    </fieldset>

                    <fieldset class="preview-option-group">
                        <legend><span><i class="bi bi-rulers" aria-hidden="true"></i> المقاس</span><strong id="selectedSizeName"></strong></legend>
                        <div class="size-options" id="sizeOptions"></div>
                    </fieldset>

                    <section class="quantity-section" aria-labelledby="quantityTitle">
                        <div><span class="option-label" id="quantityTitle"><i class="bi bi-copy" aria-hidden="true"></i> الكمية</span><small>الحد الأقصى 99 قطعة</small></div>
                        <div class="quantity-control">
                            <button type="button" id="decreaseQuantity" aria-label="تقليل الكمية"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                            <output id="quantityValue">1</output>
                            <button type="button" id="increaseQuantity" aria-label="زيادة الكمية"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                        </div>
                    </section>

                    <fieldset class="preview-option-group print-area-group">
                        <legend><span><i class="bi bi-printer" aria-hidden="true"></i> مناطق الطباعة</span><small>اختر منطقة واحدة على الأقل</small></legend>
                        <div class="print-area-options" id="printAreaOptions"></div>
                        <p class="option-error" id="printAreaError" role="alert"></p>
                    </fieldset>
                </div>
            </aside>
        </div>

        <div class="preview-purchase-row">
            <section class="price-card price-card--horizontal" aria-labelledby="priceTitle">
                <div class="price-card-title"><span><i class="bi bi-receipt" aria-hidden="true"></i><strong id="priceTitle">تفاصيل السعر</strong></span><small>لا يشمل التوصيل</small></div>
                <div class="price-breakdown" id="priceBreakdown"></div>
                <div class="price-total"><span>الإجمالي</span><strong id="totalPrice" dir="ltr">—</strong></div>
            </section>

            <div class="preview-bottom-actions">
                <p class="summary-validation" id="summaryValidation" role="status" aria-live="polite"></p>
                <div class="preview-bottom-buttons">
                    <button type="button" class="preview-button is-primary" id="addToCartButton"><i class="bi bi-cart-plus" aria-hidden="true"></i><span>إضافة إلى السلة</span></button>
                    <a class="preview-button is-secondary" href="{{ route('customer.hoodies') }}"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>العودة إلى تصاميم الهودي</span></a>
                </div>
            </div>
        </div>
        <p class="summary-help"><i class="bi bi-headset" aria-hidden="true"></i> تحتاج مساعدة؟ تواصل مع الدعم الفني</p>
    </div>

    <div class="preview-mobile-bar" id="previewMobileBar">
        <div><span>الإجمالي</span><strong id="mobileTotalPrice" dir="ltr">—</strong></div>
        <button type="button" class="preview-button is-primary" id="mobileAddToCart"><i class="bi bi-cart-plus" aria-hidden="true"></i><span>إضافة إلى السلة</span></button>
    </div>

    <dialog class="fullscreen-preview-dialog" id="fullscreenDialog" aria-labelledby="fullscreenTitle">
        <header>
            <div><span>معاينة مكبرة</span><h2 id="fullscreenTitle"></h2></div>
            <button type="button" id="closeFullscreen" aria-label="إغلاق المعاينة المكبرة"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </header>
        <div class="fullscreen-stage" id="fullscreenStage"></div>
    </dialog>

    <div class="preview-toast" id="previewToast" role="status" aria-live="polite"><span class="preview-toast-icon"><i class="bi bi-check2" aria-hidden="true"></i></span><span id="previewToastMessage"></span></div>
@endsection

@push('scripts')
    <script>
        window.palPrintsCustomerAssets = window.palPrintsCustomerAssets || {};
        window.palPrintsCustomerAssets.hoodiePreviewImage = @json(asset('front/assets/images/customer/hoodie.png'));
        window.palPrintsCustomerAssets.hoodiePreviewBackImage = @json(asset('front/assets/images/customer/hoodie-back-clean.png'));
    </script>
    <script src="{{ asset('front/js/customer/productPreview.js') }}?v={{ filemtime(public_path('front/js/customer/productPreview.js')) }}"></script>
@endpush
