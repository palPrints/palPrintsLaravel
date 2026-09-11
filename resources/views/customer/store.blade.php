{{--
    Ported from palPrintFront/storefront.html (product grid + hero section).
    Products below are the same 12 static demo cards from the front-end repo —
    not pulled from the database yet.
--}}

@extends('customer.layouts.app')

@section('title', 'متجر PalPrints')

@section('content')
    <section class="hero-section section-space">
        <div class="container-palprints hero-grid">
            <div class="hero-visual">
                <img src="{{ asset('front/assets/images/customer/palprints-hero-collage.png') }}" alt="منتجات PalPrints وتطبيقاتها" class="hero-visual__image">
            </div>
            <div class="hero-copy hero-copy--card">
                <h1><span>منصة </span><strong>PalPrints</strong></h1>
                <p class="hero-copy__text"><span class="hero-copy__line hero-copy__line--dark"> منصتك لطباعة أفكارك على منتجات حقيقية</span><br><span class="hero-copy__line hero-copy__line--accent">تصاميم جاهزة من مصممين، أو تصميمك الخاص، بلمسة واحدة</span></p>
                <div class="hero-cta">
                    <a href="#products" class="hero-cta__btn hero-cta__btn--primary">تصفح المنتجات</a>
                    <a href="#" class="hero-cta__btn hero-cta__btn--outline">ارفع تصميمك الخاص</a>
                </div>
            </div>
        </div>
    </section>

    <section id="products" class="section-space products-section">
        <div class="container-palprints">
            <div class="section-heading">
                <h2>تصفح منتجات متجرنا</h2>
                <p>اختر المنتج المناسب لك واستكشف التصاميم المتاحة عليه</p>
            </div>

            <div class="products-grid" id="productGrid">
                <article class="product-card" data-category="clothing" data-product="shirt" data-order="1">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/1.png') }}" alt="تيشيرت">
                    </div>
                    <div class="product-card__body">
                        <h3>تيشيرت</h3>
                        <p>تصاميم مخصصة بطباعة واضحة ومظهر يومي أنيق.</p>
                    </div>
                </article>
                <article class="product-card" data-category="clothing" data-product="hoodie" data-order="2" data-href="{{ route('customer.hoodies') }}">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/2.png') }}" alt="هودي">
                    </div>
                    <div class="product-card__body">
                        <h3>هودي</h3>
                        <p>خيار مريح بطباعة مميزة يناسب الاستخدام اليومي.</p>
                    </div>
                </article>
                <article class="product-card" data-category="clothing" data-product="cap" data-order="6" data-available="false">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/3.png') }}" alt="قبعات">
                    </div>
                    <div class="product-card__body">
                        <h3>قبعات</h3>
                        <p>قبعات بطابع بسيط مع إمكانية تخصيص التصميم.</p>
                    </div>
                </article>
                <article class="product-card" data-category="accessories" data-product="bag" data-order="7" data-available="false">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/4.png') }}" alt="حقائب">
                    </div>
                    <div class="product-card__body">
                        <h3>حقائب</h3>
                        <p>حقائب عملية بتصاميم مطبوعة تناسب الهدايا والاستخدام.</p>
                    </div>
                </article>
                <article class="product-card" data-category="accessories" data-product="scarf" data-order="8" data-available="false">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/5.png') }}" alt="وشاحات">
                    </div>
                    <div class="product-card__body">
                        <h3>وشاحات</h3>
                        <p>وشاحات بطباعة خاصة ولمسة ناعمة تناسب المناسبات.</p>
                    </div>
                </article>
                <article class="product-card" data-category="accessories" data-product="phone-case" data-order="9" data-available="false">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/6.png') }}" alt="كفرات موبايل">
                    </div>
                    <div class="product-card__body">
                        <h3>كفرات موبايل</h3>
                        <p>حماية أنيقة للموبايل مع تصاميم قابلة للتخصيص.</p>
                    </div>
                </article>
                <article class="product-card" data-category="drinkware" data-product="cups" data-order="5">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/7.png') }}" alt="اكواب">
                    </div>
                    <div class="product-card__body">
                        <h3>اكواب</h3>
                        <p>أكواب مطبوعة بجودة عالية تناسب البيت والعمل.</p>
                    </div>
                </article>
                <article class="product-card" data-category="office" data-product="paper" data-order="3">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/8.png') }}" alt="طباعة ورق">
                    </div>
                    <div class="product-card__body">
                        <h3>طباعة ورق</h3>
                        <p>طباعة ورق متنوعة للتغليف والعرض بطباعة مرتبة وأنيقة.</p>
                    </div>
                </article>
                <article class="product-card" data-category="office" data-product="notebooks" data-order="10" data-available="false">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/9.png') }}" alt="دفاتر">
                    </div>
                    <div class="product-card__body">
                        <h3>دفاتر</h3>
                        <p>دفاتر بتصميم خاص تناسب الدراسة والعمل والهدايا.</p>
                    </div>
                </article>
                <article class="product-card" data-category="office" data-product="posters" data-order="11" data-available="false">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/10.png') }}" alt="بوسترات">
                    </div>
                    <div class="product-card__body">
                        <h3>بوسترات</h3>
                        <p>بوسترات مطبوعة بجودة واضحة لعرض الأفكار والديكور.</p>
                    </div>
                </article>
                <article class="product-card" data-category="office" data-product="stickers" data-order="4">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/11.png') }}" alt="ستيكرات">
                    </div>
                    <div class="product-card__body">
                        <h3>ستيكرات</h3>
                        <p>ستيكرات مخصصة بأشكال متعددة ولمسات جذابة.</p>
                    </div>
                </article>
                <article class="product-card" data-category="office" data-product="wedding-cards" data-order="12" data-available="false">
                    <div class="product-card__media">
                        <img src="{{ asset('front/assets/images/customer/products/12.png') }}" alt="كروت افراح">
                    </div>
                    <div class="product-card__body">
                        <h3>كروت افراح</h3>
                        <p>كروت أفراح بتصاميم فخمة تناسب المناسبات الخاصة.</p>
                    </div>
                </article>
            </div>
            <p class="products-empty" id="productsEmpty" role="status" aria-live="polite" hidden>لا توجد منتجات مطابقة لبحثك.</p>
        </div>
    </section>
@endsection
