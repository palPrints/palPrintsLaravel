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
    @stack('styles')
</head>
<body class="@yield('body-class', 'storefront-page')">
    @include('customer.partials.header')
    @include('customer.partials.sidebar')

    <main id="mainContent" class="store-main">
        @yield('content')
    </main>

    @include('customer.partials.footer')

    <script>
        window.palPrintsCustomerAssets = {
            products: @json(asset('front/assets/images/customer/products')),
        };
    </script>
    <script src="{{ asset('front/js/customer/storefront.js') }}?v={{ filemtime(public_path('front/js/customer/storefront.js')) }}"></script>
    <script src="{{ asset('front/js/customer/interactions.js') }}?v={{ filemtime(public_path('front/js/customer/interactions.js')) }}"></script>
    @stack('scripts')
</body>
</html>
