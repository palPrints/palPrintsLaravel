@extends('designer.layouts.app')

@section('title', 'معاينة وإعدادات النشر')
@section('main-class', 'source-review-main')

@push('styles')
    <base href='{{ asset('front/designer/source/create') }}/'>
    <script>
        document.documentElement.classList.add("fonts-loading");
        window.palPrintsFontTimeout = window.setTimeout(() => document.documentElement.classList.remove("fonts-loading"), 3000);
    </script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@100..900&amp;family=Almarai:wght@400;800&amp;family=Amiri:wght@400;700&amp;family=Aref+Ruqaa:wght@400;700&amp;family=Baloo+Bhaijaan+2:wght@400..800&amp;family=Cairo:wght@200..1000&amp;family=Changa:wght@200..800&amp;family=El+Messiri:wght@400..700&amp;family=Gulzar&amp;family=Harmattan:wght@400;700&amp;family=IBM+Plex+Sans+Arabic:wght@400;700&amp;family=Jomhuria&amp;family=Katibeh&amp;family=Lalezar&amp;family=Lateef:wght@400;800&amp;family=Lemonada:wght@300..700&amp;family=Mada:wght@200..900&amp;family=Marhey:wght@300..700&amp;family=Markazi+Text:wght@400..700&amp;family=Mirza:wght@400;700&amp;display=block">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@100..900&amp;family=Noto+Naskh+Arabic:wght@400..700&amp;family=Noto+Sans+Arabic:wght@100..900&amp;family=Rakkas&amp;family=Readex+Pro:wght@160..700&amp;family=Reem+Kufi:wght@400..700&amp;family=Rubik:wght@300..900&amp;family=Scheherazade+New:wght@400;700&amp;family=Tajawal:wght@400;800&amp;family=Vibes&amp;display=block">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&amp;family=Lato:wght@400;800&amp;family=Merriweather:wght@300..900&amp;family=Montserrat:wght@100..900&amp;family=Open+Sans:wght@300..800&amp;family=Oswald:wght@200..700&amp;family=Playfair+Display:wght@400..900&amp;family=Poppins:wght@400;800&amp;family=Raleway:wght@100..900&amp;family=Roboto:wght@100..900&amp;display=block">
    <link rel='stylesheet' href='{{ asset('front/designer/source/create/assets/css/review.css') }}'>
@endpush

@section('content')
    <div class='review-shell'>
        <header class="review-header">
            <div class="page-heading">
                <span class="heading-icon"><i class="bi bi-rocket-takeoff" aria-hidden="true"></i></span>
                <div>
                    <h1>معاينة وإعدادات النشر</h1>
                    <p>راجع تصميمك وحدد سعر البيع قبل إرساله للمراجعة</p>
                </div>
            </div>
            <button class="back-button" id="backToEditor" type="button">
                <span>رجوع للمحرر</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </button>
        </header>

        <div class="review-layout">
            <section class="preview-panel" aria-labelledby="previewTitle">
                <div class="preview-toolbar">
                    <div>
                        <span class="section-kicker">معاينة المنتج</span>
                        <h2 id="previewTitle">شاهد تصميمك على المنتج</h2>
                    </div>
                    <label class="area-control" for="areaSelect">
                        <span>تنقّل بين مناطق التصميم</span>
                        <span class="select-wrap">
                            <select id="areaSelect"></select>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </span>
                    </label>
                </div>

                <div class="mockup-stage" id="mockupStage">
                    <div class="stage-halo" aria-hidden="true"></div>
                    <div class="preview-canvas" id="previewCanvas">
                        <img id="productImage" alt="">
                        <div class="preview-print-zone" id="printZone" aria-label="التصميم المطبوع">
                            <div class="preview-image-layers" id="imageLayers"></div>
                            <div class="preview-text-layers" id="textLayers"></div>
                            <div class="empty-area" id="emptyArea" hidden>
                                <i class="bi bi-image" aria-hidden="true"></i>
                                <span>لا يوجد تصميم في هذه المنطقة</span>
                            </div>
                        </div>
                    </div>
                    <div class="zoom-controls" aria-label="التحكم بالتكبير">
                        <button id="zoomOut" type="button" aria-label="تصغير"><i class="bi bi-dash-lg"></i></button>
                        <output id="zoomValue">100%</output>
                        <button id="zoomIn" type="button" aria-label="تكبير"><i class="bi bi-plus-lg"></i></button>
                        <button class="fit-button" id="fitButton" type="button" aria-label="ملاءمة للشاشة"><i
                                class="bi bi-fullscreen"></i></button>
                    </div>
                </div>

                <section class="warnings-card" id="warningsCard" aria-labelledby="warningsTitle">
                    <div class="warnings-heading">
                        <span class="warning-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                        <div>
                            <h3 id="warningsTitle">فحص جودة التصميم</h3>
                            <p id="warningsSummary"></p>
                        </div>
                    </div>
                    <ul id="warningsList"></ul>
                </section>

                <dl class="product-summary">
                    <div>
                        <dt><i class="bi bi-tag" aria-hidden="true"></i> نوع المنتج</dt>
                        <dd id="productName"></dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-palette" aria-hidden="true"></i> اللون</dt>
                        <dd><span class="color-dot" id="colorDot"></span><span id="colorName"></span></dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-rulers" aria-hidden="true"></i> المقاس</dt>
                        <dd id="sizeName"></dd>
                    </div>
                </dl>
            </section>

            <aside class="publish-panel" aria-labelledby="publishTitle">
                <div class="publish-heading">
                    <span><i class="bi bi-sliders" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="publishTitle">تفاصيل النشر</h2>
                        <p>أكمل البيانات المطلوبة للمتابعة</p>
                    </div>
                </div>

                <label class="field-label" for="designName">
                    <span>اسم التصميم <b aria-hidden="true">*</b></span>
                    <input id="designName" maxlength="100" autocomplete="off" placeholder="اكتب اسمًا واضحًا لتصميمك">
                    <small><span id="nameCount">0</span>/100</small>
                </label>

                <section class="price-section" aria-labelledby="priceTitle">
                    <div class="subheading">
                        <h3 id="priceTitle">السعر</h3><span>حدّد هامش ربحك</span>
                    </div>
                    <div class="price-row base-price"><span>تكلفة المنتج (الأساسية)</span><strong><bdi
                                id="basePrice">0.00</bdi> <small>ر.س</small></strong></div>
                    <label class="price-row selling-price" for="sellingPrice">
                        <span>سعر البيع <b aria-hidden="true">*</b></span>
                        <span class="money-input"><input id="sellingPrice" type="number" min="0" step="1"
                                inputmode="decimal"><small>ر.س</small></span>
                    </label>
                    <div class="price-row profit-row"><span><i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                            ربحك</span><strong><bdi id="profitValue">0.00</bdi> <small>ر.س</small></strong></div>
                    <p class="field-error" id="priceError" role="alert"></p>
                </section>

                <label class="rights-check" for="rightsCheck">
                    <input id="rightsCheck" type="checkbox">
                    <span class="custom-check"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                    <span><strong>أؤكد أنني أملك حق استخدام هذا التصميم</strong><small>أتحمل مسؤولية المحتوى وحقوق
                            الملكية الفكرية.</small></span>
                </label>

                <div class="validation-note" id="validationNote" role="status" aria-live="polite"></div>
            </aside>
        </div>

        <footer class="review-footer">
            <button class="save-draft-button" id="saveButton" type="button"><i class="bi bi-bookmark"
                    aria-hidden="true"></i> حفظ كمسودة</button>
            <button class="publish-button" id="publishButton" type="button"><span>متابعة وإرسال للمراجعة</span><i
                    class="bi bi-arrow-left" aria-hidden="true"></i></button>
        </footer>

    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <div class="success-dialog" id="successDialog" role="dialog" aria-modal="true" aria-labelledby="successTitle"
        hidden>
        <div class="dialog-card">
            <span class="success-mark"><i class="bi bi-check2" aria-hidden="true"></i></span>
            <h2 id="successTitle">تم إرسال التصميم للمراجعة</h2>
            <p>حُفظت بيانات المنتج والتصميم وإعدادات النشر بنجاح.</p>
            <button id="closeDialog" type="button">حسنًا</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.palPrintsCreateRoutes = {
            choose: @json(route('designer.designs.create')),
            editor: @json(route('designer.designs.editor')),
            review: @json(route('designer.designs.review')),
            dashboard: @json(route('designer.dashboard'))
        };
    </script>
    <script src='{{ asset('front/designer/source/create/assets/js/review.js') }}'></script>
@endpush
