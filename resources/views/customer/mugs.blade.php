{{--
    Ported from palPrintFront/mugs.html.
    The 8 mug designs (title/description/designer/price/image) are still
    hardcoded in mugs.js — same "demo data" situation the store page had
    before it was wired to the real products table. Needs the same DB wiring
    later. The "معاينة المنتج" button also links to a product-preview page
    that doesn't exist yet anywhere in the front-end repo.
--}}
@extends('customer.layouts.app')

@section('title', 'أكواب')
@section('meta-description', 'تصفح تصاميم الأكواب المطبوعة المميزة من PalPrints واختر التصميم الذي يناسب ذوقك')
@section('body-class', 'storefront-page mugs-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/mugs.css') }}?v={{ filemtime(public_path('front/css/customer/mugs.css')) }}">
@endpush

@section('content')
    <script>document.body.classList.add("mugs-motion-ready");</script>

    <div class="container-palprints">
        <div class="catalog-topline">
            <nav class="catalog-breadcrumb" aria-label="مسار التنقل">
                <a href="{{ route('home') }}">الرئيسية</a>
                <span>/</span>
                <a href="{{ route('customer.store') }}#products">المنتجات</a>
                <span>/</span>
                <span aria-current="page">أكواب</span>
            </nav>
            <a class="catalog-back" href="{{ route('customer.store') }}#products">
                العودة إلى المنتجات <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </div>

        <div class="catalog-heading">
            <h1><img src="{{ asset('front/assets/images/customer/cup.webp') }}" alt=""> أكواب</h1>
            <p>اكتشف مجموعة مميزة من الأكواب المطبوعة بتصاميم تحوّل لحظاتك اليومية إلى تفاصيل أكثر دفئًا وإبداعًا.</p>
        </div>

        <div class="products-grid" id="productGrid" aria-live="polite"></div>
        <p class="products-empty" id="productsEmpty" role="status" hidden>لا توجد تصاميم مطابقة لبحثك. جرّب كلمة أخرى.</p>

        <nav class="catalog-pagination" aria-label="صفحات المنتجات">
            <button disabled aria-label="الصفحة السابقة"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            <button aria-current="page" aria-label="الصفحة 1">1</button>
            <button disabled aria-label="الصفحة التالية"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
        </nav>
        <p class="catalog-count" id="catalogCount" role="status" aria-live="polite"></p>

        <noscript><p class="products-empty">يرجى تفعيل JavaScript لعرض تصاميم الأكواب.</p></noscript>
    </div>
@endsection

@push('scripts')
    <script>
        window.palPrintsCustomerAssets = window.palPrintsCustomerAssets || {};
        window.palPrintsCustomerAssets.mugsImagesBase = @json(asset('front/assets/images/customer/mugs'));
        window.palPrintsCustomerAssets.cupFallbackImage = @json(asset('front/assets/images/customer/cup.webp'));
    </script>
    <script src="{{ asset('front/js/customer/mugs.js') }}?v={{ filemtime(public_path('front/js/customer/mugs.js')) }}"></script>
@endpush
