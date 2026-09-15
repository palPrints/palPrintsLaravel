<header class="topbar">
    <a class="header-logo" href="{{ route('home') }}" aria-label="الصفحة الرئيسية">
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
                <span class="counter notification-counter" id="notificationCounter" hidden>0</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown" role="menu" aria-label="قائمة الإشعارات" hidden>
                <div class="notification-dropdown-header"><h3>الإشعارات</h3></div>
                <ul class="notification-list" id="notificationList">
                    <li class="notification-empty">لا توجد إشعارات بعد</li>
                </ul>
            </div>
        </div>
    </div>
</header>
