@extends('designer.layouts.app')

@section('title', 'محرر التصميم')
@section('main-class', 'source-editor-main')

@push('styles')
    <base href='{{ asset('front/designer/source/create') }}/'>
    <script>
        document.documentElement.classList.add("fonts-loading");
        window.palPrintsFontTimeout = window.setTimeout(() => document.documentElement.classList.remove("fonts-loading"), 3000);
    </script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@100..900&amp;family=Almarai:wght@400;800&amp;family=Amiri:wght@400;700&amp;family=Aref+Ruqaa:wght@400;700&amp;family=Baloo+Bhaijaan+2:wght@400..800&amp;family=Cairo:wght@200..1000&amp;family=Changa:wght@200..800&amp;family=El+Messiri:wght@400..700&amp;family=Gulzar&amp;family=Harmattan:wght@400;700&amp;family=IBM+Plex+Sans+Arabic:wght@400;700&amp;family=Jomhuria&amp;family=Katibeh&amp;family=Lalezar&amp;family=Lateef:wght@400;800&amp;family=Lemonada:wght@300..700&amp;family=Mada:wght@200..900&amp;family=Marhey:wght@300..700&amp;family=Markazi+Text:wght@400..700&amp;family=Mirza:wght@400;700&amp;display=block">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@100..900&amp;family=Noto+Naskh+Arabic:wght@400..700&amp;family=Noto+Sans+Arabic:wght@100..900&amp;family=Rakkas&amp;family=Readex+Pro:wght@160..700&amp;family=Reem+Kufi:wght@400..700&amp;family=Rubik:wght@300..900&amp;family=Scheherazade+New:wght@400;700&amp;family=Tajawal:wght@400;800&amp;family=Vibes&amp;display=block">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&amp;family=Lato:wght@400;800&amp;family=Merriweather:wght@300..900&amp;family=Montserrat:wght@100..900&amp;family=Open+Sans:wght@300..800&amp;family=Oswald:wght@200..700&amp;family=Playfair+Display:wght@400..900&amp;family=Poppins:wght@400;800&amp;family=Raleway:wght@100..900&amp;family=Roboto:wght@100..900&amp;display=block">
    <link rel='stylesheet' href='{{ asset('front/designer/source/create/assets/css/designer.css') }}'>
@endpush

@section('content')
    <div class='designer-shell'>
        <header class="designer-header">
            <a class="brand-mark" href="{{ route('designer.designs.create') }}" aria-label="العودة إلى اختيار المنتج">
                <img class="brand-logo" src="{{ asset('front/designer/source/create/assets/logo/PalPrints-logo.svg') }}" alt="PalPrints">
            </a>
            <div class="editor-title">
                <span class="eyebrow">استوديو التصميم</span>
                <h1>محرر التصميم</h1>
            </div>
            <div class="header-actions">
                <span class="save-status"><i class="bi bi-cloud-check" aria-hidden="true"></i> تم حفظ التغييرات</span>
                <button class="button button-outline" id="previewButton" type="button"><i class="bi bi-eye"
                        aria-hidden="true"></i> معاينة المنتج</button>
                <button class="button button-primary" id="saveButton" type="button"><i class="bi bi-download"
                        aria-hidden="true"></i> حفظ مؤقت</button>
            </div>
        </header>

        <div class="editor-layout">
            <aside class="tools-panel" aria-label="أدوات التصميم">
                <h2>أدوات التصميم</h2>
                <button class="tool-card active" type="button" data-tool="upload"><span class="tool-icon"><i
                            class="bi bi-cloud-arrow-up" aria-hidden="true"></i></span><span><strong>رفع
                            صورة</strong><small>ارفع صورتك الخاصة</small></span></button>
                <button class="tool-card" type="button" data-tool="text"><span class="tool-icon"><i class="bi bi-type"
                            aria-hidden="true"></i></span><span><strong>نص</strong><small>إضافة نص
                            جديد</small></span></button>
                <div class="tool-menu" data-tool-menu="elements">
                    <button class="tool-card" id="elementsTool" type="button" data-tool="elements" aria-expanded="false"
                        aria-controls="componentsLibrary"><span class="tool-icon"><i class="bi bi-shapes"
                                aria-hidden="true"></i></span><span><strong>عناصر</strong><small>إضافة عناصر
                                جاهزة</small></span><i class="bi bi-chevron-down tool-menu-chevron"
                            aria-hidden="true"></i></button>
                    <section class="components-library" id="componentsLibrary" aria-label="مكتبة العناصر" hidden>
                        <div class="components-library-heading"><strong>مكتبة العناصر</strong><small>اختر عنصراً
                                لإضافته</small></div>
                        <div class="components-grid" id="componentsGrid"></div>
                    </section>
                </div>
                <input id="imageUpload" type="file" accept="image/*" hidden>
                <div class="tip-card"><i class="bi bi-lightbulb" aria-hidden="true"></i><strong>نصيحة</strong>
                    <p>حدد منطقة الطباعة ثم اختر أداة لإضافة تصميمك.</p>
                </div>
            </aside>

            <section class="workspace" aria-label="مساحة العمل">
                <div class="canvas-stage" id="canvasStage">
                    <div class="stage-glow"></div>
                    <div class="product-canvas" id="productCanvas">
                        <img id="productImage" src="{{ asset('front/designer/source/create/assets/images/tshirt.webp') }}" alt="تي شيرت كلاسيكي">
                        <div class="print-zone" id="printZone" role="group" aria-label="منطقة الطباعة">
                            <div class="zone-border"></div>
                            <div class="zone-badge"><i class="bi bi-bounding-box" aria-hidden="true"></i> منطقة الطباعة
                            </div>
                            <div class="image-layers" id="imageLayers"></div>
                            <div class="text-layers" id="textLayers"></div>
                        </div>
                    </div>
                </div>
                <footer class="workspace-footer">
                    <button class="text-button" id="undoButton" type="button"><i class="bi bi-arrow-counterclockwise"
                            aria-hidden="true"></i> تراجع</button>
                    <button class="text-button muted" id="redoButton" type="button"><i class="bi bi-arrow-clockwise"
                            aria-hidden="true"></i> إعادة</button>
                    <div class="zoom-control"><button id="zoomOut" type="button" aria-label="تصغير"><i
                                class="bi bi-dash" aria-hidden="true"></i></button><strong
                            id="zoomValue">100%</strong><button id="zoomIn" type="button" aria-label="تكبير"><i
                                class="bi bi-plus" aria-hidden="true"></i></button></div>
                    <button class="text-button" id="fitButton" type="button"><i class="bi bi-fullscreen"
                            aria-hidden="true"></i> ملاءمة للشاشة</button>
                </footer>
            </section>

            <aside class="details-panel" aria-label="تفاصيل المنتج">
                <div class="details-mode-toggle" role="tablist" aria-label="نوع التخصيص">
                    <button class="details-mode-button active" type="button" data-details-mode="product" role="tab"
                        aria-selected="true">تخصيص المنتج</button>
                    <button class="details-mode-button" type="button" data-details-mode="text" role="tab"
                        aria-selected="false">تخصيص النص</button>
                </div>
                <section class="text-settings-panel" id="textPanel" hidden aria-label="إعدادات النص">
                    <div class="panel-heading">
                        <h2>إعدادات النص</h2><i class="bi bi-type" aria-hidden="true"></i>
                    </div>
                    <label class="settings-label" for="textContent">النص<textarea id="textContent" rows="2"
                            placeholder="اكتب النص هنا"></textarea></label>
                    <label class="settings-label" for="fontFamily">الخط
                        <select id="fontFamily">
                            <optgroup label="خطوط عربية ومتعددة اللغات">
                                <option value="Cairo">كايرو — Cairo</option>
                                <option value="Tajawal">تجوال — Tajawal</option>
                                <option value="Almarai">المراعي — Almarai</option>
                                <option value="Changa">شانغا — Changa</option>
                                <option value="Noto Kufi Arabic">نوتو كوفي — Noto Kufi Arabic</option>
                                <option value="Amiri">أميري — Amiri</option>
                                <option value="Noto Naskh Arabic">نوتو نسخ — Noto Naskh Arabic</option>
                                <option value="IBM Plex Sans Arabic">آي بي إم بليكس — IBM Plex Sans Arabic</option>
                                <option value="Reem Kufi">ريم كوفي — Reem Kufi</option>
                                <option value="El Messiri">المسيري — El Messiri</option>
                                <option value="Mada">مدى — Mada</option>
                                <option value="Harmattan">هارمتان — Harmattan</option>
                                <option value="Lateef">لطيف — Lateef</option>
                                <option value="Scheherazade New">شهرزاد — Scheherazade New</option>
                                <option value="Markazi Text">مركزي — Markazi Text</option>
                                <option value="Aref Ruqaa">عارف رقعة — Aref Ruqaa</option>
                                <option value="Rakkas">ركّاس — Rakkas</option>
                                <option value="Lemonada">ليمونادا — Lemonada</option>
                                <option value="Katibeh">كتيبة — Katibeh</option>
                                <option value="Mirza">ميرزا — Mirza</option>
                                <option value="Baloo Bhaijaan 2">بالو بهيجان — Baloo Bhaijaan 2</option>
                                <option value="Noto Sans Arabic">نوتو سانس — Noto Sans Arabic</option>
                                <option value="Alexandria">الإسكندرية — Alexandria</option>
                                <option value="Readex Pro">ريديكس برو — Readex Pro</option>
                                <option value="Rubik">روبيك — Rubik</option>
                                <option value="Lalezar">لاله زار — Lalezar</option>
                                <option value="Jomhuria">جمهورية — Jomhuria</option>
                                <option value="Vibes">فايبس — Vibes</option>
                                <option value="Gulzar">جولزار — Gulzar</option>
                                <option value="Marhey">مرحي — Marhey</option>
                            </optgroup>
                            <optgroup label="خطوط عالمية">
                                <option value="Roboto">Roboto</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Montserrat">Montserrat</option>
                                <option value="Poppins">Poppins</option>
                                <option value="Lato">Lato</option>
                                <option value="Playfair Display">Playfair Display</option>
                                <option value="Oswald">Oswald</option>
                                <option value="Raleway">Raleway</option>
                                <option value="Merriweather">Merriweather</option>
                                <option value="Bebas Neue">Bebas Neue</option>
                            </optgroup>
                        </select>
                    </label>
                    <div class="settings-row"><label class="settings-label" for="textSize">حجم الخط<input id="textSize"
                                type="range" min="12" max="64" value="28"></label><label
                            class="settings-label font-size-number" for="textSizeNumber">القيمة<input
                                id="textSizeNumber" type="number" min="12" max="64" value="28"></label><label
                            class="settings-label color-setting" for="textColor">اللون<input id="textColor" type="color"
                                value="#6432f2"></label></div>
                    <div class="settings-group"><span>المحاذاة</span>
                        <div class="alignment-buttons"><button class="alignment-button" type="button" data-align="right"
                                aria-label="محاذاة لليمين"><i class="bi bi-text-right"></i></button><button
                                class="alignment-button active" type="button" data-align="center"
                                aria-label="توسيط النص"><i class="bi bi-text-center"></i></button><button
                                class="alignment-button" type="button" data-align="left" aria-label="محاذاة لليسار"><i
                                    class="bi bi-text-left"></i></button></div>
                    </div>
                    <div class="settings-group"><span>المسافات</span>
                        <div class="stepper-row"><label>تباعد الأحرف<input id="letterSpacing" type="number" value="0"
                                    step="0.1"></label><label>تباعد الأسطر<input id="lineHeight" type="number"
                                    value="1.2" step="0.1"></label></div>
                    </div>
                </section>
                <section class="component-transform-panel settings-group" id="componentTransformPanel" hidden
                    aria-label="موضع العنصر المحدد"><span>الموضع والتحويل</span>
                    <div class="stepper-row"><label>من اليمين X<input id="positionX" type="number" min="0"
                                value="0"></label><label>من الأسفل Y<input id="positionY" type="number" min="0"
                                value="0"></label><label>الدوران<input id="rotation" type="number" value="0"></label>
                    </div>
                </section>
                <section class="selection-actions" id="selectionActions" hidden aria-label="إجراءات العنصر المحدد">
                    <span id="selectionActionLabel">العنصر المحدد</span>
                    <div class="action-row layer-action-row" role="group" aria-label="ترتيب طبقة العنصر المحدد">
                        <button type="button" id="bringForward"><i class="bi bi-layer-forward" aria-hidden="true"></i>
                            إرسال للأمام</button><button type="button" id="sendBackward"><i
                                class="bi bi-layer-backward" aria-hidden="true"></i> إرسال للخلف</button>
                    </div>
                    <div class="action-row"><button type="button" id="duplicateText"><i class="bi bi-copy"></i>
                            تكرار</button><button class="danger-action" type="button" id="deleteText"><i
                                class="bi bi-trash3"></i> حذف</button></div>
                </section>
                <button class="add-text-button" id="addTextButton" type="button"><i class="bi bi-check2"
                        aria-hidden="true"></i> تطبيق النص</button>
                <div class="panel-heading">
                    <h2>المنتج</h2><i class="bi bi-t-shirt" aria-hidden="true"></i>
                </div>
                <label class="product-field" for="productName">المنتج<input id="productName" value="تي شيرت كلاسيكي"
                        readonly></label>
                <section class="option-section">
                    <div class="section-title">
                        <h3>اللون</h3><i class="bi bi-palette" aria-hidden="true"></i>
                    </div>
                    <div class="swatches" id="swatches"></div>
                </section>
                <section class="option-section">
                    <div class="section-title">
                        <h3>المقاس</h3>
                    </div>
                    <div class="sizes" id="sizes"></div>
                </section>
                <section class="option-section print-area-section">
                    <div class="section-title">
                        <h3>مكان الطباعة</h3><i class="bi bi-bounding-box" aria-hidden="true"></i>
                    </div><label class="area-select-label" for="areaSelect"><span>منطقة الطباعة</span><select
                            id="areaSelect"></select><i class="bi bi-chevron-down" aria-hidden="true"></i></label>
                </section>
                <section class="print-info">
                    <div class="section-title">
                        <h3>معلومات الطباعة</h3><i class="bi bi-info-circle" aria-hidden="true"></i>
                    </div>
                    <div class="info-row"><span>منطقة الطباعة</span><strong id="areaDimensions">28 × 36 سم</strong>
                    </div>
                    <div class="info-row accent"><span>منطقة الأمان</span><strong>24 × 32 سم</strong></div>
                </section>
                <div class="notice"><i class="bi bi-shield-check" aria-hidden="true"></i>
                    <div><strong>معلومة</strong>
                        <p>تصميمك سيظهر داخل منطقة الطباعة الآمنة للحصول على أفضل نتيجة.</p>
                    </div>
                </div>
            </aside>
        </div>

    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
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
    <script src='{{ asset('front/designer/source/create/assets/js/utils/canvas-history-manager.js') }}'></script>
    <script src='{{ asset('front/designer/source/create/assets/js/designer.js') }}'></script>
@endpush
