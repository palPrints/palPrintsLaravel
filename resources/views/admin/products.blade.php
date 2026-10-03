@extends('admin.layouts.app')

@section('title', 'إدارة المنتجات')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminEarningsPayments.css').'?v='.filemtime(public_path('front/css/admin/adminEarningsPayments.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminProducts.css').'?v='.filemtime(public_path('front/css/admin/adminProducts.css')) }}">
@endpush

@php
    $activeCount = $products->where('active', true)->count();
@endphp

@section('content')
<main id="adminMain">
    <section class="products-page" aria-labelledby="productsTitle">
        @include('admin.partials.breadcrumb', ['label' => 'إدارة المنتجات'])
        <header class="products-header">
            <div>
                <h1 id="productsTitle">إدارة المنتجات</h1>
                <p>المنتجات التي يمكن للعملاء الطباعة عليها في الموقع</p>
            </div>
            <button class="primary-button" id="addProductButton" type="button">
                <i class="bi bi-plus-lg" aria-hidden="true"></i><span>إضافة منتج</span>
            </button>
        </header>

        <div class="products-summary" role="group" aria-label="فلترة المنتجات حسب الحالة">
            <button class="summary-pill" type="button" data-product-filter="all" aria-pressed="true"><b id="totalProducts">{{ $products->count() }}</b> إجمالي المنتجات</button>
            <button class="summary-pill" type="button" data-product-filter="active" aria-pressed="false"><b id="activeProducts">{{ $activeCount }}</b> متاح للطباعة</button>
            <button class="summary-pill" type="button" data-product-filter="paused" aria-pressed="false"><b id="pausedProducts">{{ $products->count() - $activeCount }}</b> موقوف مؤقتًا</button>
        </div>

        <div class="products-grid" id="productsGrid">
            @foreach ($products as $product)
                <article @class(['product-card', 'is-paused' => ! $product['active']])
                    data-product-id="{{ $product['code'] }}"
                    data-product-name="{{ $product['name'] }}"
                    data-category-id="{{ $product['categoryId'] }}"
                    data-description="{{ $product['description'] }}"
                    data-image="{{ $product['image'] }}"
                    data-status="{{ $product['active'] ? 'active' : 'paused' }}"
                    data-update-url="{{ route('admin.products.update', $product['id']) }}"
                    data-toggle-url="{{ route('admin.products.toggle', $product['id']) }}"
                    data-delete-url="{{ route('admin.products.destroy', $product['id']) }}">
                    <div class="product-visual">
                        @unless ($product['active'])<span class="paused-label">موقوف</span>@endunless
                        @if ($product['image'])
                            <img class="product-image" src="{{ $product['image'] }}" alt="صورة {{ $product['name'] }}" loading="lazy">
                        @endif
                    </div>
                    <div class="product-info">
                        <h3>{{ $product['name'] }}</h3>
                        <p>{{ $product['category'] ?? 'بدون فئة' }}</p>
                        @if ($product['description'])
                            <p class="product-description">{{ $product['description'] }}</p>
                        @endif
                    </div>
                    <div class="product-actions">
                        <button class="{{ $product['active'] ? 'danger-action' : 'success-action' }}" type="button" data-action="toggle"><i class="bi {{ $product['active'] ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>{{ $product['active'] ? 'إيقاف' : 'تفعيل' }}</button>
                        <button class="edit-action" type="button" data-action="edit"><i class="bi bi-pencil-square"></i>تعديل</button>
                        <button class="delete-action" type="button" data-action="delete" aria-label="حذف {{ $product['name'] }}"><i class="bi bi-trash3"></i></button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="empty-state" id="emptyState" @if ($products->isNotEmpty()) hidden @endif>
            <i class="bi bi-box-seam"></i>
            <h2 id="emptyStateTitle">{{ $products->isEmpty() ? 'لا توجد منتجات بعد' : 'لا توجد نتائج مطابقة' }}</h2>
            <p id="emptyStateText">{{ $products->isEmpty() ? 'أضف أول منتج ليظهر للعملاء.' : 'جرّب البحث باسم منتج أو رقمه.' }}</p>
        </div>
    </section>
</main>

<dialog class="product-dialog" id="productDialog" aria-labelledby="productDialogTitle">
    <form class="product-form" id="productForm" method="dialog" data-store-url="{{ route('admin.products.store') }}" novalidate>
        <header>
            <div>
                <span class="dialog-icon"><i class="bi bi-box-seam"></i></span>
                <div>
                    <h2 id="productDialogTitle">إضافة منتج</h2>
                    <p>أدخل بيانات المنتج المتاح للطباعة</p>
                </div>
            </div>
            <button class="dialog-close" type="button" data-dialog-close aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
        </header>
        <div class="form-body">
            <div class="product-image-field">
                <span class="field-label">صورة المنتج</span>
                <label class="image-upload" id="productImageUpload" for="productImageInput">
                    <input id="productImageInput" name="image" type="file" accept="image/png,image/jpeg,image/webp">
                    <span class="image-preview" id="productImagePreview" aria-hidden="true">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <img id="productImagePreviewImg" alt="معاينة صورة المنتج" hidden>
                    </span>
                    <span class="upload-copy">
                        <strong id="productImageUploadTitle">اختر صورة للمنتج</strong>
                        <small id="productImageHelp">PNG أو JPG أو WEBP، بحد أقصى 5MB</small>
                    </span>
                    <span class="upload-button"><i class="bi bi-image"></i><span>اختيار صورة</span></span>
                </label>
                <p class="image-upload-error" id="productImageError" role="alert" hidden></p>
            </div>
            <label><span>اسم المنتج</span><input id="productNameInput" name="name" type="text" required maxlength="255" placeholder="مثال: قميص رياضي"></label>
            <label><span>رقم المنتج</span><input id="productCodeInput" name="code" type="text" required maxlength="50" dir="ltr" placeholder="PPR-009"></label>
            <label><span>الفئة</span>
                <select id="productCategoryInput" name="category_id" required>
                    <option value="">اختر الفئة</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <label><span>الوصف</span><textarea id="productDescriptionInput" name="description" rows="3" maxlength="1000" placeholder="وصف مختصر للمنتج"></textarea></label>
        </div>
        <footer>
            <button class="secondary-button" type="button" data-dialog-close>إلغاء</button>
            <button class="primary-button" type="submit">حفظ المنتج</button>
        </footer>
    </form>
</dialog>
<dialog class="product-dialog confirm-dialog" id="deleteDialog" aria-labelledby="deleteDialogTitle">
    <div class="confirm-body">
        <span class="confirm-icon"><i class="bi bi-trash3"></i></span>
        <h2 id="deleteDialogTitle">حذف المنتج</h2>
        <p>هل تريد حذف <strong id="deleteDialogName"></strong>؟ لا يمكن التراجع عن هذا الإجراء.</p>
    </div>
    <footer class="confirm-actions">
        <button class="secondary-button" type="button" id="deleteCancel">إلغاء</button>
        <button class="danger-button" type="button" id="deleteConfirm">نعم، احذف</button>
    </footer>
</dialog>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminProducts.js').'?v='.filemtime(public_path('front/js/admin/adminProducts.js')) }}"></script>
@endpush
