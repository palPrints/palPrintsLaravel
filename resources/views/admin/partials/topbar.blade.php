@php
    $notifications = $adminNotifications ?? collect();
    $unreadNotificationsCount = $adminUnreadCount ?? 0;
@endphp

<header class="topbar admin-topbar">
    <a class="header-logo" href="{{ route('home') }}" aria-label="الصفحة الرئيسية">
        <span class="admin-logo-full">
            <img src="{{ asset('front/assets/images/admin/palprints-wordmark-transparent.png') }}" alt="PalPrints">
        </span>
        <span class="admin-logo-compact" aria-hidden="true">
            <img src="{{ asset('front/assets/images/admin/logoWithoutBackGround.png') }}" alt="">
        </span>
    </a>

    <button class="icon-button menu-button" id="menuButton" type="button" aria-label="فتح القائمة" aria-controls="adminSidebar" aria-expanded="false">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <label class="search-box admin-search-box">
        <input id="dashboardSearch" type="search" placeholder="ابحث..." aria-label="البحث في لوحة الإدارة" autocomplete="off">
        <i class="bi bi-search" aria-hidden="true"></i>
    </label>

    <div class="header-actions">
        <div class="account-wrap">
            <button class="icon-button account-button" id="accountButton" type="button" aria-label="قائمة حساب المدير" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-person" aria-hidden="true"></i>
            </button>
            <div class="account-dropdown" id="accountDropdown" role="menu" aria-label="قائمة حساب المدير" hidden>
                <a href="{{ route('admin.profile') }}" role="menuitem"><i class="bi bi-person" aria-hidden="true"></i><span>الملف الشخصي</span></a>
                <a href="{{ route('admin.settings') }}" role="menuitem"><i class="bi bi-gear" aria-hidden="true"></i><span>إعدادات الحساب</span></a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-link" role="menuitem"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>تسجيل الخروج</span></button>
                </form>
            </div>
        </div>

        <div class="notification-wrap">
            <button class="icon-button notification-button" id="notificationButton" type="button" aria-label="الإشعارات، {{ $unreadNotificationsCount }} إشعارات جديدة" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="counter notification-counter" id="notificationCounter" @if ($unreadNotificationsCount === 0) hidden @endif>{{ $unreadNotificationsCount }}</span>
            </button>
            <div class="notification-dropdown admin-notification-dropdown pp-notif-host" id="notificationDropdown" role="menu" aria-label="قائمة الإشعارات" hidden>
                @include('partials.notification-panel', ['items' => $notifications, 'readRoute' => 'admin.notifications.read', 'allUrl' => route('admin.notifications'), 'unread' => $unreadNotificationsCount, 'listId' => 'notificationList'])
            </div>
        </div>
    </div>
</header>
