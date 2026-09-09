{{-- Ported as-is from palPrintFront/storefront.html header. Cart badge count and notifications are static placeholders; not wired to real data yet. --}}
<header class="store-header">
    <div class="container-palprints store-header__inner">
        <button type="button" class="icon-button sidebar-toggle" id="sidebarToggle" aria-label="فتح القائمة الجانبية" aria-expanded="false" aria-controls="storeSidebar">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <form class="store-search" id="productSearchForm" role="search">
            <span class="store-search__icon" aria-hidden="true">
                <i class="bi bi-search"></i>
            </span>
            <input type="search" id="productSearch" placeholder="ابحث عن منتج..." aria-label="ابحث عن منتج" autocomplete="off" aria-controls="productGrid">
        </form>

        <div class="store-header__actions" aria-label="إجراءات الحساب">
            <button type="button" class="icon-button" aria-label="سلة التسوق">
                <i class="bi bi-cart3" aria-hidden="true"></i>
                <span class="icon-badge" aria-hidden="true">2</span>
            </button>
            <div class="notifications-menu">
                <button type="button" class="icon-button notifications-button" id="notificationsToggle" aria-label="الإشعارات" aria-expanded="false" aria-controls="notificationsPanel">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <span class="icon-badge notifications-badge" aria-hidden="true" hidden>0</span>
                </button>
                <div class="notifications-panel" id="notificationsPanel" hidden>
                    <div class="notifications-panel__header">
                        <strong>الإشعارات</strong>
                        <button type="button" class="notifications-clear">مسح الكل</button>
                    </div>
                    <div class="notifications-list"></div>
                    <p class="notifications-empty">لا توجد إشعارات جديدة</p>
                </div>
            </div>
            <div class="profile-menu">
                <button type="button" class="icon-button" id="profileMenuToggle" aria-label="الملف الشخصي" aria-expanded="false" aria-controls="profileDropdown">
                    <i class="bi bi-person" aria-hidden="true"></i>
                </button>
                <div class="profile-dropdown" id="profileDropdown" hidden>
                    <a href="#" class="profile-dropdown__item"><i class="bi bi-person-circle" aria-hidden="true"></i><span>الملف الشخصي</span></a>
                    <a href="#" class="profile-dropdown__item"><i class="bi bi-heart" aria-hidden="true"></i><span>المفضلة</span></a>
                    <button type="button" class="profile-dropdown__item profile-dropdown__logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>تسجيل الخروج</span></button>
                </div>
            </div>
        </div>
    </div>
</header>
