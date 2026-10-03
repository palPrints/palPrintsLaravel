@php
    $navItems = [
        ['route' => 'designer.dashboard', 'active' => ['designer.dashboard'], 'icon' => 'bi-grid', 'i18n' => 'dashboard', 'label' => 'لوحة التحكم'],
        ['route' => 'designer.designs.create', 'active' => ['designer.designs.create', 'designer.designs.preview', 'designer.designs.review'], 'icon' => 'bi-cloud-arrow-up', 'i18n' => 'uploadDesign', 'label' => 'رفع تصميم جديد'],
        ['route' => 'designer.designs.index', 'active' => ['designer.designs.index'], 'icon' => 'bi-images', 'i18n' => 'myDesigns', 'label' => 'تصاميمي'],
        ['route' => 'designer.profile', 'active' => ['designer.profile'], 'icon' => 'bi-person', 'i18n' => 'profileTitle', 'label' => 'الملف الشخصي'],
        ['route' => 'designer.earnings', 'active' => ['designer.earnings'], 'icon' => 'bi-coin', 'i18n' => 'earnings', 'label' => 'الأرباح'],
    ];
    $secondaryItems = [
        ['route' => 'designer.settings', 'active' => ['designer.settings'], 'icon' => 'bi-gear', 'i18n' => 'settings', 'label' => 'الإعدادات'],
        ['route' => 'designer.support', 'active' => ['designer.support'], 'icon' => 'bi-headset', 'i18n' => 'support', 'label' => 'مركز المساعدة'],
    ];
@endphp

<aside class="profile-sidebar" id="profileSidebar" aria-label="القائمة الجانبية للمصمم" data-i18n-aria="sidebarLabel">
    <nav class="profile-sidebar-nav" aria-label="روابط حساب المصمم" data-i18n-aria="designerNavLabel">
        @foreach ($navItems as $item)
            @php($isActive = request()->routeIs(...$item['active']))
            <a href="{{ route($item['route']) }}" @class(['profile-sidebar-link', 'active' => $isActive]) @if ($isActive) aria-current="page" @endif>
                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i><span data-i18n="{{ $item['i18n'] }}">{{ $item['label'] }}</span>
            </a>
        @endforeach

        <div class="profile-sidebar-divider" aria-hidden="true"></div>

        @foreach ($secondaryItems as $item)
            @php($isActive = request()->routeIs(...$item['active']))
            <a href="{{ route($item['route']) }}" @class(['profile-sidebar-link', 'active' => $isActive]) @if ($isActive) aria-current="page" @endif>
                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i><span data-i18n="{{ $item['i18n'] }}">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="profile-sidebar-logout-form">
        @csrf
        <button type="submit" class="profile-sidebar-logout">
            <i class="bi bi-box-arrow-left" aria-hidden="true"></i><span data-i18n="logout">تسجيل الخروج</span>
        </button>
    </form>
</aside>
