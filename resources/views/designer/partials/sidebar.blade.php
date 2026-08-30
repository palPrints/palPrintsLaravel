<aside
    class='profile-sidebar'
    id='profileSidebar'
    aria-label='القائمة الجانبية للمصمم'
    data-i18n-aria='sidebarLabel'
>
    <div class='profile-sidebar-header'>
        <a href='{{ route('home') }}' class='profile-brand' aria-label='الصفحة الرئيسية' data-i18n-aria='goHome'>
            <img
                src='{{ asset('front/assets/images/palprints-logo.png') }}'
                class='profile-brand-image'
                alt='PalPrints'
            >
        </a>

        <button
            type='button'
            class='profile-sidebar-close'
            id='sidebarCloseButton'
            aria-label='إغلاق القائمة'
            data-i18n-aria='closeSidebar'
        >
            <i class='bi bi-x-lg' aria-hidden='true'></i>
        </button>
    </div>

    <nav class='profile-sidebar-nav' aria-label='روابط حساب المصمم' data-i18n-aria='designerNavLabel'>
        <a
            href='{{ route('designer.dashboard') }}'
            @class(['profile-sidebar-link', 'active' => request()->routeIs('designer.dashboard')])
            @if(request()->routeIs('designer.dashboard')) aria-current='page' @endif
        >
            <i class='bi bi-grid' aria-hidden='true'></i>
            <span data-i18n='dashboard'>لوحة التحكم</span>
        </a>

        <a
            href='{{ route('designer.designs.create') }}'
            @class(['profile-sidebar-link', 'active' => request()->routeIs('designer.designs.create')])
            @if(request()->routeIs('designer.designs.create')) aria-current='page' @endif
        >
            <i class='bi bi-cloud-arrow-up' aria-hidden='true'></i>
            <span data-i18n='uploadDesign'>رفع تصميم جديد</span>
        </a>

        <a
            href='{{ route('designer.designs.index') }}'
            @class(['profile-sidebar-link', 'active' => request()->routeIs('designer.designs.index')])
            @if(request()->routeIs('designer.designs.index')) aria-current='page' @endif
        >
            <i class='bi bi-images' aria-hidden='true'></i>
            <span data-i18n='myDesigns'>تصاميمي</span>
        </a>

        <a
            href='{{ route('designer.profile') }}'
            @class(['profile-sidebar-link', 'active' => request()->routeIs('designer.profile')])
            @if(request()->routeIs('designer.profile')) aria-current='page' @endif
        >
            <i class='bi bi-person' aria-hidden='true'></i>
            <span data-i18n='profileTitle'>الملف الشخصي</span>
        </a>

        <a
            href='{{ route('designer.earnings') }}'
            @class(['profile-sidebar-link', 'active' => request()->routeIs('designer.earnings')])
            @if(request()->routeIs('designer.earnings')) aria-current='page' @endif
        >
            <i class='bi bi-coin' aria-hidden='true'></i>
            <span data-i18n='earnings'>الأرباح</span>
        </a>

        <div class='profile-sidebar-divider' aria-hidden='true'></div>

        <a
            href='{{ route('designer.settings') }}'
            @class(['profile-sidebar-link', 'active' => request()->routeIs('designer.settings')])
            @if(request()->routeIs('designer.settings')) aria-current='page' @endif
        >
            <i class='bi bi-gear' aria-hidden='true'></i>
            <span data-i18n='settings'>الإعدادات</span>
        </a>

        <a
            href='{{ route('designer.support') }}'
            @class(['profile-sidebar-link', 'active' => request()->routeIs('designer.support')])
            @if(request()->routeIs('designer.support')) aria-current='page' @endif
        >
            <i class='bi bi-headset' aria-hidden='true'></i>
            <span data-i18n='support'>التواصل مع الدعم الفني</span>
        </a>
    </nav>

    <form method='POST' action='{{ route('logout') }}' id='designerLogoutForm' class='profile-sidebar-logout-form'>
        @csrf
        <button type='submit' class='profile-sidebar-logout'>
            <i class='bi bi-box-arrow-left' aria-hidden='true'></i>
            <span data-i18n='logout'>تسجيل الخروج</span>
        </button>
    </form>
</aside>
