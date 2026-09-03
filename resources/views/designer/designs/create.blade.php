@extends('designer.layouts.app')

@section('title', 'اختر المنتج')
@section('main-class', 'source-create-main')

@push('styles')
    <base href='{{ asset('front/designer/source/create') }}/'>
    <link rel='stylesheet' href='{{ asset('front/designer/source/create/assets/css/choose-product.css') }}'>
@endpush

@section('content')
    <div class='source-product-picker page'>

        <section class="product-page" aria-labelledby="page-title">

            <!-- =================================================
                 HEADER
            ================================================== -->

            <header class="page-header">

                <h1 id="page-title">
                    اختر المنتج
                </h1>


                <button type="button" class="back-button" id="backButton" aria-label="العودة إلى الصفحة السابقة">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </button>

            </header>


            <!-- =================================================
                 MAIN CONTENT
            ================================================== -->

            <div class="page-content">


                <!-- =================================================
                     PRODUCTS SECTION
                ================================================== -->

                <section class="products-section" aria-labelledby="products-heading">

                    <h2 id="products-heading" class="sr-only">
                        المنتجات
                    </h2>


                    <!-- =============================================
                         CATEGORIES
                    ============================================== -->

                    <nav class="categories" id="categoriesContainer" aria-label="تصنيفات المنتجات">
                        <!--
                            Categories are generated dynamically
                            using JavaScript.
                        -->
                    </nav>


                    <!-- =============================================
                         PRODUCTS GRID
                    ============================================== -->

                    <div class="products-grid" id="productsGrid" aria-live="polite">
                        <!--
                            Products are generated dynamically.
                        -->
                    </div>

                </section>



                <!-- =================================================
                     PRODUCT CONFIGURATION
                ================================================== -->

                <aside class="configuration-panel" aria-labelledby="selected-product-title" hidden>

                    <!-- =============================================
                         PRODUCT INFORMATION
                    ============================================== -->

                    <div class="selected-product-header">

                        <h2 id="selected-product-title">
                            اختر منتجًا
                        </h2>

                        <p id="selected-product-description">
                            اختر منتجًا من القائمة لعرض تفاصيله.
                        </p>

                    </div>


                    <!-- =============================================
                         PRODUCT PREVIEW
                    ============================================== -->

                    <div class="product-preview" id="productPreview" aria-live="polite">

                        <img id="previewImage" src="" alt="">

                        <div class="preview-placeholder" id="previewPlaceholder">
                            <span aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                            <span>اختر منتجًا لعرض المعاينة</span>
                        </div>


                    </div>



                    <!-- =============================================
                         PRODUCT OPTIONS
                    ============================================== -->

                    <div id="productOptions" class="product-options" hidden>


                        <!-- =========================================
                             COLORS
                        ========================================== -->

                        <fieldset class="option-group">

                            <legend>
                                اختر اللون
                            </legend>

                            <div id="colorsContainer" class="colors-container" role="radiogroup"
                                aria-label="ألوان المنتج">
                                <!-- Dynamic -->
                            </div>

                        </fieldset>



                        <!-- =========================================
                             SIZES
                        ========================================== -->

                        <fieldset class="option-group">

                            <legend>
                                اختر المقاس
                            </legend>

                            <div id="sizesContainer" class="sizes-container" role="radiogroup"
                                aria-label="مقاسات المنتج">
                                <!-- Dynamic -->
                            </div>

                        </fieldset>



                        <!-- =========================================
                             PRINTING AREAS
                        ========================================== -->

                        <fieldset class="option-group">

                            <legend>
                                مناطق الطباعة المدعومة
                            </legend>

                            <div id="printAreasContainer" class="print-areas-container">
                                <!-- Dynamic -->
                            </div>

                        </fieldset>


                    </div>



                    <!-- =============================================
                         VALIDATION MESSAGE
                    ============================================== -->

                    <p id="selectionMessage" class="selection-message" role="alert" aria-live="assertive"></p>



                    <!-- =============================================
                         START DESIGN BUTTON
                    ============================================== -->

                    <button type="button" id="startDesignButton" class="start-design-button" disabled>

                        <i class="button-icon bi bi-pencil-square" aria-hidden="true"></i>

                        <span>
                            ابدأ التصميم
                        </span>

                    </button>

                </aside>

            </div>

        </section>

        <div class="sr-only" id="liveRegion" role="status" aria-live="polite" aria-atomic="true"></div>

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
    <script src='{{ asset('front/designer/source/create/assets/js/choose-product.js') }}'></script>
@endpush
