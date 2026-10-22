@extends('customer.layouts.app')

@section('title', 'ملصقات A4')
@section('meta-description', 'اختر ملصقات جاهزة أو ارفع تصميمك وشاهدها على ورقة A4 قبل الطلب')
@section('body-class', 'storefront-page stickers-page')
@section('main-class', 'stickers-main')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/stickers.css') }}?v={{ filemtime(public_path('front/css/customer/stickers.css')) }}">
@endpush

@section('content')
    <div class="container-palprints sticker-builder" data-sticker-builder>
        <div class="catalog-topline">
            <nav class="catalog-breadcrumb" aria-label="مسار التنقل">
                <a href="{{ route('home') }}">الرئيسية</a>
                <span>/</span>
                <a href="{{ route('customer.store') }}#products">المنتجات</a>
                <span>/</span>
                <span aria-current="page">ملصقات</span>
            </nav>
        </div>

        <section class="stickers-workspace" aria-labelledby="stickersTitle">
            <div class="stickers-shop{{ $stickerUploadMode ? ' stickers-shop--sticky' : '' }}">
                <header class="stickers-hero">
                    <span class="stickers-hero__icon"><i class="bi bi-sticky"></i></span>
                    <div>
                        <h1 id="stickersTitle">ملصقات A4 تفاعلية</h1>
                        <p>اختر الملصقات أو ارفع تصميمك، وسنرتبها على ورقة A4 حسب إعدادات الطباعة.</p>
                    </div>
                </header>

                <section class="sticker-controls" aria-label="إعدادات الملصق قبل إضافته">
                    <label><span>المقاس</span><select id="stickerSizeSelect"></select></label>
                    <label><span>الكمية</span><input id="stickerQuantityInput" type="number" min="1" max="999" value="1" inputmode="numeric"></label>
                    <label><span>نوع القص</span><select id="stickerCutSelect"><option value="circle">قص دائري مجاني</option><option value="contour">خط قص حول التصميم</option></select></label>
                    <p class="sticker-controls__hint"><i class="bi bi-info-circle" aria-hidden="true"></i> <span><strong>الخطوة الأولى:</strong> اختر المقاس والكمية ونوع القص، ثم {{ $stickerUploadMode ? 'ارفع صورتك' : 'اضغط على الملصق الذي تريده' }}. بعدها تقدر تعدّلها لكل ملصق من قائمة «ملصقاتك».</span></p>
                </section>

                {{-- Only when the customer came from "upload your design": their own image lands on the A4 sheet like any sticker. --}}
                @if ($stickerUploadMode)
                <section class="upload-panel" aria-labelledby="uploadTitle">
                    <div class="upload-panel__copy">
                        <span class="upload-panel__badge">تصميمك الخاص</span>
                        <h2 id="uploadTitle">ارفع تصميمك كملصق</h2>
                        <p>صورة PNG أو JPG، ويُفضّل PNG بخلفية شفافة. تظهر مباشرة على ورقة A4 بالمقاس والكمية ونوع القص المختارة فوق.</p>
                    </div>
                    <label class="upload-drop">
                        <input id="stickerUploadInput" type="file" accept="image/png,image/jpeg">
                        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                        <span>اختيار صورة</span>
                        <small>PNG · JPG</small>
                    </label>
                </section>
                @endif

                {{-- Every sticker added, with its own size, quantity and cut. --}}
                <div class="sticker-lines" id="stickerLines" aria-label="ملصقاتك" hidden></div>

                @unless ($stickerUploadMode)
                <section class="library-panel" aria-labelledby="libraryTitle">
                    <div class="section-title-row"><h2 id="libraryTitle">الملصقات الجاهزة</h2><span id="libraryCount"></span></div>
                    <div class="sticker-tabs" id="stickerTabs">
                        <button type="button" class="is-active" data-category="all">الكل</button>
                        @foreach($stickerCategories as $category)
                            <button type="button" data-category="{{ $category['id'] }}">{{ $category['name'] }}</button>
                        @endforeach
                    </div>
                    <div class="sticker-grid" id="stickerGrid"></div>
                    <p class="products-empty" id="stickersEmpty" hidden>لا توجد ملصقات جاهزة في هذا التصنيف بعد.</p>
                </section>
                @endunless
            </div>

            <aside class="a4-preview-panel" aria-label="معاينة ورقة A4">
                <div class="mobile-preview-toggle" role="tablist" aria-label="التبديل بين الاختيار والمعاينة">
                    <button type="button" class="is-active" data-mobile-view="shop">الاختيار</button>
                    <button type="button" data-mobile-view="preview">معاينة A4</button>
                </div>

                <div class="a4-preview-card">
                    <header>
                        <div><span>معاينة الطباعة</span><h2>ورقة A4</h2></div>
                        <div class="page-switcher">
                            <button type="button" id="prevStickerPage" aria-label="الصفحة السابقة"><i class="bi bi-chevron-right"></i></button>
                            <strong id="stickerPageLabel">1 / 1</strong>
                            <button type="button" id="nextStickerPage" aria-label="الصفحة التالية"><i class="bi bi-chevron-left"></i></button>
                        </div>
                    </header>

                    <div class="a4-sheet-wrap">
                        <div class="a4-sheet" id="a4Sheet">
                            <div class="a4-empty" id="a4Empty"><i class="bi bi-plus-circle"></i><strong>{{ $stickerUploadMode ? 'ارفع صورتك لتظهر على الورقة' : 'اختر ملصقا ليقفز إلى الورقة' }}</strong></div>
                        </div>
                    </div>

                    <div class="sticker-summary">
                        <span><b id="summaryQuantity">0</b> ملصق</span>
                        <span><b id="summaryPages">1</b> صفحة A4</span>
                        <strong id="summaryPrice">0 شيكل</strong>
                    </div>
                    <div class="sticker-page-materials" id="stickerPageMaterials" aria-label="مواد الصفحة"></div>
                    <p class="sticker-warning" id="stickerWarning" hidden></p>
                    <div class="preview-actions">
                        <button type="button" class="secondary-action" id="clearStickerBundle"><i class="bi bi-trash3"></i> مسح</button>
                        <button type="button" class="primary-action" id="addStickerBundle"><i class="bi bi-cart-plus"></i> إضافة للسلة</button>
                    </div>
                </div>
            </aside>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        window.PalPrintsStickers = {
            categories: @json($stickerCategories),
            uploadMode: @json($stickerUploadMode),
            settings: @json($stickerSettings),
            materials: @json($stickerMaterials),
            routes: @json($stickerRoutes),
            user: @json($stickerUser),
        };
    </script>
    <script src="{{ asset('front/js/customer/stickers.js') }}?v={{ filemtime(public_path('front/js/customer/stickers.js')) }}"></script>
@endpush
