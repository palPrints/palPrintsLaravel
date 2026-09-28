@php
    // Items with a route link to their page; the rest point to "#" and show a "coming soon" notice.
    $userLinks = [
        ['icon' => 'bi-person', 'label' => 'العملاء', 'type' => 'customers', 'hash' => 'customers'],
        ['icon' => 'bi-palette', 'label' => 'المصممون', 'type' => 'designers', 'hash' => 'designers'],
        ['icon' => 'bi-printer', 'label' => 'المطابع', 'type' => 'printShops', 'hash' => 'print-shops'],
    ];
    $navItems = [
        ['route' => 'admin.designs', 'icon' => 'bi-palette', 'label' => 'إدارة التصاميم', 'badge' => $adminPendingDesigns ?? 0],
        ['route' => 'admin.products', 'icon' => 'bi-shop-window', 'label' => 'إدارة المنتجات'],
        ['route' => 'admin.orders', 'icon' => 'bi-cart3', 'label' => 'إدارة الطلبات'],
        ['route' => 'admin.payments', 'icon' => 'bi-wallet2', 'label' => 'المدفوعات والأرباح'],
        ['route' => 'admin.shipping', 'icon' => 'bi-truck', 'label' => 'إدارة الشحن'],
        ['route' => 'admin.reports', 'icon' => 'bi-bar-chart-line', 'label' => 'التقارير والإحصائيات'],
        ['route' => 'admin.settings', 'icon' => 'bi-gear', 'label' => 'إعدادات النظام'],
    ];
    $onDashboard = request()->routeIs('admin.dashboard');
@endphp

<aside class="wallet-sidebar admin-sidebar" id="adminSidebar" aria-label="القائمة الجانبية للإدارة">
    <nav aria-label="روابط لوحة الإدارة">
        <a @class(['active' => $onDashboard]) href="{{ route('admin.dashboard') }}" @if ($onDashboard) aria-current="page" @endif data-tooltip="لوحة التحكم">
            <i class="bi bi-grid" aria-hidden="true"></i>
            <span>لوحة التحكم</span>
        </a>

        <div class="admin-nav-group is-open" id="usersNavGroup">
            <button @class(['admin-nav-toggle' => true, 'active' => request()->routeIs('admin.users')]) id="usersNavToggle" type="button" aria-expanded="true" aria-controls="usersSubmenu" data-tooltip="إدارة المستخدمين">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>إدارة المستخدمين</span>
                <i class="bi bi-chevron-down admin-nav-chevron" aria-hidden="true"></i>
            </button>
            <div class="admin-submenu" id="usersSubmenu">
                @foreach ($userLinks as $link)
                    <a href="{{ route('admin.users').'#'.$link['hash'] }}" data-user-type="{{ $link['type'] }}" data-tooltip="{{ $link['label'] }}">
                        <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @foreach ($navItems as $item)
            @php($isActive = $item['route'] && request()->routeIs($item['route']))
            <a @class(['active' => $isActive]) href="{{ $item['route'] ? route($item['route']) : '#' }}" @if (! $item['route']) data-soon @endif @if ($isActive) aria-current="page" @endif data-tooltip="{{ $item['label'] }}">
                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
                @if (! empty($item['badge']))
                    <b class="admin-nav-badge" aria-label="{{ $item['badge'] }} عناصر تحتاج إلى مراجعة">{{ $item['badge'] }}</b>
                @endif
            </a>
        @endforeach

        <div class="admin-nav-separator" aria-hidden="true"></div>

        <a @class(['active' => request()->routeIs('admin.support')]) href="{{ route('admin.support') }}" @if (request()->routeIs('admin.support')) aria-current="page" @endif data-tooltip="الدعم الفني">
            <i class="bi bi-headset" aria-hidden="true"></i>
            <span>الدعم الفني</span>
        </a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="admin-logout-form" id="adminLogoutForm">
        @csrf
        <button class="logout" id="logoutButton" type="submit" data-tooltip="تسجيل الخروج">
            <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
            <span>تسجيل الخروج</span>
        </button>
    </form>
</aside>
