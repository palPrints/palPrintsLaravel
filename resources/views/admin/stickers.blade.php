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
            </div>
            <div class="stickers-header-actions">
                <button class="secondary-button" id="addCategoryButton" type="button"><i class="bi bi-folder-plus"></i><span>إضافة تصنيف</span></button>
                <button class="primary-button" id="addStickerButton" type="button"><i class="bi bi-plus-lg"></i><span>إضافة ملصق</span></button>
            </div>
        </header>

        <nav class="sticker-admin-tabs" role="tablist" aria-label="إدارة الملصقات">
            @foreach(['library' => 'مكتبة الملصقات', 'categories' => 'التصنيفات', 'pricing' => 'الأسعار وحدود الطلب', 'printing' => 'إعدادات الطباعة'] as $key => $label)
                <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-admin-tab="{{ $key }}">{{ $label }}</button>
            @endforeach
        </nav>
        <div class="admin-sticker-layout">
            <section class="admin-sticker-library" id="panel-library" role="tabpanel" aria-labelledby="tab-library">
                <div class="sticker-filters">
                    <label class="sticker-search"><i class="bi bi-search"></i><input id="stickerSearch" type="search" placeholder="البحث عن ملصق" aria-label="البحث عن ملصق"></label>
                    <select id="categoryFilter" aria-label="التصنيف"></select>
                    <select id="statusFilter" aria-label="الحالة"><option value="all">كل الحالات</option><option value="1">مفعل</option><option value="0">مخفي</option></select>
                    <output id="stickerCount"></output>
                </div>
                <div class="products-grid stickers-grid" id="stickersGrid"></div>
                <div class="empty-state" id="stickersEmpty" hidden><i class="bi bi-emoji-smile"></i><h2>لا توجد ملصقات</h2><p>أضف تصنيفا ثم ارفع ملصقات PNG/JPG.</p></div>
            </section>
            <section id="panel-categories" role="tabpanel" aria-labelledby="tab-categories" hidden>
                <div class="category-table-wrap"><table class="category-table"><thead><tr><th>التصنيف</th><th>الملصقات</th><th>الترتيب</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody id="categoriesBar"></tbody></table></div>
            </section>

            <aside class="admin-sticker-settings">
                <form id="settingsForm">
                    <section id="panel-pricing" role="tabpanel" aria-labelledby="tab-pricing" hidden>
                    <div class="settings-intro"><h2>الأسعار وحدود الطلب</h2><p>هذه القيم تحدد كيف يُحسب سعر طلب الملصقات عند الزبون، وكم يستطيع أن يطلب ويرفع.</p></div>
                    <div class="settings-card">
                        <header><i class="bi bi-box-seam"></i><div><h3>حدود الكمية</h3><p>أقل وأكثر كمية يقبلها الموقع في الطلب الواحد.</p></div></header>
                        <div class="settings-grid">
                            <label><span>الحد الأدنى للطلب</span><div class="unit-input"><input name="pricing.min_quantity" type="number" min="1"><em>ملصق</em></div><small>لا يمكن للزبون طلب أقل من هذا العدد</small></label>
                            <label><span>تنبيه الكمية الكبيرة</span><div class="unit-input"><input name="pricing.warning_quantity" type="number" min="1"><em>ملصق</em></div><small>فوق هذا العدد تظهر للزبون رسالة تنبيه</small></label>
                        </div>
                    </div>
                    <div class="settings-card">
                        <header><i class="bi bi-tag"></i><div><h3>سعر الملصقات</h3><p>سعر الطلب = سعر أول كمية + سعر كل ملصق زائد عنها.</p></div></header>
                        <div class="settings-grid">
                            <label><span>سعر أول كمية</span><div class="unit-input"><input name="pricing.base_price" type="number" min="0" step="0.01"><em>₪</em></div><small>السعر الثابت للكمية الأساسية</small></label>
                            <label><span>الكمية الأساسية</span><div class="unit-input"><input name="pricing.base_quantity" type="number" min="1"><em>ملصق</em></div><small>عدد الملصقات المشمولة بالسعر الأول</small></label>
                            <label><span>سعر الملصق الإضافي</span><div class="unit-input"><input name="pricing.extra_unit_price" type="number" min="0" step="0.01"><em>₪</em></div><small>يُضاف عن كل ملصق فوق الكمية الأساسية</small></label>
                        </div>
                    </div>
                    <div class="settings-card">
                        <header><i class="bi bi-scissors"></i><div><h3>القص الحر</h3><p>رسم إضافي على التصاميم التي تُقص على شكلها.</p></div></header>
                        <div class="settings-grid">
                            <label><span>عدد التصاميم في كل رسم</span><div class="unit-input"><input name="pricing.contour_group_size" type="number" min="1"><em>تصميم</em></div><small>كل كم تصميم يُحسب رسم واحد</small></label>
                            <label><span>قيمة الرسم</span><div class="unit-input"><input name="pricing.contour_group_price" type="number" min="0" step="0.01"><em>₪</em></div><small>يُضاف لكل مجموعة بهذا العدد</small></label>
                        </div>
                    </div>
                    <div class="settings-card">
                        <header><i class="bi bi-cloud-arrow-up"></i><div><h3>رفع الملفات</h3><p>الحد الأعلى لما يرفعه الزبون في الطلب الواحد.</p></div></header>
                        <div class="settings-grid">
                            <label><span>أعلى عدد ملفات</span><div class="unit-input"><input name="upload.max_files" type="number" min="1"><em>ملف</em></div><small>عدد الصور في الطلب</small></label>
                            <label><span>حجم الملف الواحد</span><div class="unit-input"><input name="upload.max_file_mb" type="number" min="1"><em>MB</em></div><small>أكبر حجم لصورة واحدة</small></label>
                            <label><span>الحجم الكلي</span><div class="unit-input"><input name="upload.max_total_mb" type="number" min="1"><em>MB</em></div><small>مجموع أحجام كل الصور</small></label>
                        </div>
                    </div>
                    </section>
                    <section id="panel-printing" role="tabpanel" aria-labelledby="tab-printing" hidden>
                    <div class="settings-intro"><h2>إعدادات الطباعة</h2><p>تحدد كيف تُرتب الملصقات على ورقة A4 قبل إرسالها للمطبعة. المعاينة على اليسار تتحدث مباشرة.</p></div>
                    <div class="print-layout">
                    <div class="print-fields">
                        <div class="settings-card">
                            <header><i class="bi bi-bounding-box"></i><div><h3>هوامش الورقة</h3><p>المسافة بين حافة الورقة والملصقات.</p></div></header>
                            <div class="settings-grid">
                                <label><span>الأعلى</span><div class="unit-input"><input name="layout.margin_top_mm" type="number" min="0" step="0.1"><em>mm</em></div><small></small></label>
                                <label><span>اليمين</span><div class="unit-input"><input name="layout.margin_right_mm" type="number" min="0" step="0.1"><em>mm</em></div><small></small></label>
                                <label><span>الأسفل</span><div class="unit-input"><input name="layout.margin_bottom_mm" type="number" min="0" step="0.1"><em>mm</em></div><small></small></label>
                                <label><span>اليسار</span><div class="unit-input"><input name="layout.margin_left_mm" type="number" min="0" step="0.1"><em>mm</em></div><small></small></label>
                            </div>
                        </div>
                        <div class="settings-card">
                            <header><i class="bi bi-grid-3x3-gap"></i><div><h3>المسافة وخط القص</h3><p>التباعد بين الملصقات وشكل خط القص.</p></div></header>
                            <div class="settings-grid">
                                <label><span>المسافة بين الملصقات</span><div class="unit-input"><input name="layout.gap_mm" type="number" min="0" step="0.1"><em>mm</em></div><small></small></label>
                                <label><span>سماكة خط القص</span><div class="unit-input"><input name="layout.cut_line_width_mm" type="number" min="0" step="0.05"><em>mm</em></div><small></small></label>
                                <label><span>لون خط القص</span><input name="layout.cut_line_color" type="color" class="color-input"><small></small></label>
                            </div>
                        </div>
                        <div class="settings-card">
                            <header><i class="bi bi-aspect-ratio"></i><div><h3>الجودة والأمان</h3><p>حدود تحمي التصميم عند القص وتحدد وضوح الطباعة.</p></div></header>
                            <div class="settings-grid">
                                <label><span>الخروج عن الحد Bleed</span><div class="unit-input"><input name="layout.bleed_mm" type="number" min="0" step="0.1"><em>mm</em></div><small>يمتد التصميم خارج خط القص</small></label>
                                <label><span>الهامش الآمن Safe margin</span><div class="unit-input"><input name="layout.safe_margin_mm" type="number" min="0" step="0.1"><em>mm</em></div><small>لا تضع نصوصًا خارجه</small></label>
                                <label><span>الدقة DPI</span><div class="unit-input"><input name="layout.dpi" type="number" min="72" step="1"><em>DPI</em></div><small>الدقة المطلوبة للصور</small></label>
                            </div>
                        </div>
                        <div class="settings-card">
                            <header><i class="bi bi-rulers"></i><div><h3>المقاسات المتاحة</h3><p>الأحجام التي يختار منها الزبون للملصق.</p></div></header>
                            <div class="sizes-field"><div id="sizesRows"></div><button type="button" class="secondary-button" id="addSize"><i class="bi bi-plus-lg"></i> إضافة مقاس</button></div>
                        </div>
                    </div>
                    <aside class="print-preview settings-card">
                        <header><i class="bi bi-file-earmark-image"></i><div><h3>معاينة ورقة A4</h3><p>هكذا ستُطبع الملصقات.</p></div></header>
                        <div class="print-preview-body">
                    <div class="admin-a4-preview" id="adminA4Preview"></div>
                    <div class="preview-pagination"><button type="button" id="previewPrev" aria-label="الصفحة السابقة"><i class="bi bi-chevron-right"></i></button><output id="previewPage"></output><button type="button" id="previewNext" aria-label="الصفحة التالية"><i class="bi bi-chevron-left"></i></button></div>
                    <p id="previewError" role="status"></p>
                        </div>
                    </aside>
                    </div>
                    </section>
                    <div id="settingsSaveBar" hidden><span id="settingsStatus" role="status"></span><button class="primary-button" type="submit"><i class="bi bi-save"></i><span>حفظ الإعدادات</span></button></div>
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
            <div class="product-image-field">
                <span class="field-label">الصورة</span>
                <label class="image-upload" for="stickerImageInput">
                    <input id="stickerImageInput" type="file" accept="image/png,image/jpeg">
                    <span class="image-preview" aria-hidden="true">
                        <i class="bi bi-cloud-arrow-up" id="stickerImageIcon"></i>
                        <img id="stickerImagePreview" alt="معاينة الملصق" hidden>
                    </span>
                    <span class="upload-copy">
                        <strong id="stickerImageTitle">اختر صورة للملصق</strong>
                        <small>PNG أو JPG فقط</small>
                    </span>
                    <span class="upload-action">اختيار ملف</span>
                </label>
            </div>
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
                preview: @json(route('admin.stickers.preview')),
            }
        };
    </script>
    <script src="{{ asset('front/js/admin/adminStickers.js').'?v='.filemtime(public_path('front/js/admin/adminStickers.js')) }}"></script>
@endpush
