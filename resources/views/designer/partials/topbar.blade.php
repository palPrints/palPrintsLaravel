@php
    $notificationIcons = [
        'design_published' => 'bi-patch-check',
        'design_review' => 'bi-hourglass-split',
        'design_rejected' => 'bi-x-octagon',
        'withdrawal' => 'bi-cash-coin',
    ];
@endphp

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
                <span class="designer-bell-icon"><i class="bi bi-bell" aria-hidden="true"></i>@if ($designerUnreadCount > 0)<span class="designer-notification-dot" aria-hidden="true"></span>@endif</span>
            </button>
            <div class="designer-header-dropdown designer-notifications-dropdown" id="designerNotificationMenu" role="menu" hidden>
                <strong class="designer-dropdown-title">الإشعارات</strong>
                @forelse ($designerLatestNotifications as $notification)
                    <a href="{{ $notification->link ?: route('designer.notifications') }}" role="menuitem">
                        <i class="bi {{ $notificationIcons[$notification->type] ?? 'bi-bell' }}"></i>
                        <span><b>{{ $notification->title }}</b><small>{{ $notification->message }}</small></span>
                    </a>
                @empty
                    <span class="designer-dropdown-empty">لا توجد إشعارات جديدة.</span>
                @endforelse
                <a class="designer-dropdown-footer" href="{{ route('designer.notifications') }}" role="menuitem">عرض جميع الإشعارات</a>
            </div>
        </div>
    </div>
</header>
