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
                <a href="#" data-soon role="menuitem"><i class="bi bi-person" aria-hidden="true"></i><span>الملف الشخصي</span></a>
                <a href="#" data-soon role="menuitem"><i class="bi bi-gear" aria-hidden="true"></i><span>إعدادات الحساب</span></a>
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
            <div class="notification-dropdown admin-notification-dropdown" id="notificationDropdown" role="menu" aria-label="قائمة الإشعارات" hidden>
                <div class="notification-dropdown-header">
                    <h3>الإشعارات</h3>
                </div>
                <ul class="notification-list" id="notificationList">
                    @forelse ($notifications as $notification)
                        <li>
                            <a class="notification-link" href="{{ $notification->link ?: '#' }}" @if (! $notification->link) tabindex="-1" @endif>
                                <span class="notification-item-icon is-blue"><i class="bi {{ \App\Support\AdminNotifier::CATEGORIES[\App\Support\AdminNotifier::categoryOf($notification->type)]['icon'] ?? 'bi-bell' }}" aria-hidden="true"></i></span>
                                <div><strong>{{ $notification->title }}</strong><small>{{ $notification->created_at->locale('ar')->diffForHumans() }}</small></div>
                            </a>
                        </li>
                    @empty
                        <li class="notification-empty">لا توجد إشعارات حاليًا.</li>
                    @endforelse
                </ul>
                <a class="notification-view-all" href="{{ route('admin.notifications') }}">عرض كل الإشعارات</a>
            </div>
        </div>
    </div>
</header>
