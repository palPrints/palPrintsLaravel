{{--
    The designer's side menu for the pages a designer shares with customers (design studio, design preview).
    Same markup and ids as customer.partials.sidebar so storefront.js drives it, but with the designer's own links.
--}}
@php
    $designerNav = [
        ['route' => 'designer.dashboard', 'active' => ['designer.dashboard'], 'icon' => 'bi-grid', 'label' => 'لوحة التحكم'],
        ['route' => 'designer.designs.create', 'active' => ['designer.designs.create', 'designer.designs.preview', 'designer.designs.review', 'design-studio'], 'icon' => 'bi-cloud-arrow-up', 'label' => 'رفع تصميم جديد'],
        ['route' => 'designer.designs.index', 'active' => ['designer.designs.index'], 'icon' => 'bi-images', 'label' => 'تصاميمي'],
        ['route' => 'designer.profile', 'active' => ['designer.profile'], 'icon' => 'bi-person', 'label' => 'الملف الشخصي'],
        ['route' => 'designer.earnings', 'active' => ['designer.earnings'], 'icon' => 'bi-coin', 'label' => 'الأرباح'],
    ];
    $designerSecondaryNav = [
        ['route' => 'designer.settings', 'active' => ['designer.settings'], 'icon' => 'bi-gear', 'label' => 'الإعدادات'],
        ['route' => 'designer.support', 'active' => ['designer.support'], 'icon' => 'bi-headset', 'label' => 'مركز المساعدة'],
    ];
@endphp
<div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>
<aside class="store-sidebar" id="storeSidebar" aria-label="القائمة الجانبية للمصمم" aria-hidden="true">
    <nav class="store-sidebar__nav" aria-label="روابط حساب المصمم">
        @foreach ($designerNav as $item)
            @php($isActive = request()->routeIs(...$item['active']))
            <a href="{{ route($item['route']) }}" class="store-sidebar__item pal-pressable{{ $isActive ? ' active' : '' }}" @if ($isActive) aria-current="page" @endif data-tooltip="{{ $item['label'] }}"><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['label'] }}</span></a>
        @endforeach
        <div class="store-sidebar__divider" aria-hidden="true"></div>
        @foreach ($designerSecondaryNav as $item)
            @php($isActive = request()->routeIs(...$item['active']))
            <a href="{{ route($item['route']) }}" class="store-sidebar__item pal-pressable{{ $isActive ? ' active' : '' }}" @if ($isActive) aria-current="page" @endif data-tooltip="{{ $item['label'] }}"><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['label'] }}</span></a>
        @endforeach
    </nav>
    <div class="store-sidebar__divider" aria-hidden="true"></div>
    <form method="POST" action="{{ route('logout') }}" class="store-sidebar__logout-form">
        @csrf
        <button type="submit" class="store-sidebar__logout pal-pressable" data-tooltip="تسجيل الخروج">
            <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
            <span>تسجيل الخروج</span>
        </button>
    </form>
</aside>
