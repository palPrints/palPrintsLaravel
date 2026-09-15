{{--
    Ported from palPrintFront/emptyBasket.html.
    Shown once the basket has no items — orderBasket.js's remove-item flow
    redirects here (see customer.basket / public/front/js/customer/orderBasket.js).
    Once there's a real cart, this and customer.basket should probably become
    one route whose view depends on whether the cart actually has items.
--}}
@extends('customer.layouts.app')

@section('title', 'السلة فارغة')
@section('meta-description', 'سلة التسوق الفارغة في متجر PalPrints')
@section('body-class', 'storefront-page order-basket-page empty-basket-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/orderBasket.css') }}?v={{ filemtime(public_path('front/css/customer/orderBasket.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/customer/emptyBasket.css') }}?v={{ filemtime(public_path('front/css/customer/emptyBasket.css')) }}">
@endpush

@section('content')
    <div class="empty-basket-top">
        <nav class="empty-breadcrumbs" aria-label="مسار التنقل">
            <a href="{{ route('home') }}">الرئيسية</a>
            <span>/</span>
            <a href="{{ route('customer.basket') }}" id="basketCrumbLink" aria-current="page">سلة التسوق</a>
        </nav>
        <h1><span class="title-icon"><i class="bi bi-cart3" aria-hidden="true"></i></span>سلة التسوق</h1>
    </div>

    <section class="empty-hero" aria-labelledby="emptyTitle">
        <div class="empty-visual" aria-hidden="true">
            <svg class="empty-cart-illustration" viewBox="0 0 270 245" role="img">
                <defs>
                    <linearGradient id="basketBody" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#b9d5ff"/><stop offset=".48" stop-color="#8eb8ff"/><stop offset="1" stop-color="#719ce9"/></linearGradient>
                    <linearGradient id="basketRim" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#79a9fb"/><stop offset=".5" stop-color="#b9d6ff"/><stop offset="1" stop-color="#6f9be8"/></linearGradient>
                    <linearGradient id="metal" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#8db7fc"/><stop offset="1" stop-color="#527fd0"/></linearGradient>
                    <radialGradient id="wheel" cx="35%" cy="30%"><stop stop-color="#335886"/><stop offset="1" stop-color="#0b2347"/></radialGradient>
                    <filter id="softShadow" x="-30%" y="-30%" width="160%" height="180%"><feDropShadow dx="0" dy="8" stdDeviation="7" flood-color="#5889d1" flood-opacity=".28"/></filter>
                    <filter id="blurShadow"><feGaussianBlur stdDeviation="7"/></filter>
                </defs>
                <ellipse cx="132" cy="214" rx="76" ry="13" fill="#7da3d2" opacity=".2" filter="url(#blurShadow)"/>
                <g fill="none" stroke="#71b8ed" stroke-width="6" stroke-linecap="round"><path d="M43 46l-9-12"/><path d="M57 39l-3-16"/><path d="M29 59l-13-7"/><path d="M233 186l15 5"/></g>
                <g transform="translate(177 20) rotate(10 34 34)" filter="url(#softShadow)">
                    <rect x="3" y="3" width="66" height="66" rx="16" fill="#fff" stroke="#d9e6fb" stroke-width="2"/>
                    <path d="M36 48C25 40 18 34 18 26c0-7 9-11 14-5l4 5 4-5c6-6 15-2 15 5 0 8-7 14-19 22Z" fill="none" stroke="#397bf1" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <g transform="rotate(-5 130 137)" filter="url(#softShadow)">
                    <path d="M50 69c10-15 30-13 37 2l8 20" fill="none" stroke="#6699ed" stroke-width="11" stroke-linecap="round"/>
                    <path d="M55 67c9-8 19-6 24 2" fill="none" stroke="#a9caff" stroke-width="5" stroke-linecap="round"/>
                    <path d="M78 86 194 99l-15 76-86-9Z" fill="url(#basketBody)" stroke="#759fe9" stroke-width="5" stroke-linejoin="round"/>
                    <path d="M77 86 194 99" stroke="url(#basketRim)" stroke-width="13" stroke-linecap="round"/>
                    <path d="m93 103 10 58M119 106l4 58M148 109l-4 58M176 112l-10 58M88 126l96 10M91 147l89 9" fill="none" stroke="#d5e5ff" stroke-width="8" stroke-linecap="round" opacity=".9"/>
                    <path d="M91 165c5 17 17 23 34 23h45" fill="none" stroke="url(#metal)" stroke-width="8" stroke-linecap="round"/>
                    <path d="M169 187h13" stroke="#4e7bc7" stroke-width="7" stroke-linecap="round"/>
                    <g><circle cx="111" cy="198" r="14" fill="url(#wheel)"/><circle cx="111" cy="198" r="5" fill="#86adf1"/><circle cx="174" cy="198" r="14" fill="url(#wheel)"/><circle cx="174" cy="198" r="5" fill="#86adf1"/></g>
                    <path d="M85 95c30 5 73 9 103 10" stroke="#dceaff" stroke-width="4" stroke-linecap="round" opacity=".8"/>
                </g>
            </svg>
        </div>
        <h2 id="emptyTitle">سلتك فارغة حاليًا</h2>
        <p>ابدأ بإضافة المنتجات والتصاميم التي تعجبك<br>وصمّم إطلالتك المميزة مع PalPrints</p>
        <a class="browse-products pal-pressable" href="{{ route('customer.store') }}#products">تصفح المنتجات <i class="bi bi-arrow-left" aria-hidden="true"></i></a>
    </section>

    <section class="empty-suggestions" aria-labelledby="suggestionsTitle">
        <div class="suggestions-heading"><h2 id="suggestionsTitle">ماذا تنتظر؟</h2><p>اكتشف بعض المنتجات المميزة</p></div>
        <div class="suggestions-grid">
            <a href="{{ route('customer.tshirts') }}" class="suggestion-card suggestion-card--shirt">
                <span><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M17 7 7.5 11.5 3 21l8 4 3-5v21h20V20l3 5 8-4-4.5-9.5L31 7c-1.1 3.7-3.4 5.5-7 5.5S18.1 10.7 17 7Z"/><path d="M18 8c1.4 1.8 3.3 2.7 6 2.7S28.6 9.8 30 8"/></svg></span>
                <strong>تيشيرتات</strong><small>تصاميم تعبّر عنك</small>
            </a>
            <a href="{{ route('customer.hoodies') }}" class="suggestion-card suggestion-card--hoodie">
                <span><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M17 12 9 16 4 28l8 4 3-6v16h18V26l3 6 8-4-5-12-8-4"/><path d="M17 12c0-5 3-8 7-8s7 3 7 8c0 4-3 7-7 7s-7-3-7-7Z"/><path d="M19 18v6l5 3 5-3v-6M24 27v15M18 34h12"/></svg></span>
                <strong>هودي</strong><small>راحة وأناقة بطابعك الخاص</small>
            </a>
            <a href="{{ route('customer.mugs') }}" class="suggestion-card suggestion-card--mug">
                <span><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 16h26v17c0 6-4 9-10 9h-6c-6 0-10-3-10-9V16Z"/><path d="M35 21h3c5 0 7 3 7 7s-3 7-8 7h-3M17 11c-3-3 3-4 0-8M25 11c-3-3 3-4 0-8M33 11c-3-3 3-4 0-8"/></svg></span>
                <strong>أكواب</strong><small>صمّم كوبك بطريقتك</small>
            </a>
            <a href="{{ route('customer.stickers') }}" class="suggestion-card suggestion-card--stickers">
                <span><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M12 13h22a6 6 0 0 1 6 6v17a6 6 0 0 1-6 6H12a6 6 0 0 1-6-6V19a6 6 0 0 1 6-6Z"/><path d="M18 6h18a6 6 0 0 1 6 6v18M23 20l2.3 4.7 5.2.8-3.8 3.7.9 5.2-4.6-2.5-4.6 2.5.9-5.2-3.8-3.7 5.2-.8L23 20Z"/></svg></span>
                <strong>ستيكرات</strong><small>لمسات صغيرة تصنع فرقًا</small>
            </a>
        </div>
    </section>
@endsection
