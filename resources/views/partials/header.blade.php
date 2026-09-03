<!-- ==================================================
       NAVBAR
       ================================================== -->

  <header
    class="navbar"
    id="navbar"
  >

    <div class="navbar__container">

      <!-- الشعار -->

      <a
        href="{{ url('/') }}"
        class="navbar__logo"
        aria-label="PalPrints"
      >
        <img
          src="{{ asset('front/assets/images/palprints-logo.png') }}"
          alt="PalPrints Logo"
          class="logo-img"
        >
      </a>

      <!-- روابط التنقل -->

      <nav
        class="navbar__menu"
        id="navMenu"
        aria-label="القائمة الرئيسية"
      >

        <button
          type="button"
          class="navbar__menu-close"
          aria-label="إغلاق القائمة"
        >
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>

        <a
          href="{{ url('/') }}"
          class="nav-link active"
          data-ar="الرئيسية"
          data-en="Home"
        >
          الرئيسية
        </a>

        <a
          href="{{ url('/#designs') }}"
          class="nav-link"
          data-ar="التصاميم"
          data-en="Designs"
        >
          التصاميم
        </a>

        <a
          href="{{ url('/#products') }}"
          class="nav-link"
          data-ar="المنتجات"
          data-en="Products"
        >
          المنتجات
        </a>

        <a
          href="{{ url('/#about') }}"
          class="nav-link"
          data-ar="عن المنصة"
          data-en="About"
        >
          عن المنصة
        </a>

        <a
          href="{{ url('/#contact') }}"
          class="nav-link"
          data-ar="تواصل معنا"
          data-en="Contact"
        >
          تواصل معنا
        </a>

        <div class="navbar__mobile-actions">

          <div
            class="navbar__mobile-icons"
            aria-label="إجراءات سريعة"
          >
            <button
              type="button"
              class="navbar__mobile-icon"
              data-mobile-action="language"
              aria-label="تغيير اللغة"
            >
              <i class="bi bi-globe2" aria-hidden="true"></i>
            </button>

            <button
              type="button"
              class="navbar__mobile-icon"
              data-mobile-action="search"
              aria-label="بحث"
            >
              <i class="bi bi-search" aria-hidden="true"></i>
            </button>

          </div>

          <div class="navbar__mobile-auth">
            <a href="{{ route('login') }}" class="btn btn-secondary">تسجيل الدخول</a>
            <a href="{{ route('register') }}" class="btn btn-primary">إنشاء حساب</a>
          </div>

        </div>

      </nav>

      <!-- أزرار الهيدر -->

      <div class="navbar__actions">

        <div class="icons-group">

          <!-- تغيير اللغة -->

          <button
            type="button"
            id="lang-toggle"
            class="icon-btn lang-btn"
            aria-label="تغيير اللغة"
          >
            <i
              class="bi bi-globe2"
              aria-hidden="true"
            ></i>

            <span
              class="lang-text"
              id="lang-text"
            >
              EN
            </span>
          </button>

          <!-- البحث -->

          <button
            type="button"
            class="icon-btn header-search-btn"
            aria-label="بحث"
          >
            <i
              class="bi bi-search"
              aria-hidden="true"
            ></i>
          </button>

          <!-- تسجيل الدخول -->

          <a
            href="{{ route('login') }}"
            class="btn btn-secondary"
            data-ar="تسجيل الدخول"
            data-en="Log In"
          >
            تسجيل الدخول
          </a>

          <!-- إنشاء حساب -->

          <a
            href="{{ route('register') }}"
            class="btn btn-primary"
            data-ar="إنشاء حساب"
            data-en="Sign Up"
          >
            إنشاء حساب
          </a>

        </div>

      </div>

      <!-- زر القائمة للهاتف -->

      <button
        type="button"
        class="hamburger"
        id="hamburger"
        aria-label="فتح القائمة"
        aria-controls="navMenu"
        aria-expanded="false"
      >
        <i
          class="bi bi-list"
          aria-hidden="true"
        ></i>
      </button>

    </div>

  </header>
