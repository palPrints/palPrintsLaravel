<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="لوحة تحكم إدارة منصة PalPrints">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة تحكم الإدارة') | PalPrints</title>

    @php
        $adminAsset = fn (string $path): string => asset('front/'.$path).'?v='.filemtime(public_path('front/'.$path));
    @endphp

    <link rel="icon" type="image/png" href="{{ $adminAsset('assets/images/admin/logoWithoutBackGround.png') }}">
    <link rel="preload" href="{{ $adminAsset('shared/fonts/Cairo-Variable.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="{{ asset('front/shared/icons/fonts/bootstrap-icons.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ $adminAsset('shared/icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ $adminAsset('css/admin/admin-shell.css') }}">
    @stack('styles')
</head>
<body class="admin-dashboard-page @yield('body-class') sidebar-collapsed">
    <a class="skip-link" href="#@yield('main-id', 'adminMain')">تخطي إلى المحتوى</a>

    @include('admin.partials.sidebar')

    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="إغلاق القائمة"></button>

    <div class="wallet-shell">
        @include('admin.partials.topbar')

        @yield('content')
    </div>

    <div class="admin-toast" id="adminToast" role="status" aria-live="polite" aria-atomic="true"></div>

    <script src="{{ $adminAsset('js/admin/admin-shell.js') }}"></script>
    @stack('scripts')
@include('partials.page-loader')
</body>
</html>
