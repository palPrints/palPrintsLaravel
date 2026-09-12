{{--
    Ported from palPrintFront/stickers.html.
    The ready-made design catalog (per category) and the brand customizer's
    quantity/size/shape/paper/protection options are all still hardcoded in
    stickers.js — same "demo data" situation the store page had before it was
    wired to the real products table. The cart/favorites are localStorage-only
    for now, same as the other product pages.
--}}
@extends('customer.layouts.app')

@section('title', 'ستيكرات')
@section('meta-description', 'اختر من تصاميم ستيكرات PalPrints الجاهزة أو ارفع تصميم البراند الخاص بك وخصصه للطباعة')
@section('body-class', 'storefront-page stickers-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/stickers.css') }}?v={{ filemtime(public_path('front/css/customer/stickers.css')) }}">
@endpush

@section('content')
    <script>document.body.classList.add("stickers-motion-ready");</script>

    <div class="container-palprints">
        <div class="catalog-topline">
            <nav class="catalog-breadcrumb" aria-label="مسار التنقل">
                <a href="{{ route('home') }}">الرئيسية</a>
                <span>/</span>
                <a href="{{ route('customer.store') }}#products">المنتجات</a>
                <span>/</span>
                <span aria-current="page">ستيكرات</span>
            </nav>
            <a class="catalog-back" href="{{ route('customer.store') }}#products">
                العودة إلى المنتجات <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </div>

        <section class="catalog-heading stickers-heading" aria-labelledby="stickersTitle">
            <h1 id="stickersTitle"><img src="{{ asset('front/assets/images/customer/icons8-sticker-48.png') }}" alt=""> ستيكرات</h1>
            <p>مجموعة متنوعة من الملصقات لتزيين مقتنياتك والتعبير عن هويتك.</p>
        </section>

        <nav class="stickers-categories" aria-label="تصنيفات الملصقات">
            <div class="stickers-tabs" role="tablist" aria-label="اختر تصنيف الملصقات">
                <button class="sticker-tab is-active" id="tab-brand" type="button" role="tab" aria-controls="brandPanel" aria-selected="true" tabindex="0" data-category="brand">البراند</button>
                <button class="sticker-tab" id="tab-quotes" type="button" role="tab" aria-controls="readyPanel" aria-selected="false" tabindex="-1" data-category="quotes">خطوط وعبارات</button>
                <button class="sticker-tab" id="tab-colorful" type="button" role="tab" aria-controls="readyPanel" aria-selected="false" tabindex="-1" data-category="colorful">ملوّنة ومعبّأة</button>
                <button class="sticker-tab" id="tab-illustrated" type="button" role="tab" aria-controls="readyPanel" aria-selected="false" tabindex="-1" data-category="illustrated">مخططة ومرسومة</button>
                <button class="sticker-tab" id="tab-simple" type="button" role="tab" aria-controls="readyPanel" aria-selected="false" tabindex="-1" data-category="simple">بسيطة وأيقونات</button>
                <button class="sticker-tab" id="tab-3d" type="button" role="tab" aria-controls="readyPanel" aria-selected="false" tabindex="-1" data-category="3d">3D</button>
            </div>
        </nav>

        <section class="sticker-panel brand-panel" id="brandPanel" role="tabpanel" aria-labelledby="tab-brand">
            <section class="stickers-upload-card" aria-labelledby="uploadTitle">
                <input class="stickers-file-input" id="stickerFile" type="file" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml" aria-describedby="uploadHelp uploadStatus">
                <label class="stickers-upload" id="stickerDropzone" for="stickerFile">
                    <span class="stickers-upload-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
                    <strong id="uploadTitle">ارفع تصميم ملصق البراند الخاص بك</strong>
                    <span id="uploadHelp">اسحب الملف هنا أو اضغط للاختيار</span>
                    <small>PNG، JPG، JPEG، WEBP أو SVG — حتى 10MB</small>
                </label>
                <div class="stickers-upload-feedback" id="uploadStatus" aria-live="polite"></div>
            </section>

            <section class="brand-examples-section" aria-labelledby="brandExamplesTitle">
                <div class="section-title-row"><h2 id="brandExamplesTitle">أمثلة لملصقات براندات تم طباعتها</h2><span>4 أمثلة</span></div>
                <div class="brand-examples" id="brandExamples">
                    <figure class="brand-example" data-search="براند ماكونو لابتوب"><img src="{{ asset('front/assets/images/customer/stickers/brand/brand-example-1.jpg') }}" alt="حاسوب محمول يحمل ملصق براند" loading="lazy"><figcaption>ملصق براند للأجهزة</figcaption></figure>
                    <figure class="brand-example" data-search="براند مارغريت دفتر"><img src="{{ asset('front/assets/images/customer/stickers/brand/brand-example-2.jpg') }}" alt="دفتر يحمل ملصق براند" loading="lazy"><figcaption>ملصق براند للقرطاسية</figcaption></figure>
                    <figure class="brand-example" data-search="براند ماكونو دفتر أسود"><img src="{{ asset('front/assets/images/customer/stickers/brand/brand-example-3.jpg') }}" alt="دفتر أسود يحمل ملصق براند" loading="lazy"><figcaption>هوية براند مخصصة</figcaption></figure>
                    <figure class="brand-example" data-search="براند تغليف صندوق"><img src="{{ asset('front/assets/images/customer/stickers/brand/brand-example-4.jpg') }}" alt="صندوق تغليف يحمل ملصق براند" loading="lazy"><figcaption>ملصق براند للتغليف</figcaption></figure>
                </div>
                <p class="products-empty brand-empty" id="brandEmpty" role="status" hidden>لا توجد أمثلة براند مطابقة لبحثك.</p>
            </section>

            <section class="stickers-customizer" aria-labelledby="customizerTitle">
                <div class="section-title-row"><h2 id="customizerTitle">تخصيص طلبك</h2><span>معاينة مباشرة</span></div>
                <div class="stickers-customizer-grid">
                    <div class="stickers-preview-column">
                        <div class="stickers-preview-frame" id="stickerPreviewFrame" data-shape="custom"><span class="stickers-preview-shape-label"><i class="bi bi-stars" aria-hidden="true"></i>قص حسب حدود التصميم</span><img id="stickerPreview" src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="معاينة تصميم ملصق البراند"></div>
                        <p class="stickers-preview-caption" id="previewCaption">معاينة الملصق قبل الطباعة</p>
                    </div>
                    <form class="stickers-options" id="stickerCustomizer" novalidate>
                        <fieldset class="sticker-fieldset sticker-fieldset--wide">
                            <legend>تحديد الكمية</legend>
                            <div class="sticker-quantity-row">
                                <div class="sticker-stepper"><button type="button" data-quantity-action="decrease" aria-label="تقليل الكمية"><i class="bi bi-dash" aria-hidden="true"></i></button><input id="stickerQuantity" name="quantity" type="number" min="1" step="1" value="50" inputmode="numeric" aria-label="كمية الملصقات"><button type="button" data-quantity-action="increase" aria-label="زيادة الكمية"><i class="bi bi-plus" aria-hidden="true"></i></button></div>
                                <div class="sticker-choice-row" aria-label="كميات سريعة"><button class="sticker-choice is-selected" type="button" aria-pressed="true" data-quantity="50">50</button><button class="sticker-choice" type="button" aria-pressed="false" data-quantity="100">100</button><button class="sticker-choice" type="button" aria-pressed="false" data-quantity="250">250</button><button class="sticker-choice" type="button" aria-pressed="false" data-quantity="500">500</button></div>
                            </div>
                        </fieldset>
                        <fieldset class="sticker-fieldset"><legend>حجم الملصقات</legend><div class="sticker-choice-row"><button class="sticker-choice" type="button" aria-pressed="false" data-size="3x3">3×3 cm</button><button class="sticker-choice" type="button" aria-pressed="false" data-size="5x5">5×5 cm</button><button class="sticker-choice is-selected" type="button" aria-pressed="true" data-size="10x10">10×10 cm</button></div></fieldset>
                        <fieldset class="sticker-fieldset"><legend>شكل الملصقات</legend><div class="sticker-shape-row"><button class="sticker-shape" type="button" aria-label="دائرة" aria-pressed="false" data-shape="circle"><span class="shape-swatch shape-swatch--circle"></span></button><button class="sticker-shape" type="button" aria-label="مربع" aria-pressed="false" data-shape="square"><span class="shape-swatch shape-swatch--square"></span></button><button class="sticker-shape" type="button" aria-label="مربع دائري الحواف" aria-pressed="false" data-shape="rounded"><span class="shape-swatch shape-swatch--rounded"></span></button><button class="sticker-shape sticker-shape--custom is-selected" type="button" aria-label="قص مخصص يلتف حول حدود التصميم" aria-pressed="true" data-shape="custom"><span class="shape-swatch shape-swatch--custom"><i class="bi bi-stars" aria-hidden="true"></i></span></button></div></fieldset>
                        <fieldset class="sticker-fieldset"><legend>نوع الورق</legend><div class="sticker-radio-list"><label><input type="radio" name="paper" value="glossy" checked> <span>فوتو لامع</span></label><label><input type="radio" name="paper" value="matte"> <span>فوتو غير لامع</span></label><label><input type="radio" name="paper" value="non-adhesive"> <span>فوتو غير لاصق</span></label></div></fieldset>
                        <fieldset class="sticker-fieldset"><legend>خيارات الحماية</legend><div class="sticker-radio-list"><label><input type="radio" name="protection" value="protected" checked> <span>مع طبقة حماية</span></label><label><input type="radio" name="protection" value="unprotected"> <span>بدون طبقة حماية</span></label></div></fieldset>
                        <div class="stickers-submit-row"><button class="stickers-add-to-cart pal-pressable" type="submit"><i class="bi bi-cart-plus" aria-hidden="true"></i>أضف الطلب إلى السلة</button></div>
                    </form>
                </div>
            </section>
        </section>

        <section class="sticker-panel ready-panel" id="readyPanel" role="tabpanel" aria-labelledby="tab-quotes" hidden>
            <div class="ready-panel__header">
                <h2 id="readyTitle">تصاميم خطوط وعبارات جاهزة</h2>
                <div class="sticker-order-notes" aria-label="معلومات الطلب"><p><i class="bi bi-info-circle" aria-hidden="true"></i><span>الحد الأدنى للطلب: <strong>30 ستيكر</strong>.</span></p><p><i class="bi bi-info-circle" aria-hidden="true"></i><span>كل <strong>30 ستيكر</strong> بـ <strong>10 شيكل</strong>.</span></p></div>
            </div>
            <div class="products-grid sticker-design-grid" id="productGrid" aria-live="polite"></div>
            <p class="products-empty" id="productsEmpty" role="status" hidden>لا توجد تصاميم مطابقة لبحثك. جرّب كلمة أخرى أو اختر تصنيفًا مختلفًا.</p>
            <nav class="catalog-pagination stickers-pagination" id="stickersPagination" aria-label="صفحات تصاميم الستيكرات"></nav>
            <p class="catalog-count" id="catalogCount" role="status" aria-live="polite"></p>
        </section>
        <noscript><p class="products-empty">يرجى تفعيل JavaScript لعرض تصاميم الستيكرات وخيارات التخصيص.</p></noscript>
    </div>

    <div class="sticker-toast" id="stickerToast" role="status" aria-live="polite" aria-atomic="true"><i class="bi bi-check-circle" aria-hidden="true"></i><span></span></div>
@endsection

@push('scripts')
    <script>
        window.palPrintsCustomerAssets = window.palPrintsCustomerAssets || {};
        window.palPrintsCustomerAssets.stickersImagesBase = @json(asset('front/assets/images/customer/stickers'));
        window.palPrintsCustomerAssets.stickerIconFallback = @json(asset('front/assets/images/customer/icons8-sticker-48.png'));
    </script>
    <script src="{{ asset('front/js/customer/stickers.js') }}?v={{ filemtime(public_path('front/js/customer/stickers.js')) }}"></script>
@endpush
