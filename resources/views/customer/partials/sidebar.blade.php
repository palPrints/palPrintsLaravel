{{--
    Ported from palPrintFront/storefront.html sidebar.
    Links to custProfile.html sections (profile / orders / favorites / settings / support)
    are placeholders ("#") until those customer pages are built in Laravel.
--}}
<div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>
<aside class="store-sidebar is-collapsed" id="storeSidebar" aria-label="القائمة الجانبية" aria-hidden="true">
    <div class="store-sidebar__header">
        <a class="store-brand" href="{{ route('customer.store') }}" aria-label="PalPrints">
            <span class="store-brand__wordmark" aria-hidden="true"><span>PAL</span><strong>PRINTS</strong></span>
        </a>
        <button type="button" class="icon-button sidebar-close" id="sidebarClose" aria-label="إغلاق القائمة الجانبية">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
    <nav class="store-sidebar__nav" aria-label="روابط الحساب">
        <a href="{{ route('customer.store') }}" class="store-sidebar__item active pal-pressable" aria-current="page"><i class="bi bi-shop" aria-hidden="true"></i><span>متجر PalPrint</span></a>
        <a href="#" class="store-sidebar__item pal-pressable"><i class="bi bi-person" aria-hidden="true"></i><span>الملف الشخصي</span></a>
        <a href="#" class="store-sidebar__item pal-pressable"><i class="bi bi-bag" aria-hidden="true"></i><span>طلباتي</span></a>
        <a href="#" class="store-sidebar__item pal-pressable"><i class="bi bi-heart" aria-hidden="true"></i><span>المفضلة</span></a>
        <a href="#" class="store-sidebar__item pal-pressable"><i class="bi bi-gear" aria-hidden="true"></i><span>الإعدادات</span></a>
        <a href="#" class="store-sidebar__item pal-pressable"><i class="bi bi-headset" aria-hidden="true"></i><span>الدعم الفني</span></a>
    </nav>
    <div class="store-sidebar__divider" aria-hidden="true"></div>
    <form method="POST" action="{{ route('logout') }}" class="store-sidebar__logout-form">
        @csrf
        <button type="submit" class="store-sidebar__logout pal-pressable">
            <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
            <span>تسجيل الخروج</span>
        </button>
    </form>
</aside>
