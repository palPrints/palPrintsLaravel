@extends('admin.layouts.app')

@section('title', 'إدارة الستيكرات')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminEarningsPayments.css').'?v='.filemtime(public_path('front/css/admin/adminEarningsPayments.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminProducts.css').'?v='.filemtime(public_path('front/css/admin/adminProducts.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminStickers.css').'?v='.filemtime(public_path('front/css/admin/adminStickers.css')) }}">
@endpush

@section('content')
<main id="adminMain">
    <section class="products-page stickers-admin" aria-labelledby="stickersTitle">
        @include('admin.partials.breadcrumb', ['label' => 'إدارة الستيكرات'])
        <header class="products-header">
            <div>
                <h1 id="stickersTitle">إدارة الستيكرات</h1>
                <p>التصنيفات والصور التي يختار منها العملاء في صفحة الستيكرات</p>
            </div>
            <div class="stickers-header-actions">
                <button class="secondary-button" id="addCategoryButton" type="button"><i class="bi bi-folder-plus" aria-hidden="true"></i><span>إضافة تصنيف</span></button>
                <button class="primary-button" id="addStickerButton" type="button"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>إضافة ستيكر</span></button>
            </div>
        </header>

        <p class="stickers-note" role="note"><i class="bi bi-info-circle" aria-hidden="true"></i> واجهة التحكم جاهزة للتجربة، لكن الحفظ في قاعدة البيانات يتفعّل بعد إضافة جداول الستيكرات، لذلك ما تضيفه الآن يختفي عند تحديث الصفحة.</p>

        <div class="products-summary stickers-categories-bar" id="categoriesBar" role="group" aria-label="تصنيفات الستيكرات"></div>

        <div class="products-grid stickers-grid" id="stickersGrid"></div>

        <div class="empty-state" id="stickersEmpty" hidden>
            <i class="bi bi-emoji-smile"></i>
            <h2 id="stickersEmptyTitle"></h2>
            <p id="stickersEmptyText"></p>
        </div>
    </section>
</main>

<dialog class="product-dialog" id="categoryDialog" aria-labelledby="categoryDialogTitle">
    <form class="product-form" id="categoryForm" method="dialog" novalidate>
        <header>
            <div>
                <span class="dialog-icon"><i class="bi bi-folder"></i></span>
                <div><h2 id="categoryDialogTitle">إضافة تصنيف</h2><p>التصنيف يجمع الستيكرات المتشابهة تحت اسم واحد</p></div>
            </div>
            <button class="dialog-close" type="button" data-dialog-close aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
        </header>
        <div class="form-body">
            <label><span>اسم التصنيف</span><input id="categoryNameInput" type="text" required maxlength="60" placeholder="مثال: فلسطين"></label>
            <p class="image-upload-error" id="categoryError" role="alert" hidden></p>
        </div>
        <footer>
            <button class="secondary-button" type="button" data-dialog-close>إلغاء</button>
            <button class="primary-button" type="submit">حفظ التصنيف</button>
        </footer>
    </form>
</dialog>

<dialog class="product-dialog" id="stickerDialog" aria-labelledby="stickerDialogTitle">
    <form class="product-form" id="stickerForm" method="dialog" novalidate>
        <header>
            <div>
                <span class="dialog-icon"><i class="bi bi-emoji-smile"></i></span>
                <div><h2 id="stickerDialogTitle">إضافة ستيكر</h2><p>ارفع صورة الستيكر واختر تصنيفه</p></div>
            </div>
            <button class="dialog-close" type="button" data-dialog-close aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
        </header>
        <div class="form-body">
            <div class="product-image-field">
                <span class="field-label">صورة الستيكر</span>
                <label class="image-upload" for="stickerImageInput">
                    <input id="stickerImageInput" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                    <span class="image-preview" aria-hidden="true">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <img id="stickerPreviewImg" alt="معاينة الستيكر" hidden>
                    </span>
                    <span class="upload-copy">
                        <strong id="stickerUploadTitle">اختر صورة للستيكر</strong>
                        <small>PNG أو JPG أو WEBP أو SVG، بحد أقصى 2MB (يفضّل PNG بخلفية شفافة)</small>
                    </span>
                    <span class="upload-button"><i class="bi bi-image"></i><span>اختيار صورة</span></span>
                </label>
            </div>
            <label><span>اسم الستيكر</span><input id="stickerNameInput" type="text" required maxlength="80" placeholder="مثال: غصن زيتون"></label>
            <label><span>التصنيف</span><select id="stickerCategoryInput" required></select></label>
            <p class="image-upload-error" id="stickerError" role="alert" hidden></p>
        </div>
        <footer>
            <button class="secondary-button" type="button" data-dialog-close>إلغاء</button>
            <button class="primary-button" id="stickerSubmitButton" type="submit">حفظ الستيكر</button>
        </footer>
    </form>
</dialog>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminStickers.js').'?v='.filemtime(public_path('front/js/admin/adminStickers.js')) }}"></script>
@endpush
