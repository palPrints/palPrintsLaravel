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
                @include('customer.partials.upload-design')
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
                @forelse ($products as $product)
                    <article class="product-card"
                        data-category="{{ $product['category'] }}"
                        data-product="{{ $product['product_key'] }}"
                        data-order="{{ $product['order'] }}"
                        @if ($product['price'] !== null) data-price="{{ $product['price'] }}" @endif
                        @if ($product['custom_image']) data-custom-image="true" @endif
                        @if ($product['available']) data-href="{{ $product['route'] }}" @else data-available="false" @endif>
                        <div class="product-card__media">
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                            @if ($product['status_label'])
                                <span class="product-card__availability">{{ $product['status_label'] }}</span>
                            @endif
                        </div>
                        <div class="product-card__body">
                            <h3>{{ $product['name'] }}</h3>
                            <p>{{ $product['blurb'] }}</p>
                        </div>
                    </article>
                @empty
                    <p class="products-empty" role="status">لا توجد منتجات متاحة حالياً.</p>
                @endforelse
            </div>

            <p class="products-empty" id="productsEmpty" role="status" aria-live="polite" hidden><i class="bi bi-inbox" aria-hidden="true"></i>لا توجد منتجات مطابقة لبحثك.</p>
        </div>
    </section>
@endsection
