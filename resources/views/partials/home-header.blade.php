{{-- هيدر الصفحة الرئيسية وصفحاتها التعريفية. $active: home | about | null --}}
@php($active = $active ?? null)
<header class="home-admin-header">
  <a class="home-admin-header__logo" href="{{ route('home') }}" aria-label="الصفحة الرئيسية">
    <img src="{{ asset('front/home/images/palprints-wordmark-transparent.png') }}" alt="PalPrints" />
  </a>
  <nav class="home-admin-header__nav" id="homeNavigation" aria-label="التنقل الرئيسي">
    <a @class(['is-active' => $active === 'home']) href="{{ route('home') }}" @if ($active === 'home') aria-current="page" @endif>الرئيسية</a>
    <a href="{{ route('home') }}#what-to-print">المنتجات</a>
    <a href="{{ route('home') }}#paper-printing">طباعة الورق</a>
    <a href="{{ route('home') }}#how-it-works">كيف نعمل</a>
    <a @class(['is-active' => $active === 'about']) href="{{ route('about') }}" @if ($active === 'about') aria-current="page" @endif>عن المنصة</a>
  </nav>
  <nav class="home-admin-header__actions" aria-label="إجراءات الحساب">
    @auth
      <a class="home-admin-header__button home-admin-header__button--start" href="{{ route(auth()->user()->dashboardRouteName()) }}">{{ auth()->user()->hasRole('customer') ? 'المتجر' : 'لوحة التحكم' }}</a>
      <form method="POST" action="{{ route('logout') }}" style="display:contents">
        @csrf
        <button class="home-admin-header__button home-admin-header__button--login" type="submit" style="font-family:inherit;cursor:pointer">تسجيل الخروج</button>
      </form>
    @else
      <a class="home-admin-header__button home-admin-header__button--start" href="{{ route('register') }}">ابدأ الآن</a>
      <a class="home-admin-header__button home-admin-header__button--login" href="{{ route('login') }}">تسجيل الدخول</a>
    @endauth
    <button class="home-admin-header__menu" type="button" aria-label="فتح قائمة التنقل" aria-controls="homeNavigation" aria-expanded="false">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
    </button>
  </nav>
</header>
