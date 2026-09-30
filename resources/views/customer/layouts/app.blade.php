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
    <link rel="stylesheet" href="{{ asset('front/shared/interactions.css') }}?v={{ filemtime(public_path('front/shared/interactions.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/shared/site-footer.css') }}?v={{ filemtime(public_path('front/css/shared/site-footer.css')) }}">
    @stack('styles')
</head>
<body class="@yield('body-class', 'storefront-page')" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
    <script>
        // Toggled from the settings page's "تقليل الحركات" switch; applied here so it takes effect on every page.
        if (localStorage.getItem('palprints-reduce-motion') === '1') {
            document.body.classList.add('pp-reduce-motion');
        }
        // Toggled from the settings page's "الوضع الليلي" switch. Scoped to settings-page only
        // (not a global data-bs-theme flip) so it can't collide with the separate, page-specific
        // dark-mode CSS already baked into storefront/catalog pages.
        if (document.body.classList.contains('settings-page') && localStorage.getItem('palprints-pp-dark') === '1') {
            document.body.classList.add('pp-dark-mode');
        }
    </script>
    @include('customer.partials.header')
    @include('customer.partials.sidebar')

    <main id="@yield('main-id', 'mainContent')" class="store-main @yield('main-class')">
        @yield('content')
    </main>

    @include('partials.footer')

    <script>
        window.palPrintsCustomerAssets = {
            products: @json(asset('front/assets/images/customer/products')),
            basketUrl: @json(route('customer.basket')),
            emptyBasketUrl: @json(route('customer.basket.empty')),
            productPreviewUrl: @json(route('customer.productPreview')),
            cartPaperUrl: @json(route('customer.cart.store-paper')),
        };
    </script>
    <script src="{{ asset('front/js/customer/storefront.js') }}?v={{ filemtime(public_path('front/js/customer/storefront.js')) }}"></script>
    <script src="{{ asset('front/shared/interactions.js') }}?v={{ filemtime(public_path('front/shared/interactions.js')) }}"></script>
    @stack('scripts')
@include('partials.page-loader', ['wait' => View::hasSection('page-loader-wait')])
</body>
</html>
