{{--
    Cart badge count is still a placeholder; the notifications bell reads user_notifications.
    The "المتجر" mega menu lists the actual product categories from customer.store
    (resources/views/customer/store.blade.php) — every entry points at an existing
    route/anchor; items still marked data-available="false" there show "قريبًا" here.
--}}
<header class="store-header">
    <div class="store-header__inner">
        <div class="store-header__start">
            <a href="{{ route('customer.store') }}" class="store-brand store-header__brand" aria-label="PalPrints">
                <span class="store-brand__logo-full">
                    <img src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="PalPrints" class="store-brand__logo">
                </span>
                <span class="store-brand__logo-compact" aria-hidden="true">
                    <img src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="">
                </span>
            </a>

            @unless ($isGuestShop ?? false)
            <button type="button" class="icon-button sidebar-toggle" id="sidebarToggle" aria-label="فتح القائمة الجانبية" aria-expanded="false" aria-controls="storeSidebar">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            @endunless

            <form class="store-search" id="productSearchForm" role="search">
                <span class="store-search__icon" aria-hidden="true">
                    <i class="bi bi-search"></i>
                </span>
                <input type="search" id="productSearch" placeholder="ابحث عن منتج..." aria-label="ابحث عن منتج" autocomplete="off" aria-controls="productGrid">
            </form>
        </div>

        {{--
            منطقة النهاية: رابط "المتجر" (مع القائمة المنسدلة) + الأيقونات مع
            بعض. ترتيب الأيقونات من الأقل استخدامًا للأكثر أهمية: الملف
            الشخصي، الإشعارات، وأخيرًا السلة — أهم إجراء تجاري بالهيدير —
            بأقصى طرف، مفصولة بخط رفيع عن أيقونات الحساب حتى تبرز كأولوية.
        --}}
        <div class="store-header__end">
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
                        <a href="{{ route('customer.paperPrinting') }}" class="store-mega__item"><i class="bi bi-journal-bookmark" aria-hidden="true"></i><span>طباعة ورق</span></a>
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

            <div class="store-header__actions" aria-label="إجراءات الحساب">
                @if ($isGuestShop ?? false)
                    <a href="{{ route('login') }}" class="store-guest-link">تسجيل الدخول</a>
                    <a href="{{ route('register') }}" class="store-guest-link store-guest-link--primary">ابدأ الآن</a>
                @else
                <div class="profile-menu">
                    <button type="button" class="icon-button" id="profileMenuToggle" aria-label="الملف الشخصي" aria-expanded="false" aria-controls="profileDropdown">
                        <i class="bi bi-person" aria-hidden="true"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown" hidden>
                        <a href="{{ route('customer.profile') }}" class="profile-dropdown__item"><i class="bi bi-person-circle" aria-hidden="true"></i><span>الملف الشخصي</span></a>
                        <a href="{{ route('customer.favorites') }}" class="profile-dropdown__item"><i class="bi bi-heart" aria-hidden="true"></i><span>المفضلة</span></a>
                        <button type="button" class="profile-dropdown__item profile-dropdown__logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>تسجيل الخروج</span></button>
                    </div>
                </div>
                @php
                    $notificationIcons = [
                        'design_published' => 'bi-patch-check',
                        'design_review' => 'bi-hourglass-split',
                        'design_rejected' => 'bi-x-octagon',
                        'withdrawal_review' => 'bi-cash-coin',
                        'approval.submitted' => 'bi-send-check',
                        'support.reply' => 'bi-headset',
                    ];
                @endphp
                <div class="notifications-menu">
                    <button type="button" class="icon-button notifications-button" id="notificationsToggle" aria-label="الإشعارات" aria-expanded="false" aria-controls="notificationsPanel">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <span class="icon-badge notifications-badge" aria-hidden="true" @if($customerUnreadCount === 0) hidden @endif>{{ $customerUnreadCount > 99 ? '99+' : $customerUnreadCount }}</span>
                    </button>
                    <div class="notifications-panel" id="notificationsPanel" hidden>
                        <div class="notifications-panel__header">
                            <strong>الإشعارات</strong>
                        </div>
                        <div class="notifications-list">
                            @forelse($customerLatestNotifications as $notification)
                                <form method="POST" action="{{ route('customer.notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" data-no-press class="notification-item{{ $notification->is_read ? '' : ' is-unread' }}">
                                        <span class="notification-item__icon"><i class="bi {{ $notificationIcons[$notification->type] ?? 'bi-bell' }}" aria-hidden="true"></i></span>
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
                        @if(true)
                            <div class="notifications-panel__footer">
                                <a href="{{ route('customer.notifications') }}">رؤية جميع الإشعارات</a>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
                <span class="store-header__actions-divider" aria-hidden="true"></span>
                <div class="cart-menu">
                    <button type="button" class="icon-button" id="cartButton" aria-label="{{ $customerCartCount ? 'سلة التسوق، '.$customerCartCount.' منتجات' : 'سلة التسوق، فارغة' }}" aria-expanded="false" aria-controls="cartDropdown">
                        <i class="bi bi-cart3" aria-hidden="true"></i>
                        <span class="icon-badge" id="cartCount" aria-hidden="true" @if($customerCartCount === 0) hidden @endif>{{ $customerCartCount }}</span>
                    </button>
                    <div class="cart-dropdown" id="cartDropdown" hidden>
                        <div class="cart-dropdown__header">
                            <strong>سلة التسوق</strong>
                        </div>
                        <div class="cart-dropdown__list" id="cartDropdownList" @if($customerCartItems->isEmpty()) hidden @endif>
                            @foreach($customerCartItems->take(2) as $cartItem)
                                <div class="cart-dropdown__item">
                                    <div class="cart-dropdown__item-media">
                                        @if(!empty($cartItem->selected_options['mockup']))
                                            @include('customer.partials.cart-mockup', ['mockup' => $cartItem->selected_options['mockup'], 'alt' => $cartItem->product?->name ?? 'منتج'])
                                        @else
                                            <img src="{{ asset($cartItem->product?->image ?: 'front/assets/images/customer/products/1.png') }}" alt="{{ $cartItem->product?->name }}">
                                        @endif
                                    </div>
                                    <div class="cart-dropdown__item-body">
                                        <h4>{{ $cartItem->product?->name ?? 'منتج' }}</h4>
                                        <p>الكمية: {{ $cartItem->quantity }}</p>
                                    </div>
                                    <span class="cart-dropdown__item-price">{{ number_format((float) $cartItem->unit_price, 0) }} ₪</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="cart-dropdown__empty" id="cartDropdownEmpty" @if($customerCartItems->isNotEmpty()) hidden @endif><i class="bi bi-cart-x" aria-hidden="true"></i>سلتك فارغة حاليًا</p>
                        <div class="cart-dropdown__footer">
                            <a href="{{ route('customer.basket') }}" class="cart-dropdown__view">عرض السلة</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
