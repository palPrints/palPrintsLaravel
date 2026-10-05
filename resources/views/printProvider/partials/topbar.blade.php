<header class="topbar">
    <a class="header-logo" href="{{ route('print-provider.dashboard') }}" aria-label="لوحة تحكم المطبعة">
        <img src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="PalPrints">
    </a>
    <button class="icon-button menu-button" id="menuButton" type="button" aria-label="فتح القائمة" aria-controls="walletSidebar" aria-expanded="false">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <label class="search-box">
        <input id="dashboardSearch" type="search" placeholder="إبحث عن منتج ..." aria-label="البحث عن منتج أو رقم طلب" autocomplete="off">
        <i class="bi bi-search" aria-hidden="true"></i>
    </label>
    <div class="header-actions">
        <div class="account-wrap">
            <button class="icon-button account-button" id="accountButton" type="button" aria-label="قائمة الحساب" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-person" aria-hidden="true"></i>
            </button>
            <div class="account-dropdown" id="accountDropdown" role="menu" aria-label="قائمة الحساب" hidden>
                <a href="{{ route('print-provider.profile') }}" role="menuitem"><i class="bi bi-person" aria-hidden="true"></i><span>الملف الشخصي</span></a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-link" role="menuitem"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>تسجيل الخروج</span></button>
                </form>
            </div>
        </div>
        <div class="notification-wrap">
            <button class="icon-button notification-button" id="notificationButton" type="button" aria-label="الإشعارات" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="counter notification-counter" id="notificationCounter" @if($providerUnreadCount === 0) hidden @endif>{{ $providerUnreadCount > 99 ? '99+' : $providerUnreadCount }}</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown" role="menu" aria-label="قائمة الإشعارات" hidden>
                <div class="notification-dropdown-header"><h3>الإشعارات</h3></div>
                <ul class="notification-list" id="notificationList">
                    @forelse($providerLatestNotifications as $notification)
                        <li>
                            <form method="POST" action="{{ route('print-provider.notifications.read', $notification) }}">
                                @csrf
                                <button type="submit" class="notification-entry is-unread" role="menuitem">
                                    <span class="notification-entry__icon"><i class="bi {{ $notification->type === 'approval.submitted' ? 'bi-send-check' : (str_starts_with((string) $notification->type, 'order') ? 'bi-bag-check' : 'bi-bell') }}" aria-hidden="true"></i></span>
                                    <span class="notification-entry__body">
                                        <strong>{{ $notification->title }}</strong>
                                        <small>{{ $notification->message }}</small>
                                        <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->locale('ar')->diffForHumans() }}</time>
                                    </span>
                                </button>
                            </form>
                        </li>
                    @empty
                        <li class="notification-empty">لا توجد إشعارات جديدة</li>
                    @endforelse
                </ul>
                <a class="notification-dropdown-footer" href="{{ route('print-provider.notifications') }}" role="menuitem">رؤية جميع الإشعارات</a>
            </div>
        </div>
    </div>
</header>
