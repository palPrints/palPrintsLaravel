<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta-description', 'متجر PalPrints للمنتجات الطباعية المخصصة')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'متجر PalPrints')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('front/css/customer/storefront.css') }}?v={{ filemtime(public_path('front/css/customer/storefront.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/customer/interactions.css') }}?v={{ filemtime(public_path('front/css/customer/interactions.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/shared/site-footer.css') }}?v={{ filemtime(public_path('front/css/shared/site-footer.css')) }}">
    @stack('styles')
</head>
<body class="@yield('body-class', 'storefront-page')">
    @include('customer.partials.header')
    @include('customer.partials.sidebar')

    <main id="mainContent" class="store-main">
        @yield('content')
    </main>

    @include('partials.footer')

    <script>
        window.palPrintsCustomerAssets = {
            products: @json(asset('front/assets/images/customer/products')),
            basketUrl: @json(route('customer.basket')),
            emptyBasketUrl: @json(route('customer.basket.empty')),
            basketSeed: [
                {
                    id: 'tshirt-explore-more',
                    title: 'تيشيرت كلاسيك',
                    description: 'تصميم Explore More',
                    image: @json(asset('front/assets/images/customer/orderBasket/explore-more-tshirt.png')),
                    price: 20,
                    quantity: 2,
                    meta: ['المقاس: L', 'اللون: أسود', 'الطباعة: أمامي']
                },
                {
                    id: 'hoodie-good-vibes',
                    title: 'هودي',
                    description: 'تصميم Good Vibes',
                    image: @json(asset('front/assets/images/customer/orderBasket/good-vibes-hoodie.png')),
                    price: 28,
                    quantity: 1,
                    meta: ['المقاس: M', 'اللون: رمادي']
                },
                {
                    id: 'cap-mountain',
                    title: 'طاقية',
                    description: 'تصميم Mountain',
                    image: @json(asset('front/assets/images/customer/orderBasket/mountain-cap.png')),
                    price: 15,
                    quantity: 3,
                    meta: ['المقاس: مقاس واحد', 'اللون: أخضر']
                }
            ],
        };
    </script>
    <script src="{{ asset('front/js/customer/storefront.js') }}?v={{ filemtime(public_path('front/js/customer/storefront.js')) }}"></script>
    <script src="{{ asset('front/js/customer/interactions.js') }}?v={{ filemtime(public_path('front/js/customer/interactions.js')) }}"></script>
    @stack('scripts')
</body>
</html>
