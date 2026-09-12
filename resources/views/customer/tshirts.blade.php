{{--
    Ported from palPrintFront/tshirts.html.
    The 6 t-shirt designs (title/description/designer/price/image) are still
    hardcoded in tshirts.js — same "demo data" situation the store page had
    before it was wired to the real products table. Needs the same DB wiring
    later. The "معاينة المنتج" button also links to a product-preview page
    that doesn't exist yet anywhere in the front-end repo.
--}}
@extends('customer.layouts.app')

@section('title', 'تيشيرتات')
@section('meta-description', 'تصفح تصاميم التيشيرتات للأطفال والرجال والنساء والأوفر سايز من PalPrints')
@section('body-class', 'storefront-page tshirts-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/tshirts.css') }}?v={{ filemtime(public_path('front/css/customer/tshirts.css')) }}">
@endpush

@section('content')
    <script>document.body.classList.add("tshirts-motion-ready");</script>

    <div class="container-palprints">
        <div class="catalog-topline">
            <nav class="catalog-breadcrumb" aria-label="مسار التنقل">
                <a href="{{ route('home') }}">الرئيسية</a>
                <span>/</span>
                <a href="{{ route('customer.store') }}#products">المنتجات</a>
                <span>/</span>
                <span aria-current="page">تيشيرتات</span>
            </nav>
            <a class="catalog-back" href="{{ route('customer.store') }}#products">
                العودة إلى المنتجات <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </div>

        <div class="catalog-heading">
            <h1><img src="{{ asset('front/assets/images/customer/t-shirt.png') }}" alt=""> تيشيرتات</h1>
            <p>اكتشف مجموعة مميزة من التيشيرتات بتصاميم فريدة تناسب كل الأذواق والمناسبات.</p>
        </div>

        <div class="tshirt-filters" role="group" aria-label="تصنيف التيشيرتات">
            <button type="button" data-filter="kids" aria-pressed="false"><i class="bi bi-emoji-smile" aria-hidden="true"></i>أطفال</button>
            <button type="button" data-filter="adults" aria-pressed="false"><i class="bi bi-people" aria-hidden="true"></i>رجال / نساء</button>
            <button type="button" data-filter="oversized" aria-pressed="false"><i class="bi bi-bag" aria-hidden="true"></i>أوفر سايز</button>
        </div>

        <div class="products-grid" id="productGrid" aria-live="polite"></div>
        <p class="products-empty" id="productsEmpty" role="status" hidden>لا توجد منتجات مطابقة لبحثك. جرّب كلمة أخرى أو ألغِ التصنيف المحدد.</p>

        <nav class="catalog-pagination" aria-label="صفحات المنتجات">
            <button disabled aria-label="الصفحة السابقة"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            <button aria-current="page" aria-label="الصفحة 1">1</button>
            <button disabled aria-label="الصفحة التالية"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
        </nav>
        <p class="catalog-count" id="catalogCount" role="status" aria-live="polite"></p>

        <noscript><p class="products-empty">يرجى تفعيل JavaScript لعرض منتجات التيشيرتات.</p></noscript>
    </div>
@endsection

@push('scripts')
    <script>
        window.palPrintsCustomerAssets = window.palPrintsCustomerAssets || {};
        window.palPrintsCustomerAssets.tshirtsImagesBase = @json(asset('front/assets/images/customer/tshirts'));
        window.palPrintsCustomerAssets.tshirtFallbackImage = @json(asset('front/assets/images/customer/tshirt.webp'));
    </script>
    <script src="{{ asset('front/js/customer/tshirts.js') }}?v={{ filemtime(public_path('front/js/customer/tshirts.js')) }}"></script>
@endpush
