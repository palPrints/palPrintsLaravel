<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="لوحة تحكم مصمم PalPrints">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') | PalPrints</title>

    <script>
        (function () {
            try {
                var defaultsKey = 'palprints-designer-dashboard-defaults-v2';

                if (localStorage.getItem(defaultsKey) !== 'true') {
                    localStorage.setItem('palprints-language', 'ar');
                    localStorage.setItem('palprints-theme', 'light');
                    localStorage.setItem('palprints-sidebar-collapsed', 'false');
                    localStorage.setItem(defaultsKey, 'true');
                }

                var language = 'ar'; // the site is Arabic only
                var theme = localStorage.getItem('palprints-theme') === 'dark' ? 'dark' : 'light';

                document.documentElement.lang = language;
                document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
                document.documentElement.setAttribute('data-bs-theme', theme);
            } catch (error) {
                document.documentElement.lang = 'ar';
                document.documentElement.dir = 'rtl';
                document.documentElement.setAttribute('data-bs-theme', 'light');
            }
        })();
    </script>

    @php
        $designerAsset = fn (string $path): string => asset('front/designer/'.$path).'?v='.filemtime(public_path('front/designer/'.$path));
    @endphp

    <link rel="preload" href="{{ asset('front/shared/fonts/Cairo-Variable.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="{{ asset('front/shared/icons/fonts/bootstrap-icons.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('front/shared/icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ $designerAsset('css/profile-core.css') }}">
    <link rel="stylesheet" href="{{ $designerAsset('css/designerProfile.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ $designerAsset('css/designer-laravel.css') }}">
    <link rel="stylesheet" href="{{ asset('front/shared/interactions.css').'?v='.filemtime(public_path('front/shared/interactions.css')) }}">
</head>
<body class="profile-page designer-profile-page @yield('body-class')">
    <a href="#designerMain" class="profile-skip-link" data-i18n="skipToContent">تخطي إلى المحتوى</a>

    <div class="profile-app">
        @include('designer.partials.sidebar')

        <button type="button" class="profile-sidebar-backdrop" id="sidebarBackdrop" aria-label="إغلاق القائمة الجانبية" data-i18n-aria="closeSidebar"></button>

        <div class="profile-shell">
            @include('designer.partials.topbar')

            @yield('content')
        </div>
    </div>

    <div class="profile-toast" id="profileToast" role="status" aria-live="polite" aria-atomic="true">
        <span class="profile-toast-icon" aria-hidden="true"><i class="bi bi-check2"></i></span>
        <span id="profileToastMessage"></span>
    </div>

    <script src="{{ $designerAsset('js/profile-core.js') }}"></script>
    @stack('scripts')
    <script src="{{ $designerAsset('js/designer-shell.js') }}"></script>
    <script src="{{ asset('front/shared/interactions.js').'?v='.filemtime(public_path('front/shared/interactions.js')) }}"></script>
@include('partials.page-loader')
</body>
</html>
