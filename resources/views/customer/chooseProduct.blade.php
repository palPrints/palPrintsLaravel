{{--
    Product picker for the customer's own uploaded design.
    Copy of designer/designs/create.blade.php inside the customer layout.
--}}
@extends('customer.layouts.app')

@section('title', 'اختر المنتج')
@section('body-class', 'storefront-page designer-create-flow customer-choose-product-page')

@push('styles')
    <base href='{{ asset('front/designer/source/create') }}/'>
    <link rel='stylesheet' href='{{ asset('front/designer/source/create/assets/css/choose-product.css') }}'>
    <style>body.storefront-page .source-create-main { width: auto; min-width: 0; }</style>
@endpush

@section('content')
<div class='source-create-main'>
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
    </div>
</div>
@endsection

@push('scripts')
    <script>
        window.palPrintsCreateRoutes = {
            studio: @json(route('design-studio')),
            store: @json(route('customer.store'))
        };
        window.palPrintsDesignerCatalogResponse = @json($designerCatalog);
    </script>
    <script src='{{ asset('front/js/customer/chooseProduct.js') }}?v={{ filemtime(public_path('front/js/customer/chooseProduct.js')) }}'></script>
@endpush
