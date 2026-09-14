@extends('customer.layouts.app')

@section('title', 'طباعة الورق')
@section('meta-description', 'طباعة الورق في متجر PalPrints')
@section('body-class', 'storefront-page paper-printing-page')
@section('main-class', 'paper-main')
@section('main-id', 'paperMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/paperPrinting.css') }}?v={{ filemtime(public_path('front/css/customer/paperPrinting.css')) }}">
@endpush

@section('content')
        <div class="paper-page-heading">
          <div>
            <ol class="profile-breadcrumb" aria-label="مسار التنقل">
              <li><a href="{{ route('home') }}">الرئيسية</a></li>
              <li><a href="{{ route('customer.store') }}#products">المنتجات</a></li>
              <li aria-current="page"><span>طباعة الورق</span></li>
            </ol>
            <div class="paper-title-row">
              <span class="paper-title-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
              <div>
                <h1>طباعة الورق</h1>
                <p>ارفع ملفاتك، اضبط خصائصها، ثم اختر طريقة التجميع والتغليف.</p>
              </div>
            </div>
          </div>
        </div>

        <nav class="paper-progress" aria-label="مراحل طلب الطباعة">
          <button type="button" class="paper-progress-step is-active" data-progress-step="1" data-no-press aria-current="step">
            <span class="progress-number">1</span><span class="progress-copy"><strong>الملفات</strong><small>الرفع والمعالجة</small></span>
          </button>
          <span class="progress-line" aria-hidden="true"></span>
          <button type="button" class="paper-progress-step" data-progress-step="2" data-no-press disabled>
            <span class="progress-number">2</span><span class="progress-copy"><strong>خصائص الطباعة</strong><small>الورق والألوان</small></span>
          </button>
          <span class="progress-line" aria-hidden="true"></span>
          <button type="button" class="paper-progress-step" data-progress-step="3" data-no-press disabled>
            <span class="progress-number">3</span><span class="progress-copy"><strong>التجميع والتغليف</strong><small>شكل المطبوعات</small></span>
          </button>
          <span class="progress-line" aria-hidden="true"></span>
          <button type="button" class="paper-progress-step" data-progress-step="4" data-no-press disabled>
            <span class="progress-number">4</span><span class="progress-copy"><strong>المراجعة</strong><small>تأكيد الطلب</small></span>
          </button>
        </nav>

        <div class="paper-configurator-layout">
          <div class="paper-workspace">
            <section class="journey-stage is-active" data-stage="1" aria-labelledby="stageTitle1">
              <button type="button" class="stage-mobile-header" data-stage-toggle="1" aria-expanded="true">
                <span class="stage-mobile-number">1</span><span><strong>الملفات</strong><small id="stageSummary1">المرحلة الحالية</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i>
              </button>
              <div class="stage-body">
                <header class="stage-heading">
                  <div><span class="stage-kicker">الخطوة 1 من 4</span><h2 id="stageTitle1">ابدأ برفع ملفاتك</h2><p>سنحلل الصفحات أولًا حتى تكون الخصائص والسعر أدق.</p></div>
                  <span class="stage-heading-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
                </header>

                <div class="upload-zone" id="uploadZone">
                  <input type="file" id="paperFileInput" accept=".pdf,.jpg,.jpeg,.png,.docx,.pptx" multiple hidden>
                  <span class="upload-illustration" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
                  <h3>اسحب ملفاتك إلى هنا</h3>
                  <p>أو اخترها من جهازك لبدء المعالجة</p>
                  <button type="button" class="paper-button is-primary" id="chooseFilesButton"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>اختيار الملفات</span></button>
                  <div class="upload-rules" aria-label="شروط الرفع">
                    <span><i class="bi bi-file-earmark-check" aria-hidden="true"></i>PDF, JPG, PNG, DOCX, PPTX</span>
                    <span><i class="bi bi-hdd" aria-hidden="true"></i><span>50MB لكل ملف</span></span>
                    <span><i class="bi bi-layers" aria-hidden="true"></i><span>10 ملفات / 500 صفحة</span></span>
                  </div>
                </div>

                <div class="file-section" id="fileSection" hidden>
                  <div class="file-section-header">
                    <div><h3>الملفات المرفوعة</h3><p id="fileCounter" aria-live="polite"></p></div>
                    <button type="button" class="paper-button is-soft is-small" id="addMoreFilesButton"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>إضافة ملفات</span></button>
                  </div>
                  <div class="paper-file-list" id="paperFileList"></div>
                </div>

                <div class="stage-error" id="stageError1" role="alert" hidden></div>
                <div class="stage-actions"><span></span><button type="button" class="paper-button is-primary" data-next-step="2"><span>متابعة إلى خصائص الطباعة</span><i class="bi bi-arrow-left" aria-hidden="true"></i></button></div>
              </div>
            </section>

            <section class="journey-stage" data-stage="2" aria-labelledby="stageTitle2">
              <button type="button" class="stage-mobile-header" data-stage-toggle="2" aria-expanded="false" disabled>
                <span class="stage-mobile-number">2</span><span><strong>خصائص الطباعة</strong><small id="stageSummary2">أكمل المرحلة السابقة أولًا</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i>
              </button>
              <div class="stage-body">
                <header class="stage-heading">
                  <div><span class="stage-kicker">الخطوة 2 من 4</span><h2 id="stageTitle2">اختر خصائص الطباعة</h2><p>تطبّق هذه الخصائص على جميع الملفات، ويمكنك تخصيص أي ملف لاحقًا.</p></div>
                  <span class="stage-heading-icon is-violet" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                </header>

                <div class="settings-banner">
                  <div><i class="bi bi-layers" aria-hidden="true"></i><span><strong>الإعداد العام</strong><small id="overrideCountText">سيُطبق على جميع الملفات</small></span></div>
                  <button type="button" class="paper-text-button" id="applyAllButton" hidden>تطبيق العام على الجميع</button>
                </div>

                <div class="option-groups" id="globalOptions">
                  <fieldset class="option-group" data-option-group="size"><legend><i class="bi bi-file-earmark" aria-hidden="true"></i><span>حجم الورق</span><em>مطلوب</em></legend><div class="choice-grid is-three"><button type="button" class="choice-card" data-value="A5"><strong>A5</strong><small>148 × 210 mm</small></button><button type="button" class="choice-card" data-value="A4"><strong>A4</strong><small>210 × 297 mm</small></button><button type="button" class="choice-card" data-value="A3"><strong>A3</strong><small>297 × 420 mm</small></button></div></fieldset>
                  <fieldset class="option-group" data-option-group="paper"><legend><i class="bi bi-stack" aria-hidden="true"></i><span>نوع الورق</span><em>مطلوب</em></legend><div class="choice-grid is-three"><button type="button" class="choice-card" data-value="standard"><i class="bi bi-file-earmark"></i><strong>عادي 80 جم</strong><small>للمستندات اليومية</small></button><button type="button" class="choice-card" data-value="thick"><i class="bi bi-file-earmark-richtext"></i><strong>فاخر 120 جم</strong><small>أكثر سماكة</small></button><button type="button" class="choice-card" data-value="coated"><i class="bi bi-stars"></i><strong>مصقول 150 جم</strong><small>ألوان أوضح</small></button></div></fieldset>
                  <fieldset class="option-group" data-option-group="color"><legend><i class="bi bi-droplet" aria-hidden="true"></i><span>لون الطباعة</span><em>مطلوب</em></legend><div class="choice-grid is-two"><button type="button" class="choice-card" data-value="bw"><span class="choice-swatch is-bw"></span><strong>أبيض وأسود</strong><small>اقتصادي وواضح</small></button><button type="button" class="choice-card" data-value="color"><span class="choice-swatch is-color"></span><strong>ملون</strong><small>للعروض والصور</small></button></div></fieldset>
                  <fieldset class="option-group" data-option-group="sides"><legend><i class="bi bi-files" aria-hidden="true"></i><span>جوانب الطباعة</span><em>مطلوب</em></legend><div class="choice-grid is-two"><button type="button" class="choice-card" data-value="single"><i class="bi bi-file-earmark"></i><strong>وجه واحد</strong><small>كل صفحة على ورقة</small></button><button type="button" class="choice-card" data-value="double"><i class="bi bi-front"></i><strong>وجهين</strong><small>أوراق أقل</small></button></div></fieldset>
                  <fieldset class="option-group" data-option-group="layout"><legend><i class="bi bi-grid" aria-hidden="true"></i><span>تخطيط الصفحة</span><em>مطلوب</em></legend><div class="choice-grid is-three"><button type="button" class="choice-card" data-value="1"><i class="bi bi-square"></i><strong>صفحة واحدة</strong><small>لكل وجه</small></button><button type="button" class="choice-card" data-value="2"><i class="bi bi-layout-split"></i><strong>صفحتان</strong><small>لكل وجه</small></button><button type="button" class="choice-card" data-value="4"><i class="bi bi-grid"></i><strong>4 صفحات</strong><small>لكل وجه</small></button></div></fieldset>
                </div>

                <div class="file-customization-panel">
                  <div class="file-section-header"><div><h3>تخصيص ملفات منفردة</h3><p>اختياري — استخدمه فقط إذا احتاج ملف إلى خصائص مختلفة.</p></div></div>
                  <div class="customization-list" id="customizationList"></div>
                  <div class="override-editor" id="overrideEditor" hidden></div>
                </div>

                <div class="stage-error" id="stageError2" role="alert" hidden></div>
                <div class="stage-actions"><button type="button" class="paper-button is-ghost" data-go-step="1"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>السابق</span></button><button type="button" class="paper-button is-primary" data-next-step="3"><span>تأكيد خصائص الطباعة</span><i class="bi bi-arrow-left" aria-hidden="true"></i></button></div>
              </div>
            </section>

            <section class="journey-stage" data-stage="3" aria-labelledby="stageTitle3">
              <button type="button" class="stage-mobile-header" data-stage-toggle="3" aria-expanded="false" disabled>
                <span class="stage-mobile-number">3</span><span><strong>التجميع والتغليف</strong><small id="stageSummary3">أكمل المرحلة السابقة أولًا</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i>
              </button>
              <div class="stage-body">
                <header class="stage-heading"><div><span class="stage-kicker">الخطوة 3 من 4</span><h2 id="stageTitle3">كيف تريد استلام ملفاتك؟</h2><p>اختر طريقة التجميع، ثم التغليف المناسب للمطبوعات.</p></div><span class="stage-heading-icon is-cyan" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span></header>

                <fieldset class="option-group grouping-choice" id="groupingChoice"><legend><i class="bi bi-collection" aria-hidden="true"></i><span>طريقة التعامل مع الملفات</span><em>مطلوب</em></legend><div class="choice-grid is-two"><button type="button" class="choice-card grouping-card" data-grouping="combined"><i class="bi bi-layers"></i><span><strong>دمج الجميع</strong><small>ملزمة واحدة حسب الترتيب</small></span></button><button type="button" class="choice-card grouping-card" data-grouping="separate"><i class="bi bi-files"></i><span><strong>فصل الجميع</strong><small>كل ملف كمطبوعة مستقلة</small></span></button></div></fieldset>

                <div class="binding-workspace" id="bindingWorkspace" hidden>
                  <section class="reorder-panel" id="reorderPanel" hidden><div class="section-label"><span><i class="bi bi-sort-down" aria-hidden="true"></i><strong>ترتيب الملفات داخل الملزمة</strong></span><small>استخدم الأسهم لتحديد ترتيب الدمج.</small></div><div class="reorder-list" id="reorderList"></div></section>

                  <section class="binding-panel" id="combinedBindingPanel" hidden><div class="section-label"><span><i class="bi bi-journal" aria-hidden="true"></i><strong>اختر التغليف</strong></span><small>الخيار غير المتاح يوضح سببه.</small></div><div class="binding-grid" id="bindingGrid"></div></section>

                  <section class="binding-panel" id="separateBindingPanel" hidden><div class="section-label"><span><i class="bi bi-files" aria-hidden="true"></i><strong>تغليف كل ملف</strong></span><small>يمكن أن يختلف التغليف من ملف لآخر.</small></div><div class="separate-binding-list" id="separateBindingList"></div></section>

                  <section class="quantity-panel" id="orderQuantityPanel"><div><span class="quantity-icon"><i class="bi bi-copy" aria-hidden="true"></i></span><span><strong>كمية الطلب</strong><small>تطبق الكمية على الطلب الكامل</small></span></div><div class="quantity-control"><button type="button" id="decreaseQuantity" aria-label="تقليل الكمية"><i class="bi bi-dash-lg"></i></button><output id="quantityOutput">1</output><button type="button" id="increaseQuantity" aria-label="زيادة الكمية"><i class="bi bi-plus-lg"></i></button></div></section>
                </div>

                <div class="stage-error" id="stageError3" role="alert" hidden></div>
                <div class="stage-actions"><button type="button" class="paper-button is-ghost" data-go-step="2"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>السابق</span></button><button type="button" class="paper-button is-primary" data-next-step="4"><span>متابعة إلى المراجعة</span><i class="bi bi-arrow-left" aria-hidden="true"></i></button></div>
              </div>
            </section>

            <section class="journey-stage" data-stage="4" aria-labelledby="stageTitle4">
              <button type="button" class="stage-mobile-header" data-stage-toggle="4" aria-expanded="false" disabled>
                <span class="stage-mobile-number">4</span><span><strong>المراجعة</strong><small id="stageSummary4">أكمل المرحلة السابقة أولًا</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i>
              </button>
              <div class="stage-body">
                <header class="stage-heading"><div><span class="stage-kicker">الخطوة 4 من 4</span><h2 id="stageTitle4">راجع طلبك قبل إضافته للسلة</h2><p>تأكد من الملفات والخصائص والتغليف. يمكنك تعديل أي مرحلة دون فقدان اختياراتك.</p></div><span class="stage-heading-icon is-green" aria-hidden="true"><i class="bi bi-check2-circle"></i></span></header>
                <div class="review-list" id="reviewList"></div>
                <div class="review-total-card"><div><span>إجمالي الطباعة</span><small>يُحسب التوصيل في السلة</small></div><strong id="reviewTotal">—</strong></div>
                <div class="stage-actions"><button type="button" class="paper-button is-ghost" data-go-step="3"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>السابق</span></button><button type="button" class="paper-button is-primary is-cart" id="addToCartButton"><i class="bi bi-cart-plus" aria-hidden="true"></i><span>إضافة إلى السلة</span></button></div>
              </div>
            </section>
          </div>

          <aside class="order-summary" aria-labelledby="summaryTitle">
            <div class="summary-heading"><div><span class="summary-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span><span><small>طلبك</small><h2 id="summaryTitle">ملخص الطباعة</h2></span></div></div>
            <div class="summary-progress"><span id="summaryProgressBar"></span></div>
            <div class="summary-current"><span>المرحلة الحالية</span><strong id="summaryCurrentStep">الملفات</strong></div>
            <dl class="summary-facts">
              <div><dt><i class="bi bi-files" aria-hidden="true"></i><span>الملفات</span></dt><dd id="summaryFiles">—</dd></div>
              <div><dt><i class="bi bi-printer" aria-hidden="true"></i><span>الطباعة</span></dt><dd id="summaryPrint">—</dd></div>
              <div><dt><i class="bi bi-journal-bookmark" aria-hidden="true"></i><span>التغليف</span></dt><dd id="summaryBinding">—</dd></div>
              <div><dt><i class="bi bi-copy" aria-hidden="true"></i><span>الكمية</span></dt><dd id="summaryQuantity">1</dd></div>
            </dl>
            <details class="price-breakdown"><summary><span>تفصيل السعر</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary><div id="priceBreakdown"></div></details>
            <div class="summary-total"><span><small>إجمالي الطباعة</small><strong id="summaryTotal">—</strong></span><small>لا يشمل التوصيل</small></div>
            <button type="button" class="paper-button is-primary summary-cta" id="summaryCta"><span>متابعة</span><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
            <p class="summary-help"><i class="bi bi-headset" aria-hidden="true"></i><span>تحتاج مساعدة؟ تواصل مع الدعم الفني</span></p>
          </aside>
        </div>

    <div class="mobile-order-bar" id="mobileOrderBar">
        <button type="button" class="mobile-total" id="openMobileSummary"><span>إجمالي الطباعة</span><strong id="mobileTotal">—</strong></button>
        <button type="button" class="paper-button is-primary" id="mobileCta"><span>متابعة</span><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
    </div>

    <dialog class="paper-dialog preview-dialog" id="previewDialog">
        <div class="dialog-header"><div><span class="dialog-icon"><i class="bi bi-eye" aria-hidden="true"></i></span><span><small>معاينة الملف</small><h2 id="previewTitle"></h2></span></div><button type="button" class="dialog-close" data-close-dialog="previewDialog" aria-label="إغلاق"><i class="bi bi-x-lg"></i></button></div>
        <div class="preview-content"><img id="previewImage" alt="" hidden><iframe id="previewFrame" title="معاينة PDF" hidden></iframe><div class="preview-placeholder" id="previewPlaceholder"><i class="bi bi-file-earmark-text" aria-hidden="true"></i><h3>المعاينة المرئية غير متاحة لهذا النوع</h3><p id="previewMeta"></p></div></div>
    </dialog>

    <dialog class="paper-dialog summary-dialog" id="mobileSummaryDialog">
        <div class="dialog-header"><div><span class="dialog-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span><h2>ملخص الطباعة</h2></div><button type="button" class="dialog-close" data-close-dialog="mobileSummaryDialog" aria-label="إغلاق"><i class="bi bi-x-lg"></i></button></div>
        <div class="mobile-summary-content" id="mobileSummaryContent"></div>
    </dialog>

    <div class="paper-toast" id="paperToast" role="status" aria-live="polite"><span class="paper-toast-icon"><i class="bi bi-check2"></i></span><span id="paperToastMessage"></span></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/paperPrinting.js') }}?v={{ filemtime(public_path('front/js/customer/paperPrinting.js')) }}"></script>
@endpush
