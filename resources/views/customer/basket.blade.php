{{--
    Ported from palPrintFront/orderBasket.html.
    The 3 basket items (and the "ترند فلسطين" trending strip) are still
    hardcoded — same "demo data" situation the other product pages have
    before it's wired to a real cart/products table. Once that exists, this
    page and customer.basket-empty should probably become one route whose
    view depends on whether the cart actually has items.
--}}
@extends('customer.layouts.app')

@section('title', 'سلة التسوق')
@section('meta-description', 'سلة التسوق في متجر PalPrints')
@section('body-class', 'storefront-page order-basket-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/orderBasket.css') }}?v={{ filemtime(public_path('front/css/customer/orderBasket.css')) }}">
@endpush

@section('content')
    <div class="page-heading">
        <div class="breadcrumbs">
            <a href="{{ route('home') }}">الرئيسية</a>
            <span>/</span>
            <span>السلة</span>
        </div>
        <h1><i class="bi bi-cart3"></i> سلة التسوق</h1>
        <p>لديك <strong id="itemsCount">3</strong> منتجات في السلة</p>
    </div>

    <section class="basket-card" aria-labelledby="basketTitle">
        <h2 class="sr-only" id="basketTitle">منتجات السلة</h2>
        <div class="basket-items" id="basketItems"></div>
        <div class="basket-actions">
            <a href="{{ route('customer.store') }}#products" class="primary-action"><i class="bi bi-arrow-left"></i> متابعة لإتمام الطلب</a>
            <a href="{{ route('customer.store') }}" class="secondary-action">العودة إلى متجر المنتجات <i class="bi bi-arrow-right"></i></a>
        </div>
    </section>

    <section class="trending" aria-labelledby="trendingTitle">
        <div class="section-heading"><div><h2 id="trendingTitle">ترند فلسطين</h2><p>منتجات مستوحاة من فلسطين، الأكثر رواجًا بين عملائنا</p></div></div>
        <div class="product-grid">
            <article class="trend-card"><div class="trend-media"><img src="{{ asset('front/assets/images/customer/products/4.png') }}" alt="حقيبة قماش فلسطين"></div><h3>حقيبة قماش</h3><p>تصميم أغصان الزيتون</p><strong>يبدأ من <span dir="ltr">$20</span></strong><a href="{{ route('customer.store') }}">عرض المنتج</a></article>
            <article class="trend-card"><div class="trend-media"><img src="{{ asset('front/assets/images/customer/orderBasket/mountain-cap.png') }}" alt="طاقية فلسطين"></div><h3>طاقية فلسطين</h3><p>تصميم الجبال</p><strong>يبدأ من <span dir="ltr">$20</span></strong><a href="{{ route('customer.store') }}">معاينة المنتج</a></article>
            <article class="trend-card"><div class="trend-media"><img src="{{ asset('front/assets/images/customer/orderBasket/good-vibes-hoodie.png') }}" alt="هودي فلسطين"></div><h3>هودي فلسطين</h3><p>تصميم تفاؤل</p><strong>يبدأ من <span dir="ltr">$20</span></strong><a href="{{ route('customer.hoodies') }}">عرض المنتج</a></article>
            <article class="trend-card"><div class="trend-media"><img src="{{ asset('front/assets/images/customer/orderBasket/explore-more-tshirt.png') }}" alt="تيشيرت فلسطين"></div><h3>تيشيرت فلسطين</h3><p>تصميم خريطة الأمل</p><strong>يبدأ من <span dir="ltr">$20</span></strong><a href="{{ route('customer.tshirts') }}">معاينة المنتج</a></article>
        </div>
    </section>

    <div class="toast" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/orderBasket.js') }}?v={{ filemtime(public_path('front/js/customer/orderBasket.js')) }}"></script>
@endpush
