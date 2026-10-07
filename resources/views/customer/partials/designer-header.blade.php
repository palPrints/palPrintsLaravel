{{--
    The designer's top bar for the pages a designer shares with customers (design studio, design preview).
    Same markup and ids as customer.partials.header so storefront.js drives it, but with no shop menu, search or cart:
    a designer's account has its own links and notifications.
--}}
@php
    $designerUser = auth()->user();
    $designerUnread = $designerUser?->userNotifications()->where('is_read', false)->count() ?? 0;
    $designerLatest = $designerUser ? $designerUser->userNotifications()->where('is_read', false)->latest()->limit(5)->get() : collect();
    $designerNotificationIcons = [
        'design_published' => 'bi-patch-check',
        'design_review' => 'bi-hourglass-split',
        'design_rejected' => 'bi-x-octagon',
        'withdrawal_review' => 'bi-cash-coin',
        'approval.submitted' => 'bi-send-check',
        'support.reply' => 'bi-headset',
    ];
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
                    <div class="notifications-panel" id="notificationsPanel" hidden>
                        <div class="notifications-panel__header">
                            <strong>الإشعارات</strong>
                        </div>
                        <div class="notifications-list">
                            @forelse($designerLatest as $notification)
                                <form method="POST" action="{{ route('designer.notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="notification-item{{ $notification->is_read ? '' : ' is-unread' }}">
                                        <span class="notification-item__icon"><i class="bi {{ $designerNotificationIcons[$notification->type] ?? 'bi-bell' }}" aria-hidden="true"></i></span>
                                        <span class="notification-item__body">
                                            <strong>{{ $notification->title }}</strong>
                                            <small>{{ $notification->message }}</small>
                                            <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->locale('ar')->diffForHumans() }}</time>
                                        </span>
                                    </button>
                                </form>
                            @empty
                                <p class="notifications-empty"><i class="bi bi-bell-slash" aria-hidden="true"></i>لا توجد إشعارات جديدة</p>
                            @endforelse
                        </div>
                        <div class="notifications-panel__footer">
                            <a href="{{ route('designer.notifications') }}">رؤية جميع الإشعارات</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
