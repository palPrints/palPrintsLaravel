@extends('customer.layouts.app')

@section('title', 'متجر PalPrints')

@section('content')
    <section class="hero-section section-space">
        <div class="container-palprints hero-grid">
            <div class="hero-visual">
                <img src="{{ asset('front/assets/images/customer/palprints-hero-collage.png') }}" alt="منتجات PalPrints" class="hero-visual__image">
            </div>
            <div class="hero-copy hero-copy--card">
                <h1><span>منصة </span><strong>PalPrints</strong></h1>
                <p class="hero-copy__text">
                    <span class="hero-copy__line hero-copy__line--dark">منصتك لطباعة أفكارك على منتجات حقيقية</span><br>
                    <span class="hero-copy__line hero-copy__line--accent">اختر المنتج ثم تصفح التصاميم المعتمدة عليه</span>
                </p>
                <div class="hero-cta">
                    <a href="#products" class="hero-cta__btn hero-cta__btn--primary">تصفح المنتجات</a>
                    <a href="{{ route('designer.designs.create') }}" class="hero-cta__btn hero-cta__btn--outline">ارفع تصميمك الخاص</a>
                </div>
            </div>
        </div>
    </section>

    <section id="products" class="section-space products-section">
        <div class="container-palprints">
            <div class="section-heading">
                <h2>تصفح منتجات متجرنا</h2>
                <p>اختر المنتج المناسب لك، ثم شاهد التصاميم المنشورة المتاحة عليه.</p>
            </div>

            <div class="products-grid" id="productGrid">
                @forelse($products as $product)
                    <article
                        class="product-card @unless($product['available']) is-unavailable @endunless"
                        data-category="{{ $product['category'] }}"
                        data-product="{{ $product['product_key'] }}"
                        data-order="{{ $product['order'] }}"
                        data-price="{{ $product['price'] }}"
                        data-available="{{ $product['available'] ? 'true' : 'false' }}"
                        @if($product['route']) data-href="{{ $product['route'] }}" @endif
                    >
                        <div class="product-card__media">
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                            @unless($product['available'])
                                <span class="product-card__availability">غير متاح</span>
                            @endunless
                        </div>
                        <div class="product-card__body">
                            <h3>{{ $product['name'] }}</h3>
                            <p>{{ $product['description'] }}</p>
                        </div>
                    </article>
                @empty
                    <p class="products-empty" role="status">لا توجد منتجات متاحة حالياً.</p>
                @endforelse
            </div>

            <p class="products-empty" id="productsEmpty" role="status" aria-live="polite" hidden>لا توجد منتجات مطابقة لبحثك.</p>
        </div>
    </section>
@endsection
