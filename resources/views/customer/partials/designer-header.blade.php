{{--
    The designer's top bar for the pages a designer shares with customers (design studio, design preview).
    Same markup and ids as customer.partials.header so storefront.js drives it, but with no shop menu, search or cart:
    a designer's account has its own links and notifications.
--}}
@php
    $designerUser = auth()->user();
    $designerUnread = $designerUser?->userNotifications()->where('is_read', false)->count() ?? 0;
    $designerLatest = $designerUser ? $designerUser->userNotifications()->where('is_read', false)->latest()->limit(5)->get() : collect();
@endphp
<header class="store-header">
    <div class="store-header__inner">
        <div class="store-header__start">
            <a href="{{ route('designer.dashboard') }}" class="store-brand store-header__brand" aria-label="PalPrints">
                <span class="store-brand__logo-full">
                    <img src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="PalPrints" class="store-brand__logo">
                </span>
                <span class="store-brand__logo-compact" aria-hidden="true">
                    <img src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="">
                </span>
            </a>

            <button type="button" class="icon-button sidebar-toggle" id="sidebarToggle" aria-label="فتح القائمة الجانبية" aria-expanded="false" aria-controls="storeSidebar">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
        </div>

        <div class="store-header__end">
            <div class="store-header__actions" aria-label="إجراءات الحساب">
                <div class="profile-menu">
                    <button type="button" class="icon-button" id="profileMenuToggle" aria-label="الملف الشخصي" aria-expanded="false" aria-controls="profileDropdown">
                        <i class="bi bi-person" aria-hidden="true"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown" hidden>
                        <a href="{{ route('designer.profile') }}" class="profile-dropdown__item"><i class="bi bi-person-circle" aria-hidden="true"></i><span>الملف الشخصي</span></a>
                        <a href="{{ route('designer.settings') }}" class="profile-dropdown__item"><i class="bi bi-gear" aria-hidden="true"></i><span>الإعدادات</span></a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="profile-dropdown__item profile-dropdown__logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>تسجيل الخروج</span></button>
                        </form>
                    </div>
                </div>
                <div class="notifications-menu">
                    <button type="button" class="icon-button notifications-button" id="notificationsToggle" aria-label="الإشعارات" aria-expanded="false" aria-controls="notificationsPanel">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <span class="icon-badge notifications-badge" aria-hidden="true" @if($designerUnread === 0) hidden @endif>{{ $designerUnread > 99 ? '99+' : $designerUnread }}</span>
                    </button>
                    <div class="notifications-panel pp-notif-host" id="notificationsPanel" hidden>
                        @include('partials.notification-panel', ['items' => $designerLatest, 'readRoute' => 'designer.notifications.read', 'allUrl' => route('designer.notifications'), 'unread' => $designerUnread])
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
