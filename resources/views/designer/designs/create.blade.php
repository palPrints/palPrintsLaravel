@extends('designer.layouts.app')

@section('title', 'اختر المنتج')
@section('body-class', 'designer-create-flow')

@push('styles')
    <base href='{{ asset('front/designer/source/create') }}/'>
    <link rel='stylesheet' href='{{ asset('front/designer/source/create/assets/css/choose-product.css') }}'>
    <link rel='stylesheet' href='{{ asset('front/designer/css/designerFlow.css') }}?v={{ filemtime(public_path('front/designer/css/designerFlow.css')) }}'>
@endpush

@section('content')
<main class='designer-content-main source-create-main' id='designerMain'>
    <div class='source-product-picker page'>
        <section class="product-page" aria-labelledby="page-title">
            <header class="page-header">
                <h1 id="page-title">اختر المنتج</h1>

            </header>

            <div class="page-content">
                <section class="products-section" aria-labelledby="products-heading">
                    <h2 id="products-heading" class="sr-only">المنتجات</h2>


                    <div class="products-grid" id="productsGrid" aria-live="polite"></div>
                </section>

                <aside class="configuration-panel" aria-labelledby="selected-product-title" hidden>
                    <div class="selected-product-header">
                        <h2 id="selected-product-title">اختر منتجا</h2>

                        <p id="selected-product-description">
                            اختر منتجا من القائمة لعرض تفاصيله.
                        </p>
                    </div>

                    <div class="product-preview" id="productPreview" aria-live="polite">
                        <img id="previewImage" src="" alt="">

                        <div class="preview-placeholder" id="previewPlaceholder">
                            <span aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                            <span>اختر منتجا لعرض المعاينة</span>
                        </div>
                    </div>

                    <div id="productOptions" class="product-options" hidden>
                        <fieldset class="option-group">
                            <legend>اختر اللون</legend>

                            <div id="colorsContainer" class="colors-container" role="radiogroup"
                                aria-label="ألوان المنتج"></div>
                        </fieldset>

                        <fieldset class="option-group">
                            <legend>اختر المقاس</legend>

                            <div id="sizesContainer" class="sizes-container" role="radiogroup"
                                aria-label="مقاسات المنتج"></div>
                        </fieldset>

                        <fieldset class="option-group">
                            <legend>مناطق الطباعة المدعومة</legend>

                            <div id="printAreasContainer" class="print-areas-container"></div>
                        </fieldset>
                    </div>

                    <p id="selectionMessage" class="selection-message" role="alert" aria-live="assertive"></p>

                    <button type="button" id="startDesignButton" class="start-design-button" disabled>
                        <i class="button-icon bi bi-pencil-square" aria-hidden="true"></i>
                        <span>ابدأ التصميم</span>
                    </button>
                </aside>
            </div>
        </section>

        <div class="sr-only" id="liveRegion" role="status" aria-live="polite" aria-atomic="true"></div>

        {{-- Clothing is sold by audience: the designer says who the design is for before opening the studio. --}}
        <dialog class="flow-dialog" id="audienceDialog" aria-labelledby="audienceTitle">
            <div class="flow-dialog__head">
                <h2 id="audienceTitle">لمن هذا التصميم؟</h2>
                <p>اختر الفئة المناسبة، وبناءً عليها تظهر المقاسات المتاحة للعملاء.</p>
            </div>
            <div class="flow-dialog__options">
                @foreach(\App\Support\CatalogProductData::AUDIENCES as $key => $audience)
                    <button type="button" class="audience-option" data-audience="{{ $key }}">
                        <span class="audience-option__icon" aria-hidden="true"><i class="bi {{ ['adults' => 'bi-people', 'oversized' => 'bi-bag', 'kids' => 'bi-emoji-smile'][$key] ?? 'bi-tag' }}"></i></span>
                        <span class="audience-option__text"><strong>{{ $audience['label'] }}</strong><small>{{ $audience['hint'] }}</small></span>
                        <i class="bi bi-chevron-left audience-option__go" aria-hidden="true"></i>
                    </button>
                @endforeach
            </div>
            <div class="flow-dialog__foot">
                <button type="button" class="flow-dialog__cancel" id="audienceCancel">إلغاء</button>
            </div>
        </dialog>
    </div>
</main>
@endsection

@push('scripts')
    <script>
        window.palPrintsCreateRoutes = {
            choose: @json(route('designer.designs.create')),
            editor: @json(route('design-studio')),
            review: @json(route('designer.designs.review')),
            dashboard: @json(route('designer.dashboard'))
        };
        window.palPrintsDesignerCatalogResponse = @json($designerCatalog);
        window.palPrintsApparelCodes = @json(\App\Support\CatalogProductData::APPAREL_CODES);
    </script>
    <script src='{{ asset('front/designer/source/create/assets/js/choose-product.js') }}'></script>
@endpush
