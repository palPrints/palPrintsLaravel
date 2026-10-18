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
                <p>تحكم بتصنيفات الملصقات، المكتبة الجاهزة، إعدادات السعر، ومعاينة ورقة A4.</p>
            </div>
            <div class="stickers-header-actions">
                <button class="secondary-button" id="addCategoryButton" type="button"><i class="bi bi-folder-plus"></i><span>إضافة تصنيف</span></button>
                <button class="primary-button" id="addStickerButton" type="button"><i class="bi bi-plus-lg"></i><span>إضافة ملصق</span></button>
            </div>
        </header>

        <div class="admin-sticker-layout">
            <section class="admin-sticker-library">
                <div class="products-summary stickers-categories-bar" id="categoriesBar"></div>
                <div class="products-grid stickers-grid" id="stickersGrid"></div>
                <div class="empty-state" id="stickersEmpty" hidden><i class="bi bi-emoji-smile"></i><h2>لا توجد ملصقات</h2><p>أضف تصنيفا ثم ارفع ملصقات PNG/JPG.</p></div>
            </section>

            <aside class="admin-sticker-settings">
                <form id="settingsForm">
                    <h2>إعدادات المنتج و A4</h2>
                    <div class="settings-grid">
                        <label><span>الحد الأدنى</span><input name="pricing.min_quantity" type="number" min="1"></label>
                        <label><span>تحذير الكمية</span><input name="pricing.warning_quantity" type="number" min="1"></label>
                        <label><span>سعر أول كمية</span><input name="pricing.base_price" type="number" min="0" step="0.01"></label>
                        <label><span>كمية السعر الأساسي</span><input name="pricing.base_quantity" type="number" min="1"></label>
                        <label><span>سعر الإضافي</span><input name="pricing.extra_unit_price" type="number" min="0" step="0.01"></label>
                        <label><span>كل كم قص حر</span><input name="pricing.contour_group_size" type="number" min="1"></label>
                        <label><span>رسم القص الحر</span><input name="pricing.contour_group_price" type="number" min="0" step="0.01"></label>
                        <label><span>أعلى عدد ملفات</span><input name="upload.max_files" type="number" min="1"></label>
                        <label><span>حجم الملف MB</span><input name="upload.max_file_mb" type="number" min="1"></label>
                        <label><span>الحجم الكلي MB</span><input name="upload.max_total_mb" type="number" min="1"></label>
                        <label><span>هامش أعلى mm</span><input name="layout.margin_top_mm" type="number" min="0" step="0.1"></label>
                        <label><span>هامش يمين mm</span><input name="layout.margin_right_mm" type="number" min="0" step="0.1"></label>
                        <label><span>هامش أسفل mm</span><input name="layout.margin_bottom_mm" type="number" min="0" step="0.1"></label>
                        <label><span>هامش يسار mm</span><input name="layout.margin_left_mm" type="number" min="0" step="0.1"></label>
                        <label><span>المسافة mm</span><input name="layout.gap_mm" type="number" min="0" step="0.1"></label>
                        <label><span>سماكة خط القص</span><input name="layout.cut_line_width_mm" type="number" min="0" step="0.05"></label>
                        <label><span>لون خط القص</span><input name="layout.cut_line_color" type="color"></label>
                        <label><span>Bleed mm</span><input name="layout.bleed_mm" type="number" min="0" step="0.1"></label>
                        <label><span>Safe margin mm</span><input name="layout.safe_margin_mm" type="number" min="0" step="0.1"></label>
                        <label><span>DPI</span><input name="layout.dpi" type="number" min="72"></label>
                    </div>
                    <label class="sizes-field"><span>المقاسات المتاحة</span><textarea id="sizesInput" rows="4"></textarea><small>صيغة: key,label,width_mm,height_mm في كل سطر. مثال: 5x5,5 x 5 cm,50,50</small></label>
                    <div class="admin-a4-preview" id="adminA4Preview"></div>
                    <button class="primary-button" type="submit"><i class="bi bi-save"></i><span>حفظ الإعدادات</span></button>
                </form>
            </aside>
        </div>
    </section>
</main>

<dialog class="product-dialog" id="categoryDialog">
    <form class="product-form" id="categoryForm" method="dialog">
        <header><div><span class="dialog-icon"><i class="bi bi-folder"></i></span><div><h2 id="categoryDialogTitle">إضافة تصنيف</h2><p>الترتيب الأصغر يظهر أولا.</p></div></div><button class="dialog-close" type="button" data-dialog-close><i class="bi bi-x-lg"></i></button></header>
        <div class="form-body">
            <label><span>اسم التصنيف</span><input id="categoryNameInput" type="text" required maxlength="80"></label>
            <label><span>الترتيب</span><input id="categoryOrderInput" type="number" min="0" value="0"></label>
            <label class="check-row"><input id="categoryActiveInput" type="checkbox" checked><span>مفعل</span></label>
            <p class="image-upload-error" id="categoryError" hidden></p>
        </div>
        <footer><button class="secondary-button" type="button" data-dialog-close>إلغاء</button><button class="primary-button" type="submit">حفظ</button></footer>
    </form>
</dialog>

<dialog class="product-dialog" id="stickerDialog">
    <form class="product-form" id="stickerForm" method="dialog">
        <header><div><span class="dialog-icon"><i class="bi bi-emoji-smile"></i></span><div><h2 id="stickerDialogTitle">إضافة ملصق</h2><p>PNG أو JPG فقط.</p></div></div><button class="dialog-close" type="button" data-dialog-close><i class="bi bi-x-lg"></i></button></header>
        <div class="form-body">
            <label><span>الصورة</span><input id="stickerImageInput" type="file" accept="image/png,image/jpeg"></label>
            <label><span>اسم الملصق</span><input id="stickerNameInput" type="text" required maxlength="100"></label>
            <label><span>التصنيف</span><select id="stickerCategoryInput" required></select></label>
            <label><span>الترتيب</span><input id="stickerOrderInput" type="number" min="0" value="0"></label>
            <label class="check-row"><input id="stickerActiveInput" type="checkbox" checked><span>مفعل</span></label>
            <p class="image-upload-error" id="stickerError" hidden></p>
        </div>
        <footer><button class="secondary-button" type="button" data-dialog-close>إلغاء</button><button class="primary-button" type="submit">حفظ</button></footer>
    </form>
</dialog>
@endsection

@push('scripts')
    <script>
        window.PalPrintsAdminStickers = {
            categories: @json($stickerCategories),
            settings: @json($stickerSettings),
            routes: {
                categoryStore: @json(route('admin.stickers.categories.store')),
                categoryUpdate: @json(route('admin.stickers.categories.update', ['stickerCategory' => '__ID__'])),
                categoryDestroy: @json(route('admin.stickers.categories.destroy', ['stickerCategory' => '__ID__'])),
                itemStore: @json(route('admin.stickers.items.store')),
                itemUpdate: @json(route('admin.stickers.items.update', ['stickerLibraryItem' => '__ID__'])),
                itemDestroy: @json(route('admin.stickers.items.destroy', ['stickerLibraryItem' => '__ID__'])),
                settings: @json(route('admin.stickers.settings.update')),
            }
        };
    </script>
    <script src="{{ asset('front/js/admin/adminStickers.js').'?v='.filemtime(public_path('front/js/admin/adminStickers.js')) }}"></script>
@endpush
