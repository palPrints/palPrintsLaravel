{{--
    Cart badge count and notifications are static placeholders; not wired to real data yet.
    The "المتجر" mega menu lists the actual product categories from customer.store
    (resources/views/customer/store.blade.php) — every entry points at an existing
    route/anchor; items still marked data-available="false" there show "قريبًا" here.
--}}
<header class="store-header">
    <div class="store-header__inner">
        <div class="store-header__start">
            <button type="button" class="icon-button sidebar-toggle" id="sidebarToggle" aria-label="فتح القائمة الجانبية" aria-expanded="false" aria-controls="storeSidebar">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <a href="{{ route('customer.store') }}" class="store-brand store-header__brand" aria-label="PalPrints">
                <img src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="PalPrints" class="store-brand__logo">
            </a>
        </div>

        <nav class="store-nav" aria-label="التصفح الرئيسي">
            <div class="store-nav__item" id="storeNavShop">
                <a href="{{ route('customer.store') }}#products" class="store-nav__link" id="storeNavShopTrigger" aria-haspopup="true" aria-expanded="false" aria-controls="storeNavShopMenu">
                    المتجر
                    <i class="bi bi-chevron-down store-nav__chevron" aria-hidden="true"></i>
                </a>
                <div class="store-mega" id="storeNavShopMenu" aria-label="منتجات المتجر" hidden>
                    <p class="store-mega__label">متوفر الآن</p>
                    <a href="{{ route('customer.tshirts') }}" class="store-mega__item"><i class="bi bi-bag" aria-hidden="true"></i><span>تيشيرت</span></a>
                    <a href="{{ route('customer.hoodies') }}" class="store-mega__item"><i class="bi bi-bag" aria-hidden="true"></i><span>هودي</span></a>
                    <a href="{{ route('customer.mugs') }}" class="store-mega__item"><i class="bi bi-cup-hot" aria-hidden="true"></i><span>اكواب</span></a>
                    <a href="{{ route('customer.store') }}#products" class="store-mega__item"><i class="bi bi-journal-bookmark" aria-hidden="true"></i><span>طباعة ورق</span></a>
                    <a href="{{ route('customer.stickers') }}" class="store-mega__item"><i class="bi bi-journal-bookmark" aria-hidden="true"></i><span>ستيكرات</span></a>

                    <p class="store-mega__label">قريبًا</p>
                    <a href="{{ route('customer.store') }}#products" class="store-mega__item is-soon"><i class="bi bi-bag" aria-hidden="true"></i><span>قبعات</span></a>
                    <a href="{{ route('customer.store') }}#products" class="store-mega__item is-soon"><i class="bi bi-handbag" aria-hidden="true"></i><span>حقائب</span></a>
                    <a href="{{ route('customer.store') }}#products" class="store-mega__item is-soon"><i class="bi bi-handbag" aria-hidden="true"></i><span>وشاحات</span></a>
                    <a href="{{ route('customer.store') }}#products" class="store-mega__item is-soon"><i class="bi bi-handbag" aria-hidden="true"></i><span>كفرات موبايل</span></a>
                    <a href="{{ route('customer.store') }}#products" class="store-mega__item is-soon"><i class="bi bi-journal-bookmark" aria-hidden="true"></i><span>دفاتر</span></a>
                    <a href="{{ route('customer.store') }}#products" class="store-mega__item is-soon"><i class="bi bi-journal-bookmark" aria-hidden="true"></i><span>بوسترات</span></a>
                    <a href="{{ route('customer.store') }}#products" class="store-mega__item is-soon"><i class="bi bi-journal-bookmark" aria-hidden="true"></i><span>كروت افراح</span></a>
                </div>
            </div>
        </nav>

        {{--
            منطقة النهاية: البحث + الأيقونات مع بعض بدل ما يكون البحث محشور
            جنب "المتجر". ترتيب الأيقونات من الأقل استخدامًا للأكثر أهمية:
            الملف الشخصي، الإشعارات، وأخيرًا السلة — أهم إجراء تجاري بالهيدير —
            بأقصى طرف، مفصولة بخط رفيع عن أيقونات الحساب حتى تبرز كأولوية.
        --}}
        <div class="store-header__end">
            <form class="store-search" id="productSearchForm" role="search">
                <span class="store-search__icon" aria-hidden="true">
                    <i class="bi bi-search"></i>
                </span>
                <input type="search" id="productSearch" placeholder="ابحث عن منتج..." aria-label="ابحث عن منتج" autocomplete="off" aria-controls="productGrid">
            </form>

            <div class="store-header__actions" aria-label="إجراءات الحساب">
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
                <span class="store-header__actions-divider" aria-hidden="true"></span>
                <button type="button" class="icon-button" id="cartButton" aria-label="سلة التسوق">
                    <i class="bi bi-cart3" aria-hidden="true"></i>
                    <span class="icon-badge" id="cartCount" aria-hidden="true">2</span>
                </button>
            </div>
        </div>
    </div>
</header>
