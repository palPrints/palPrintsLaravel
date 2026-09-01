<!doctype html>
<html lang='ar' dir='rtl' data-bs-theme='light'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='description' content='لوحة تحكم مصمم PalPrints'>
    <meta name='csrf-token' content='{{ csrf_token() }}'>
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

                var language = localStorage.getItem('palprints-language') === 'en' ? 'en' : 'ar';
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

    <link rel='preload' href='{{ asset('front/designer/assets/fonts/Cairo-Variable.ttf') }}' as='font' type='font/ttf' crossorigin>
    <link rel='preload' href='{{ asset('front/designer/assets/icons/fonts/bootstrap-icons.woff2') }}' as='font' type='font/woff2' crossorigin>
    <link rel='stylesheet' href='{{ asset('front/designer/assets/icons/bootstrap-icons.min.css') }}'>
    @stack('base-styles')
    <link rel='stylesheet' href='{{ asset('front/designer/css/designer.css') }}?v={{ filemtime(public_path('front/designer/css/designer.css')) }}'>
    @stack('styles')
</head>
<body class='dashboard-page'>
    <div class='dashboard-container profile-app' id='app'>
        @include('designer.partials.sidebar')

        <button
            type='button'
            class='sidebar-backdrop'
            id='sidebarBackdrop'
            aria-label='إغلاق القائمة'
            data-i18n-aria='closeSidebar'
        ></button>

        <div class='page-shell'>
            @include('designer.partials.topbar')

            <main class='main-content @yield('main-class')' id='designerMain'>
                @yield('content')
            </main>
        </div>
    </div>

    <script src='{{ asset('front/designer/js/designer.js') }}'></script>
    @stack('scripts')
</body>
</html>
