{{--
    Ported from palPrintFront/storefront.html sidebar.
    Links to custProfile.html sections (profile / orders / favorites / settings / support)
    are placeholders ("#") until those customer pages are built in Laravel.
    No brand mark and no internal header strip here on purpose — the header stays fixed
    with the single PalPrints logo above everything, including this panel, at all times
    (open or closed). Closing happens via the ☰ toggle in that header (reachable again
    once this panel is open), the backdrop tap on mobile, or Escape.
--}}
<div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>
<aside class="store-sidebar" id="storeSidebar" aria-label="القائمة الجانبية" aria-hidden="true">
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
