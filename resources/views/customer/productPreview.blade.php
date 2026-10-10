{{--
    Ported from palPrintFront/productPreview.html (latest, role-aware preview).
    The page markup, CSS and JS are the frontend's; only the boundaries with
    Laravel differ: links are routes, the role comes from the logged-in user,
    and "add to cart" posts to the database-backed cart.
--}}
@php
    $isCustomer = auth()->user()?->hasRole('customer');
    $productsUrl = $isCustomer ? route('customer.store').'#products' : route('designer.designs.create');
    $designerProducts = collect(\App\Support\CatalogProductData::forDesigner()['products']);
    $productNames = $designerProducts->mapWithKeys(fn ($product) => [strtoupper((string) $product['code']) => $product['name']])->all();
    // The colours and sizes a print shop can really make now; the copy the studio saved in the browser can be older.
    $productAvailable = $designerProducts->mapWithKeys(fn ($product) => [strtoupper((string) $product['code']) => [
        'colors' => collect($product['colors'])->pluck('id')->map(fn ($id) => strtolower((string) $id))->all(),
        'sizes' => collect($product['sizes'])->pluck('id')->map(fn ($id) => strtolower((string) $id))->all(),
    ]])->all();
    $productLinks = $isCustomer ? ['TSHIRT-CLASSIC' => route('customer.tshirts'), 'HOODIE-PREMIUM' => route('customer.hoodies'), 'MUG-CERAMIC' => route('customer.mugs'), 'STICKER-CUSTOM' => route('customer.stickers')] : [];
@endphp
@extends('customer.layouts.app')

@section('title', 'معاينة المنتج')
@section('meta-description', 'عاين منتجك المخصص واختر اللون والمقاس ومناطق الطباعة قبل إضافته إلى السلة')
@section('body-class', 'storefront-page product-preview-page preview-booting')
@section('main-class', 'product-preview-main')
@section('main-id', 'productPreviewMain')
@section('page-loader-wait', '1')

@push('styles')
    <base href="{{ asset('front/studio') }}/">
    <link rel="stylesheet" href="{{ asset('front/css/customer/productPreview.css') }}?v={{ filemtime(public_path('front/css/customer/productPreview.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/designer/css/designerFlow.css') }}?v={{ filemtime(public_path('front/designer/css/designerFlow.css')) }}">
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
          <li><a href="{{ $productsUrl }}" id="breadcrumbProducts">المنتجات</a></li>
          <li><a href="{{ $productsUrl }}" id="breadcrumbProductName">المنتج</a></li>
          <li aria-current="page"><span>معاينة المنتج</span></li>
        </ol>
        <div class="preview-title-row">
          <span class="preview-heading-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
          <div>
            <h1 id="previewPageTitle">معاينة المنتج</h1>
            <p id="previewPageDescription">راجع الشكل النهائي واختياراتك، ثم أضف المنتج إلى سلتك بثقة.</p>
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
                <div class="preview-product-tint" id="productTint" aria-hidden="true" hidden></div>
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
              <legend><span><i class="bi bi-palette" aria-hidden="true"></i> <span id="colorControlLabel">اللون</span></span><strong id="selectedColorName"></strong></legend>
              <div class="color-options" id="colorOptions"></div>
            </fieldset>

            <fieldset class="preview-option-group" id="customerSizeGroup">
              <legend><span><i class="bi bi-rulers" aria-hidden="true"></i> المقاس</span><strong id="selectedSizeName"></strong></legend>
              <div class="size-options" id="sizeOptions"></div>
            </fieldset>

            <section class="quantity-section" id="customerQuantitySection" aria-labelledby="quantityTitle">
              <div><span class="option-label" id="quantityTitle"><i class="bi bi-copy" aria-hidden="true"></i> الكمية</span><small id="quantityLimitText"></small></div>
              <div class="quantity-control">
                <button type="button" id="decreaseQuantity" aria-label="تقليل الكمية"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                <output id="quantityValue">1</output>
                <button type="button" id="increaseQuantity" aria-label="زيادة الكمية"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
              </div>
            </section>

            <fieldset class="preview-option-group print-area-group" id="customerPrintAreaGroup">
              <legend><span><i class="bi bi-printer" aria-hidden="true"></i> مناطق الطباعة</span><small>اختر منطقة واحدة على الأقل</small></legend>
              <div class="print-area-options" id="printAreaOptions"></div>
              <p class="option-error" id="printAreaError" role="alert"></p>
            </fieldset>
          </div>

          <section class="designer-color-approval" id="designerColorApproval" aria-labelledby="designerColorsTitle" hidden>
            <div>
              <span class="option-label" id="designerColorsTitle"><i class="bi bi-palette2" aria-hidden="true"></i> حدد ألوان المنتج المناسبة لهذا التصميم</span>
              <p id="allowedColorsSummary">لم يتم تحديد ألوان بعد</p>
            </div>
            <button type="button" class="preview-button is-secondary" id="openAllowedColors"><i class="bi bi-check2-square" aria-hidden="true"></i><span>اختيار الألوان</span></button>
          </section>

          <section class="designer-size-approval" id="designerSizeApproval" aria-labelledby="designerSizesTitle" hidden>
            <div class="designer-size-approval__head">
              <span class="option-label" id="designerSizesTitle"><i class="bi bi-rulers" aria-hidden="true"></i> حدد المقاسات المناسبة لهذا التصميم</span>
              <span class="audience-badge" id="audienceBadge"></span>
            </div>
            <p id="allowedSizesSummary"></p>
            <div class="size-chips" id="allowedSizeOptions" role="group" aria-label="المقاسات المناسبة"></div>
          </section>

          <section class="printing-technology-section" id="printingTechnologySection" aria-labelledby="printingTechnologyTitle">
            <label for="printingTechnology" id="printingTechnologyTitle"><i class="bi bi-printer" aria-hidden="true"></i> تقنية الطباعة</label>
            <select id="printingTechnology" disabled><option value="">غير متاحة — لم تتم تهيئتها بعد</option></select>
            <small id="printingTechnologyNote">لم تُضف بيانات تقنيات الطباعة المتاحة لهذا المنتج أو المطبعة بعد.</small>
          </section>

        </aside>
      </div>

      @if(auth()->user()?->hasRole('designer'))
        <div class="designer-publish-actions">
          <a class="designer-flow-button is-ghost" href="{{ route('design-studio') }}"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>رجوع للاستوديو</span></a>
          <button type="button" class="designer-flow-button is-primary" id="designerContinueButton"><span>متابعة لتفاصيل النشر</span><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
        </div>
      @endif

      <div class="preview-purchase-row" id="customerPurchaseRow">
        <section class="price-card price-card--horizontal" aria-labelledby="priceTitle">
          <div class="price-card-title"><span><i class="bi bi-receipt" aria-hidden="true"></i><strong id="priceTitle">تفاصيل السعر</strong></span><small>لا يشمل التوصيل</small></div>
          <div class="price-breakdown" id="priceBreakdown"></div>
          <div class="price-total"><span>الإجمالي</span><strong id="totalPrice" dir="ltr">—</strong></div>
        </section>

        <div class="preview-bottom-actions">
          <p class="summary-validation" id="summaryValidation" role="status" aria-live="polite"></p>
          <div class="preview-bottom-buttons">
            <button type="button" class="preview-button is-primary" id="addToCartButton"><i class="bi bi-cart-plus" aria-hidden="true"></i><span>إضافة إلى السلة</span></button>
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

  <dialog class="allowed-colors-dialog" id="allowedColorsDialog" aria-labelledby="allowedColorsDialogTitle">
    <div class="allowed-colors-card">
      <header>
        <div><span>إعدادات النشر</span><h2 id="allowedColorsDialogTitle">حدد ألوان المنتج المناسبة لهذا التصميم</h2></div>
        <button type="button" id="closeAllowedColors" aria-label="إغلاق"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
      </header>
      <p>يمكنك معاينة الألوان من الصفحة قبل اعتمادها للبيع.</p>
      <div class="allowed-colors-actions"><button type="button" id="selectAllColors">تحديد الكل</button><button type="button" id="clearAllColors">إلغاء تحديد الكل</button></div>
      <div class="allowed-colors-list" id="allowedColorOptions"></div>
      <footer><button type="button" class="preview-button is-secondary" id="cancelAllowedColors">إلغاء</button><button type="button" class="preview-button is-primary" id="confirmAllowedColors">اعتماد الألوان</button></footer>
    </div>
  </dialog>

  <div class="preview-toast" id="previewToast" role="status" aria-live="polite"><span class="preview-toast-icon"><i class="bi bi-check2" aria-hidden="true"></i></span><span id="previewToastMessage"></span></div>

@endsection

@push('scripts')
    <script>
        window.palPrintsAudiences = @json(\App\Support\CatalogProductData::AUDIENCES);
        window.palPrintsDesignerAssets = { reviewUrl: @json(auth()->user()?->hasRole('designer') ? route('designer.designs.review') : null) };
        window.PALPRINTS_AUTH = { role: @json(auth()->user()?->hasRole('designer') ? 'designer' : 'customer') };
        window.palPrintsCustomerAssets = window.palPrintsCustomerAssets || {};
        window.palPrintsCustomerAssets.cartCatalogUrl = @json(route('customer.cart.store-catalog'));
        window.palPrintsCustomerAssets.cartCustomDesignUrl = @json(route('customer.cart.store-custom-design'));
    </script>
    <script>
        // Real (database) product names and the catalog page of each product, for the title and the breadcrumb.
        window.palPrintsPreviewCatalog = {
            names: @json($productNames),
            available: @json($productAvailable),
            links: @json($productLinks),
            productsUrl: @json($productsUrl),
            // Printing methods (DTF, DTG...) the print shops really offer, for the products that have more than one.
            printingMethods: @json(\App\Support\PrintingMethodOptions::forPreview()),
        };
    </script>
    <script src="{{ asset('front/studio/assets/js/pages/design-studio-product-catalog.js') }}"></script>
    <script src="{{ asset('front/studio/assets/js/pages/design-studio-graphics.js') }}"></script>
    <script src="{{ asset('front/js/customer/designComposite.js') }}?v={{ filemtime(public_path('front/js/customer/designComposite.js')) }}"></script>
    <script src="{{ asset('front/js/customer/role-auth-resolver.js') }}?v={{ filemtime(public_path('front/js/customer/role-auth-resolver.js')) }}"></script>
    <script src="{{ asset('front/js/customer/productPreview.js') }}?v={{ filemtime(public_path('front/js/customer/productPreview.js')) }}"></script>
@endpush
