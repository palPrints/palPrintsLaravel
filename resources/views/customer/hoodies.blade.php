{{--
    Ported from palPrintFront/hoodies.html.
    The 8 hoodie designs (title/description/designer/price/tone) are still
    hardcoded in hoodies.js — same "demo data" situation the store page had
    before it was wired to the real products table. Needs the same DB wiring
    later. The "معاينة المنتج" button also links to a product-preview page
    that doesn't exist yet anywhere in the front-end repo.
--}}
@extends('customer.layouts.app')

@section('title', 'هودي')
@section('meta-description', 'تصفح تصاميم الهودي للأطفال والرجال والنساء والأوفر سايز من PalPrints')
@section('body-class', 'storefront-page hoodies-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/hoodies.css') }}?v={{ filemtime(public_path('front/css/customer/hoodies.css')) }}">
@endpush

@section('content')
    <script>document.body.classList.add("hoodies-motion-ready");</script>

    <div class="container-palprints">
        <div class="catalog-topline">
            <nav class="catalog-breadcrumb" aria-label="مسار التنقل">
                <a href="{{ route('home') }}">الرئيسية</a>
                <span>/</span>
                <a href="{{ route('customer.store') }}#products">المنتجات</a>
                <span>/</span>
                <span aria-current="page">هودي</span>
            </nav>
            <a class="catalog-back" href="{{ route('customer.store') }}#products">
                العودة إلى المنتجات <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </div>

        <div class="catalog-heading">
            <h1><img src="{{ asset('front/assets/images/customer/hoddieIcon.svg') }}" alt=""> هودي</h1>
            <p>اكتشف مجموعة مميزة من الهودي بتصاميم فريدة تناسب كل الأذواق والمناسبات.</p>
        </div>

        <div class="hoodie-filters" role="group" aria-label="تصنيف الهودي">
            <button type="button" data-filter="kids" aria-pressed="false"><i class="bi bi-emoji-smile" aria-hidden="true"></i>أطفال</button>
            <button type="button" data-filter="adults" aria-pressed="false"><i class="bi bi-people" aria-hidden="true"></i>رجال / نساء</button>
            <button type="button" data-filter="oversized" aria-pressed="false"><i class="bi bi-bag" aria-hidden="true"></i>أوفر سايز</button>
        </div>

        <div class="products-grid" id="productGrid"></div>
        <p class="products-empty" id="productsEmpty" role="status" hidden>لا توجد منتجات مطابقة لبحثك. جرّب كلمة أخرى أو ألغِ التصنيف المحدد.</p>

        <nav class="catalog-pagination" aria-label="صفحات المنتجات">
            <button disabled aria-label="الصفحة السابقة"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            <button aria-current="page" aria-label="الصفحة 1">1</button>
            <button disabled aria-label="الصفحة التالية"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
        </nav>
        <p class="catalog-count" id="catalogCount" role="status" aria-live="polite"></p>

        <noscript><p class="products-empty">يرجى تفعيل JavaScript لعرض منتجات الهودي.</p></noscript>
    </div>
@endsection

@push('scripts')
    <script>
        window.palPrintsCustomerAssets = window.palPrintsCustomerAssets || {};
        window.palPrintsCustomerAssets.hoodieImage = @json(asset('front/assets/images/customer/hoodie.png'));
    </script>
    <script src="{{ asset('front/js/customer/hoodies.js') }}?v={{ filemtime(public_path('front/js/customer/hoodies.js')) }}"></script>
@endpush
