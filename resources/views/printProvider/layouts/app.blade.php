<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="لوحة تحكم المطابع في منصة PalPrints">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') | PalPrints</title>

    <link rel="icon" type="image/png" href="{{ asset('front/assets/images/palprints-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('front/designer/assets/icons/bootstrap-icons.min.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/shell.css') }}?v={{ filemtime(public_path('front/css/printProvider/shell.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/dashboard.css') }}?v={{ filemtime(public_path('front/css/printProvider/dashboard.css')) }}">
</head>
<body class="printshop-dashboard-page sidebar-collapsed @yield('bodyClass')">
    <a class="skip-link" href="#printshopDashboardMain">تخطي إلى المحتوى</a>

    @include('printProvider.partials.sidebar')

    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="إغلاق القائمة"></button>

    <div class="wallet-shell">
        @include('printProvider.partials.topbar')

        <main class="printshop-main" id="printshopDashboardMain">
            @yield('content')
        </main>
    </div>

    <div class="printshop-toast" id="dashboardToast" role="status" aria-live="polite" aria-atomic="true"></div>

    <script src="{{ asset('front/js/printProvider/dashboard.js') }}"></script>
    @stack('scripts')
</body>
</html>
