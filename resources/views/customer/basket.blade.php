{{--
    Wired to the real carts/cart_items tables via CustomerCartController.
    The old orderBasket.js was broken beyond the cart logic (undefined
    `toggle`/`sidebar`/`items` DOM refs, a missing itemTemplate() function —
    it would have thrown immediately), so this page uses plain server-rendered
    forms for quantity/remove instead, same pattern as the orders page's
    cancel button. The "ترند فلسطين" trending strip below is still a static
    marketing strip (not cart data), left as-is.
--}}
@extends('customer.layouts.app')

@section('title', 'سلة التسوق')
@section('meta-description', 'سلة التسوق في متجر PalPrints')
@section('body-class', 'storefront-page order-basket-page')
@section('main-class', 'basket-main')
@section('main-id', 'basketMain')

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
        <p>لديك <strong id="itemsCount">{{ $items->count() }}</strong> منتجات في السلة</p>
    </div>

    @if(session('status') === 'item-added')
        <div class="alert-success page-alert">تمت إضافة المنتج إلى السلة.</div>
    @elseif(session('status') === 'item-removed')
        <div class="alert-success page-alert">تم حذف المنتج من السلة.</div>
    @endif

    @if($items->isEmpty())
        <section class="basket-card basket-empty-state">
            <span class="basket-empty-state__icon"><i class="bi bi-cart-x"></i></span>
            <h2>سلتك فارغة حاليًا</h2>
            <p>تصفحي المتجر وضيفي منتجات لتبدئي طلبك.</p>
            <a class="primary-action" href="{{ route('customer.store') }}">تصفح المتجر <i class="bi bi-arrow-left"></i></a>
        </section>
    @else
        <section class="basket-card" aria-labelledby="basketTitle">
            <h2 class="sr-only" id="basketTitle">منتجات السلة</h2>
            <div class="basket-items" id="basketItems">
                @foreach($items as $item)
                    <article class="basket-item" data-id="{{ $item['id'] }}">
                        <div class="product-info">
                            <div class="product-media" @if(!empty($item['design_overlay'])) style="position:relative" @endif>
                                @if(!empty($item['mockup']))
                                    @include('customer.partials.cart-mockup', ['mockup' => $item['mockup'], 'alt' => $item['product_name']])
                                @else
                                <img src="{{ $item['product_image'] }}" alt="{{ $item['product_name'] }}">
                                @if(!empty($item['design_overlay']))
                                    <img class="design-overlay" src="{{ $item['design_overlay'] }}" alt="التصميم" style="position:absolute;inset:50% auto auto 50%;width:38%;height:auto;max-height:46%;transform:translate(-50%,-50%);object-fit:contain">
                                @endif
                                @endif
                            </div>
                            <div>
                                <h3>{{ $item['product_name'] }}</h3>
                                @if(!empty($item['options']))
                                    <div class="tags">
                                        @if(!empty($item['product_label']))<span>{{ $item['product_label'] }}</span>@endif
                                        @foreach($item['options'] as $key => $value)
                                            @if(! in_array($key, ['files', 'design_name', 'layout', 'branch_product_offering_id', 'print_provider_branch_id'], true) && is_scalar($value))
                                                <span>{{ $value }}</span>
                                            @endif
                                        @endforeach
                                        @if(!empty($item['options']['print_areas']) && is_array($item['options']['print_areas']))
                                            <span><i class="bi bi-printer"></i> طباعة: {{ implode(' + ', $item['options']['print_areas']) }}</span>
                                        @endif
                                        @if(!empty($item['options']['files']))
                                            <span><i class="bi bi-file-earmark-pdf"></i> {{ count($item['options']['files']) }} ملف</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="item-price">
                            <strong dir="ltr">{{ number_format($item['total_price'], 0) }} ₪</strong>
                            <span dir="ltr">{{ number_format($item['unit_price'], 0) }} ₪ × {{ $item['quantity'] }}</span>
                        </div>
                        <div class="quantity">
                            <form method="POST" action="{{ route('customer.cart.update', $item['id']) }}" style="display:contents">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="quantity" value="{{ max(1, $item['quantity'] - 1) }}">
                                <button type="submit" aria-label="إنقاص الكمية" @if($item['quantity'] <= 1) disabled @endif>−</button>
                            </form>
                            <output>{{ $item['quantity'] }}</output>
                            <form method="POST" action="{{ route('customer.cart.update', $item['id']) }}" style="display:contents">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                                <button type="submit" aria-label="زيادة الكمية">+</button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('customer.cart.destroy', $item['id']) }}" style="display:contents"
                              data-confirm-title="حذف المنتج من السلة"
                              data-confirm-message="هل تريد حذف :name من السلة؟"
                              data-confirm-name="{{ $item['product_name'] }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="remove-item" aria-label="حذف المنتج"><i class="bi bi-trash3"></i></button>
                        </form>
                    </article>
                @endforeach
            </div>
            <div class="basket-actions">
                <a href="{{ route('customer.checkout') }}" class="primary-action"><i class="bi bi-arrow-left"></i> متابعة لإتمام الطلب</a>
                <a href="{{ route('customer.store') }}" class="secondary-action">العودة إلى متجر المنتجات <i class="bi bi-arrow-right"></i></a>
            </div>
        </section>
    @endif

    <section class="trending" aria-labelledby="trendingTitle">
        <div class="section-heading"><div><h2 id="trendingTitle">ترند فلسطين</h2><p>منتجات مستوحاة من فلسطين، الأكثر رواجًا بين عملائنا</p></div></div>
        <div class="product-grid">
            <article class="trend-card"><div class="trend-media"><img src="{{ asset('front/assets/images/customer/products/4.png') }}" alt="حقيبة قماش فلسطين"></div><h3>حقيبة قماش</h3><p>تصميم أغصان الزيتون</p><strong>يبدأ من <span dir="ltr">20 ₪</span></strong><a href="{{ route('customer.store') }}">عرض المنتج</a></article>
            <article class="trend-card"><div class="trend-media"><img src="{{ asset('front/assets/images/customer/orderBasket/mountain-cap.png') }}" alt="طاقية فلسطين"></div><h3>طاقية فلسطين</h3><p>تصميم الجبال</p><strong>يبدأ من <span dir="ltr">20 ₪</span></strong><a href="{{ route('customer.store') }}">معاينة المنتج</a></article>
            <article class="trend-card"><div class="trend-media"><img src="{{ asset('front/assets/images/customer/orderBasket/good-vibes-hoodie.png') }}" alt="هودي فلسطين"></div><h3>هودي فلسطين</h3><p>تصميم تفاؤل</p><strong>يبدأ من <span dir="ltr">20 ₪</span></strong><a href="{{ route('customer.hoodies') }}">عرض المنتج</a></article>
            <article class="trend-card"><div class="trend-media"><img src="{{ asset('front/assets/images/customer/orderBasket/explore-more-tshirt.png') }}" alt="تيشيرت فلسطين"></div><h3>تيشيرت فلسطين</h3><p>تصميم خريطة الأمل</p><strong>يبدأ من <span dir="ltr">20 ₪</span></strong><a href="{{ route('customer.tshirts') }}">معاينة المنتج</a></article>
        </div>
    </section>
@endsection
