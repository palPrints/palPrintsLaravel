@extends('designer.layouts.app')

@section('title', 'تفاصيل النشر')
@section('body-class', 'designer-create-flow')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/designer/css/designerFlow.css') }}?v={{ filemtime(public_path('front/designer/css/designerFlow.css')) }}">
@endpush

@section('content')
<main class="designer-content-main" id="designerMain">
    <div class="designer-publish">
        <header class="dp-header">
            <div class="dp-heading">
                <span class="dp-heading__icon" aria-hidden="true"><i class="bi bi-rocket-takeoff"></i></span>
                <div>
                    <h1>تفاصيل النشر</h1>
                    <p>اكتب اسم تصميمك وسعره، ثم أرسله للمراجعة</p>
                </div>
            </div>
            <a class="designer-flow-button is-ghost" id="backToPreview" href="{{ route('designer.designs.preview') }}">
                <i class="bi bi-arrow-right" aria-hidden="true"></i><span>رجوع للمعاينة</span>
            </a>
        </header>

        <section class="dp-empty" id="publishMissing" hidden>
            <h2>لا يوجد تصميم جاهز للنشر</h2>
            <p>ابدأ تصميمك في الاستوديو، ثم عاينه وتابع من هناك.</p>
            <a class="designer-flow-button is-primary" href="{{ route('design-studio') }}"><span>الذهاب إلى الاستوديو</span></a>
        </section>

        <div class="dp-layout" id="publishLayout" hidden>
            <section class="dp-card" aria-labelledby="previewTitle">
                <div class="dp-card__head">
                    <span aria-hidden="true"><i class="bi bi-eye"></i></span>
                    <div>
                        <h2 id="previewTitle">هكذا سيظهر تصميمك في المعرض</h2>
                        <p>بعد موافقة الإدارة على التصميم</p>
                    </div>
                </div>

                <div class="dp-stage">
                    <img id="publishPreview" alt="معاينة التصميم على المنتج">
                </div>

                <dl class="dp-facts">
                    <div>
                        <dt><i class="bi bi-tag" aria-hidden="true"></i> نوع المنتج</dt>
                        <dd id="productName"></dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-people" aria-hidden="true"></i> الفئة</dt>
                        <dd id="audienceName">—</dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-palette" aria-hidden="true"></i> لون المعاينة</dt>
                        <dd id="colorName"></dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-check2-square" aria-hidden="true"></i> الألوان المناسبة</dt>
                        <dd id="allowedColors"></dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-rulers" aria-hidden="true"></i> المقاسات المناسبة</dt>
                        <dd id="allowedSizes">—</dd>
                    </div>
                </dl>
            </section>

            <aside class="dp-card" aria-labelledby="publishTitle">
                <div class="dp-card__head">
                    <span aria-hidden="true"><i class="bi bi-sliders"></i></span>
                    <div>
                        <h2 id="publishTitle">بيانات التصميم</h2>
                        <p>املأ الحقول المطلوبة (*) ليُفعَّل زر الإرسال</p>
                    </div>
                </div>

                <label class="dp-field" for="designName">
                    <span>اسم التصميم <b aria-hidden="true">*</b></span>
                    <input class="dp-input" id="designName" maxlength="100" autocomplete="off" placeholder="اكتب اسمًا واضحًا لتصميمك">
                    <small><span id="nameCount">0</span>/100</small>
                </label>

                <section class="dp-price" aria-labelledby="priceTitle">
                    <div class="dp-price__title">
                        <h3 id="priceTitle">سعر التصميم</h3>
                    </div>
                    <label class="dp-row dp-row--sell" for="sellingPrice">
                        <span>سعر تصميمك <b aria-hidden="true" style="color:var(--df-danger)">*</b></span>
                        <span class="dp-money"><input id="sellingPrice" type="text" dir="ltr" lang="en" inputmode="decimal" autocomplete="off" placeholder="0.00"><small>₪</small></span>
                    </label>
                    <div class="dp-row dp-row--profit"><span><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> ربحك من كل قطعة</span><strong><bdi id="profitValue">0.00</bdi> ₪</strong></div>
                    <p class="dp-hint">بعد خصم عمولة المنصة (<bdi id="commissionRate">0</bdi>%). تكلفة المنتج تُضاف على العميل تلقائيًا.</p>
                    <p class="dp-error" id="priceError" role="alert"></p>
                </section>

                <label class="dp-rights" for="rightsCheck">
                    <input id="rightsCheck" type="checkbox">
                    <span><strong>أؤكد أنني أملك حق استخدام هذا التصميم</strong><small>أتحمل مسؤولية المحتوى وحقوق الملكية الفكرية.</small></span>
                </label>

                <div class="dp-note" id="validationNote" role="status" aria-live="polite"></div>
            </aside>
        </div>

        <footer class="dp-actions" id="publishFooter" hidden>
            <a class="designer-flow-button is-ghost" href="{{ route('designer.designs.preview') }}"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>رجوع للمعاينة</span></a>
            <button class="designer-flow-button is-ghost" id="saveButton" type="button"><i class="bi bi-bookmark" aria-hidden="true"></i><span>حفظ كمسودة</span></button>
            <button class="designer-flow-button is-primary" id="publishButton" type="button"><i class="bi bi-send" aria-hidden="true"></i><span>إرسال للمراجعة</span></button>
        </footer>
    </div>

    <div class="dp-toast" id="toast" role="status" aria-live="polite"></div>
    <div class="dp-dialog" id="successDialog" role="dialog" aria-modal="true" aria-labelledby="successTitle" hidden>
        <div class="dp-dialog__card">
            <span class="dp-dialog__mark" aria-hidden="true"><i class="bi bi-check2"></i></span>
            <h2 id="successTitle">تم إرسال التصميم للمراجعة</h2>
            <p>وصل تصميمك إلى فريق الإدارة، وسيظهر في المعرض بعد الموافقة عليه.</p>
            <button class="designer-flow-button is-primary" id="closeDialog" type="button"><span>حسنًا</span></button>
        </div>
    </div>
</main>
@endsection

@push('scripts')
    <script>
        window.palPrintsPublish = {
            basePrices: @json($basePrices),
            commissionRate: @json($commissionRate),
            audiences: @json(\App\Support\CatalogProductData::AUDIENCES),
            saveUrl: @json(route('designer.designs.store')),
            designsUrl: @json(route('designer.designs.index'))
        };
    </script>
    <script src="{{ asset('front/designer/js/designerPublish.js') }}?v={{ filemtime(public_path('front/designer/js/designerPublish.js')) }}"></script>
@endpush
