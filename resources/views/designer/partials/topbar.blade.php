<header class="profile-topbar designer-printshop-topbar">
    <a class="designer-printshop-brand" href="{{ route('home') }}" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="PalPrints">
    </a>

    <button type="button" class="designer-printshop-icon designer-menu-button" id="sidebarToggleButton" data-no-press aria-controls="profileSidebar" aria-expanded="true" aria-label="إغلاق القائمة الجانبية" data-i18n-aria="closeSidebar">
        <i class="bi bi-list" id="sidebarToggleIcon" aria-hidden="true"></i>
    </button>

    <form class="designer-printshop-search" method="GET" action="{{ route('designer.designs.index') }}" role="search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="ابحث في تصاميمك ..." aria-label="البحث في التصاميم">
    </form>

    <div class="designer-printshop-actions">
        <div class="designer-header-dropdown-wrap">
            <button type="button" class="designer-printshop-icon designer-dropdown-toggle" id="designerProfileMenuButton" aria-label="الحساب" aria-haspopup="true" aria-expanded="false" aria-controls="designerProfileMenu" data-no-press>
                <i class="bi bi-person" aria-hidden="true"></i>
            </button>
            <div class="designer-header-dropdown" id="designerProfileMenu" role="menu" hidden>
                <a href="{{ route('designer.profile') }}" role="menuitem"><i class="bi bi-person"></i><span>الملف الشخصي</span></a>
                <a href="{{ route('designer.settings') }}" role="menuitem"><i class="bi bi-gear"></i><span>الإعدادات</span></a>
                <span class="designer-dropdown-divider" aria-hidden="true"></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="designer-dropdown-danger" role="menuitem"><i class="bi bi-box-arrow-left"></i><span>تسجيل الخروج</span></button>
                </form>
            </div>
        </div>

        <div class="designer-header-dropdown-wrap">
            <button type="button" class="designer-printshop-icon designer-dropdown-toggle designer-notification-button" id="designerNotificationMenuButton" aria-label="الإشعارات" aria-haspopup="true" aria-expanded="false" aria-controls="designerNotificationMenu" data-no-press>
                <span class="designer-bell-icon"><i class="bi bi-bell" aria-hidden="true"></i>@if ($designerUnreadCount > 0)<span class="designer-notification-count" aria-label="{{ $designerUnreadCount }} إشعارات غير مقروءة">{{ $designerUnreadCount > 99 ? '99+' : $designerUnreadCount }}</span>@endif</span>
            </button>
            <div class="designer-header-dropdown designer-notifications-dropdown pp-notif-host" id="designerNotificationMenu" role="menu" hidden>
                @include('partials.notification-panel', ['items' => $designerLatestNotifications, 'readRoute' => 'designer.notifications.read', 'allUrl' => route('designer.notifications'), 'unread' => $designerUnreadCount])
            </div>
        </div>
    </div>
</header>
