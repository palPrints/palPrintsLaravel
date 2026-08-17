@extends('layouts.front')

@section('title', 'PALPRINTS')
@section('body-class', 'home-page')

@push('styles')
  <link
    rel="stylesheet"
    href="{{ asset('front/css/home/main.css') }}"
  >
@endpush

@section('content')

<!-- خلفية الهيرو -->

  <div class="hero-shell">

  <!-- ==================================================
       HERO SECTION
       ================================================== -->

  <section class="hero">

    <div class="hero__container">

      <!-- محتوى الهيرو -->

      <div class="hero__content">

        <h1 class="hero__title">
          أفكارك، نطبعها
          <br>

          لك

          <span class="text-gradient">
            بجودة عالية
          </span>
        </h1>

        <p class="hero__description">
          اكتشف آلاف التصاميم المميزة واطبعها على منتجات عالية الجودة،
          سريع، سهل، ومصمم لأجلك.
        </p>

        <div class="hero__actions">

          <a
            href="#join"
            class="btn btn-hero btn-hero-outlined"
          >
            <i
              class="bi bi-person"
              aria-hidden="true"
            ></i>

            <span>
              انضم كمصمم
            </span>
          </a>

          <a
            href="#designs"
            class="btn btn-hero btn-hero-primary"
          >
            <span>
              تصفح التصاميم
            </span>

            <span
              class="browse-designs-arrow"
              aria-hidden="true"
            >
              <i class="bi bi-arrow-left"></i>
            </span>
          </a>

        </div>

      </div>

      <!-- صور الهيرو -->

      <div
        class="hero__visual"
        aria-roledescription="carousel"
        aria-label="صور منتجات PalPrints"
      >

        <span
          class="hero__platform-ring"
          aria-hidden="true"
        ></span>

        <span
          class="hero__platform-glow"
          aria-hidden="true"
        ></span>

        <div
          class="hero__slider-stage"
          data-direction="next"
        >

          <img
            src="{{ asset('front/images/home/herosectionphoto1.png') }}"
            data-hero-base="{{ asset('front/images/home/herosectionphoto1') }}"
            alt="منتجات PalPrints المطبوعة - الصورة 1"
            class="hero__img hero__slide is-active"
            draggable="false"
            decoding="async"
            fetchpriority="high"
          >

          <img
            src="{{ asset('front/images/home/herosectionphoto2.png') }}"
            data-hero-base="{{ asset('front/images/home/herosectionphoto2') }}"
            alt="منتجات PalPrints المطبوعة - الصورة 2"
            class="hero__img hero__slide"
            draggable="false"
            decoding="async"
            loading="eager"
          >

          <img
            src="{{ asset('front/images/home/herosectionphoto3.png') }}"
            data-hero-base="{{ asset('front/images/home/herosectionphoto3') }}"
            alt="منتجات PalPrints المطبوعة - الصورة 3"
            class="hero__img hero__slide"
            draggable="false"
            decoding="async"
            loading="eager"
          >

        </div>

        <!-- أزرار تحكم السلايدر -->

        <div
          class="hero__slider-controls"
          aria-label="التحكم بصور المنتجات"
        >

          <button
            type="button"
            class="hero__slider-btn hero__slider-btn--prev"
            aria-label="الصورة السابقة"
          >
            <i
              class="bi bi-arrow-left"
              aria-hidden="true"
            ></i>
          </button>

          <span
            class="hero__slider-counter"
            aria-live="polite"
            aria-atomic="true"
          >
            <span class="hero__slider-current">
              1
            </span>

            <span aria-hidden="true">
              /
            </span>

            <span class="hero__slider-total">
              3
            </span>
          </span>

          <button
            type="button"
            class="hero__slider-btn hero__slider-btn--next"
            aria-label="الصورة التالية"
          >
            <i
              class="bi bi-arrow-right"
              aria-hidden="true"
            ></i>
          </button>

        </div>

      </div>

    </div>

  </section>

  </div>

  <!-- ==================================================
       STATS SECTION
       ================================================== -->

  <section
    class="stats-section"
    aria-labelledby="stats-title"
  >

    <span
      class="stats-section__glow stats-section__glow--one"
      aria-hidden="true"
    ></span>

    <span
      class="stats-section__glow stats-section__glow--two"
      aria-hidden="true"
    ></span>

    <div class="stats__container">

      <div class="stats__header">

        <span class="stats__eyebrow">

          <i
            class="bi bi-stars"
            aria-hidden="true"
          ></i>

          <span>
            PALPRINTS بالأرقام
          </span>

        </span>

        <h2
          class="stats__title"
          id="stats-title"
        >
          أرقام تعكس ثقتكم بنا
        </h2>

        <p class="stats__description">
          مجتمع من المصممين وتصاميم جاهزة للطباعة،
          مع عناية بالجودة في كل طلب.
        </p>

      </div>

      <div class="stats__card">

        <!-- التصاميم -->

        <div
          class="stat-item"
          style="--stat-delay: 0ms"
        >

          <div
            class="stat-item__icon"
            aria-hidden="true"
          >
            <i class="bi bi-file-earmark-richtext"></i>
          </div>

          <div class="stat-item__text">

            <span
              class="stat-item__number"
              data-target="500"
              data-prefix="+"
            >
              +500
            </span>

            <span class="stat-item__label">
              تصميم متنوع
            </span>

            <span class="stat-item__meta">
              خيارات جاهزة للطباعة
            </span>

          </div>

        </div>

        <div
          class="stat-divider"
          aria-hidden="true"
        ></div>

        <!-- المصممون -->

        <div
          class="stat-item"
          style="--stat-delay: 120ms"
        >

          <div
            class="stat-item__icon"
            aria-hidden="true"
          >
            <i class="bi bi-people"></i>
          </div>

          <div class="stat-item__text">

            <span
              class="stat-item__number"
              data-target="100"
              data-prefix="+"
            >
              +100
            </span>

            <span class="stat-item__label">
              مصمم مبدع
            </span>

            <span class="stat-item__meta">
              إبداعات من مجتمعنا
            </span>

          </div>

        </div>

        <div
          class="stat-divider"
          aria-hidden="true"
        ></div>

        <!-- جودة الطباعة -->

        <div
          class="stat-item"
          style="--stat-delay: 240ms"
        >

          <div
            class="stat-item__icon"
            aria-hidden="true"
          >
            <i class="bi bi-printer"></i>
          </div>

          <div class="stat-item__text">

            <span class="stat-item__value-text">
              جودة عالية
            </span>

            <span class="stat-item__label">
              منتجات مطبوعة
            </span>

            <span class="stat-item__meta">
              عناية في كل تفصيل
            </span>

          </div>

        </div>

      </div>

    </div>

  </section>

  <!-- ==================================================
       CATEGORIES SECTION
       ================================================== -->

  <section
    class="categories-section"
    id="products"
  >

    <div class="categories__container">

      <div class="categories__header">

        <h2 class="categories__title">
          <span>
            اختر المنتج الذي يناسب أسلوبك
          </span>
        </h2>

        <p class="categories__description">
          تصفّح منتجاتنا واختر المساحة المثالية لتصميمك المفضل.
        </p>

      </div>

      <div class="categories__scroll-wrapper">

        <div class="categories__grid">

          <!-- دفاتر -->

          <a
            href="#"
            class="category-card"
          >
            <div class="category-card__icon">
              <img
                src="{{ asset('front/images/home/notebookicon.png') }}"
                alt="دفاتر"
                class="category-card__img"
              >
            </div>

            <span class="category-card__title">
              دفاتر
            </span>
          </a>

          <!-- طواقي -->

          <a
            href="#"
            class="category-card"
          >
            <div class="category-card__icon">
              <img
                src="{{ asset('front/images/home/capIcon.png') }}"
                alt="طواقي"
                class="category-card__img"
              >
            </div>

            <span class="category-card__title">
              طواقي
            </span>
          </a>

          <!-- حقائب -->

          <a
            href="#"
            class="category-card"
          >
            <div class="category-card__icon">
              <img
                src="{{ asset('front/images/home/bagicon.png') }}"
                alt="حقائب"
                class="category-card__img"
              >
            </div>

            <span class="category-card__title">
              حقائب
            </span>
          </a>

          <!-- هوديز -->

          <a
            href="#"
            class="category-card"
          >
            <div class="category-card__icon">
              <img
                src="{{ asset('front/images/home/hoddieIcon.svg') }}"
                alt="هوديز"
                class="category-card__img"
              >
            </div>

            <span class="category-card__title">
              هوديز
            </span>
          </a>

          <!-- تيشيرتات -->

          <a
            href="#"
            class="category-card"
          >
            <div class="category-card__icon">
              <img
                src="{{ asset('front/images/home/t-shirt.png') }}"
                alt="تيشيرتات"
                class="category-card__img"
              >
            </div>

            <span class="category-card__title">
              تيشيرتات
            </span>
          </a>

          <!-- طباعة ورق -->

          <a
            href="#"
            class="category-card"
          >
            <div class="category-card__icon">
              <img
                src="{{ asset('front/images/home/document.png') }}"
                alt="طباعة ورق"
                class="category-card__img"
              >
            </div>

            <span class="category-card__title">
              طباعة ورق
            </span>
          </a>

          <!-- طباعة جلديات -->

          <a
            href="#"
            class="category-card"
          >
            <div class="category-card__icon">
              <img
                src="{{ asset('front/images/home/posterposter.png') }}"
                alt="طباعة جلديات"
                class="category-card__img no-filter custom-size"
              >
            </div>

            <span class="category-card__title">
              طباعة جلديات
            </span>
          </a>

          <!-- ملصقات -->

          <a
            href="#"
            class="category-card"
          >
            <div class="category-card__icon">
              <img
                src="{{ asset('front/images/home/icons8-sticker-48.png') }}"
                alt="ملصقات"
                class="category-card__img no-filter custom-size"
              >
            </div>

            <span class="category-card__title">
              ملصقات
            </span>
          </a>

        </div>

      </div>

    </div>

  </section>

  <!-- ==================================================
       DESIGN DISCOVERY SECTION
       ================================================== -->

  <section
    class="design-discovery"
    id="designs"
    aria-labelledby="design-discovery-title"
  >

    <div class="design-discovery__container">

      <div class="design-discovery__top">

        <header class="design-discovery__header">

          <h2
            class="design-discovery__title"
            id="design-discovery-title"
          >
            <span>كل تصميم يقصّ حكاية...</span>
            <span class="design-discovery__title-accent">انتقِ الحكاية التي تجذبك</span>
          </h2>

          <p class="design-discovery__description">
            اكتشف أعمالاً صُنعت بأيدٍ مبدعة، واختر التصميم الذي يناسبك.
          </p>

        </header>

        <form
          class="design-discovery__search"
          role="search"
          aria-label="البحث في فئات التصاميم"
        >

          <label
            class="pal-sr-only"
            for="design-category-search"
          >
            البحث في فئات التصاميم
          </label>

          <input
            class="design-discovery__search-input"
            id="design-category-search"
            type="search"
            placeholder="ابحث عن تصميم، فئة أو مناسبة..."
            autocomplete="off"
          >

          <button
            class="design-discovery__search-button"
            type="submit"
            aria-label="تنفيذ البحث"
          >
            <i
              class="bi bi-search"
              aria-hidden="true"
            ></i>
          </button>

        </form>

      </div>

      <div class="design-discovery__grid">

        <article
          class="design-category-card"
          data-search="التجريدي abstract modern shapes patterns"
          style="--design-accent: #6c5ce7; --design-accent-rgb: 108 92 231"
        >
          <div
            class="design-category-card__previews"
            aria-hidden="true"
          >
            <span class="design-preview"><i class="bi bi-bezier2"></i></span>
            <span class="design-preview"><i class="bi bi-intersect"></i></span>
            <span class="design-preview"><i class="bi bi-water"></i></span>
          </div>
          <div class="design-category-card__content">
            <span class="design-category-card__icon" aria-hidden="true">
              <i class="bi bi-bezier"></i>
            </span>
            <div class="design-category-card__copy">
              <h3>التجريدي</h3>
              <p><span>98</span> <span>تصميم</span></p>
              <a href="#" class="design-category-card__link">
                <span>استكشف التصاميم</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>

        <article
          class="design-category-card"
          data-search="رسومات وإيضاحات illustrations graphics drawing art"
          style="--design-accent: #ff9f43; --design-accent-rgb: 255 159 67"
        >
          <div class="design-category-card__previews" aria-hidden="true">
            <span class="design-preview"><i class="bi bi-rocket-takeoff"></i></span>
            <span class="design-preview"><i class="bi bi-stars"></i></span>
            <span class="design-preview"><i class="bi bi-person-bounding-box"></i></span>
          </div>
          <div class="design-category-card__content">
            <span class="design-category-card__icon" aria-hidden="true">
              <i class="bi bi-palette"></i>
            </span>
            <div class="design-category-card__copy">
              <h3>رسومات وإيضاحات</h3>
              <p><span>176</span> <span>تصميم</span></p>
              <a href="#" class="design-category-card__link">
                <span>استكشف التصاميم</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>

        <article
          class="design-category-card"
          data-search="العبارات والخطوط typography quotes lettering calligraphy"
          style="--design-accent: #7057e8; --design-accent-rgb: 112 87 232"
        >
          <div class="design-category-card__previews" aria-hidden="true">
            <span class="design-preview"><i class="bi bi-quote"></i></span>
            <span class="design-preview"><i class="bi bi-type-h1"></i></span>
            <span class="design-preview"><i class="bi bi-pen"></i></span>
          </div>
          <div class="design-category-card__content">
            <span class="design-category-card__icon" aria-hidden="true">
              <i class="bi bi-vector-pen"></i>
            </span>
            <div class="design-category-card__copy">
              <h3>العبارات والخطوط</h3>
              <p><span>215</span> <span>تصميم</span></p>
              <a href="#" class="design-category-card__link">
                <span>استكشف التصاميم</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>

        <article
          class="design-category-card"
          data-search="الطبيعة والزهور nature flowers plants botanical"
          style="--design-accent: #55b960; --design-accent-rgb: 85 185 96"
        >
          <div class="design-category-card__previews" aria-hidden="true">
            <span class="design-preview"><i class="bi bi-flower1"></i></span>
            <span class="design-preview"><i class="bi bi-tree"></i></span>
            <span class="design-preview"><i class="bi bi-flower2"></i></span>
          </div>
          <div class="design-category-card__content">
            <span class="design-category-card__icon" aria-hidden="true">
              <i class="bi bi-flower1"></i>
            </span>
            <div class="design-category-card__copy">
              <h3>الطبيعة والزهور</h3>
              <p><span>128</span> <span>تصميم</span></p>
              <a href="#" class="design-category-card__link">
                <span>استكشف التصاميم</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>

        <article
          class="design-category-card"
          data-search="الطعام والمشروبات food drinks coffee restaurant"
          style="--design-accent: #ff7b46; --design-accent-rgb: 255 123 70"
        >
          <div class="design-category-card__previews" aria-hidden="true">
            <span class="design-preview"><i class="bi bi-cup-hot"></i></span>
            <span class="design-preview"><i class="bi bi-cake2"></i></span>
            <span class="design-preview"><i class="bi bi-egg-fried"></i></span>
          </div>
          <div class="design-category-card__content">
            <span class="design-category-card__icon" aria-hidden="true">
              <i class="bi bi-cup-straw"></i>
            </span>
            <div class="design-category-card__copy">
              <h3>الطعام والمشروبات</h3>
              <p><span>76</span> <span>تصميم</span></p>
              <a href="#" class="design-category-card__link">
                <span>استكشف التصاميم</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>

        <article
          class="design-category-card"
          data-search="الرياضة sports football fitness gym"
          style="--design-accent: #4f8d5b; --design-accent-rgb: 79 141 91"
        >
          <div class="design-category-card__previews" aria-hidden="true">
            <span class="design-preview"><i class="bi bi-trophy"></i></span>
            <span class="design-preview"><i class="bi bi-dribbble"></i></span>
            <span class="design-preview"><i class="bi bi-bicycle"></i></span>
          </div>
          <div class="design-category-card__content">
            <span class="design-category-card__icon" aria-hidden="true">
              <i class="bi bi-trophy"></i>
            </span>
            <div class="design-category-card__copy">
              <h3>الرياضة</h3>
              <p><span>64</span> <span>تصميم</span></p>
              <a href="#" class="design-category-card__link">
                <span>استكشف التصاميم</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>

        <article
          class="design-category-card"
          data-search="الشخصيات والكيوت characters cute kids animals cartoon"
          style="--design-accent: #ff6b81; --design-accent-rgb: 255 107 129"
        >
          <div class="design-category-card__previews" aria-hidden="true">
            <span class="design-preview"><i class="bi bi-emoji-smile"></i></span>
            <span class="design-preview"><i class="bi bi-heart"></i></span>
            <span class="design-preview"><i class="bi bi-balloon-heart"></i></span>
          </div>
          <div class="design-category-card__content">
            <span class="design-category-card__icon" aria-hidden="true">
              <i class="bi bi-emoji-smile"></i>
            </span>
            <div class="design-category-card__copy">
              <h3>الشخصيات والكيوت</h3>
              <p><span>113</span> <span>تصميم</span></p>
              <a href="#" class="design-category-card__link">
                <span>استكشف التصاميم</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>

        <article
          class="design-category-card"
          data-search="الثقافة والتراث culture heritage architecture history"
          style="--design-accent: #c18a3d; --design-accent-rgb: 193 138 61"
        >
          <div class="design-category-card__previews" aria-hidden="true">
            <span class="design-preview"><i class="bi bi-building"></i></span>
            <span class="design-preview"><i class="bi bi-moon-stars"></i></span>
            <span class="design-preview"><i class="bi bi-globe2"></i></span>
          </div>
          <div class="design-category-card__content">
            <span class="design-category-card__icon" aria-hidden="true">
              <i class="bi bi-bank"></i>
            </span>
            <div class="design-category-card__copy">
              <h3>الثقافة والتراث</h3>
              <p><span>82</span> <span>تصميم</span></p>
              <a href="#" class="design-category-card__link">
                <span>استكشف التصاميم</span>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>

      </div>

      <p
        class="design-discovery__no-results"
        role="status"
        hidden
      >
        لا توجد فئات مطابقة لبحثك.
      </p>

      <div class="design-discovery__features">

        <article class="design-discovery__feature design-discovery__feature--seasons">
          <div class="design-discovery__feature-copy">
            <h3>مواسم ومناسبات</h3>
            <p><span>96</span> <span>تصميم</span></p>
            <a href="#" class="design-category-card__link">
              <span>استكشف التصاميم</span>
              <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
          </div>
          <div class="design-discovery__season-previews" aria-hidden="true">
            <span><i class="bi bi-moon-stars"></i></span>
            <span><i class="bi bi-balloon-heart"></i></span>
            <span><i class="bi bi-gift"></i></span>
          </div>
        </article>

        <a
          href="#"
          class="design-discovery__feature design-discovery__feature--all"
        >
          <span class="design-discovery__all-icon" aria-hidden="true">
            <i class="bi bi-grid"></i>
          </span>
          <span class="design-discovery__all-copy">
            <strong>عرض جميع الفئات</strong>
            <small>تصفح كل التصنيفات المتاحة</small>
          </span>
        </a>

      </div>

    </div>

  </section>

  <!-- ==================================================
       HOW IT WORKS SECTION
       ================================================== -->

  <section
    class="how-it-works"
    id="about"
  >

    <div class="how-it-works__container">

      <div class="section-title">

        <span class="title-line"></span>

        <h2>
          كيف تعمل PALPRINTS؟
        </h2>

        <span class="title-line"></span>

      </div>

      <div class="steps__grid">

        <svg
          class="steps__wave-line"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 1000 100"
          preserveAspectRatio="none"
          aria-hidden="true"
        >

          <defs>

            <linearGradient
              id="wave-gradient-smooth"
              x1="100%"
              y1="0%"
              x2="0%"
              y2="0%"
            >
              <stop
                offset="0%"
                stop-color="#08c4cf"
              ></stop>

              <stop
                offset="33%"
                stop-color="#168bea"
              ></stop>

              <stop
                offset="66%"
                stop-color="#4d45f5"
              ></stop>

              <stop
                offset="100%"
                stop-color="#8516f3"
              ></stop>
            </linearGradient>

          </defs>

          <path
            d="M 875 50 C 790 75, 710 75, 625 50 C 540 25, 460 25, 375 50 C 290 75, 210 75, 125 50"
            fill="none"
            stroke="url(#wave-gradient-smooth)"
            stroke-width="2.5"
            stroke-dasharray="6,5"
            stroke-linecap="round"
          ></path>

          <circle
            cx="750"
            cy="68.75"
            r="5"
            fill="#168bea"
          ></circle>

          <circle
            cx="500"
            cy="31.25"
            r="5"
            fill="#4d45f5"
          ></circle>

          <circle
            cx="250"
            cy="68.75"
            r="5"
            fill="#4d45f5"
          ></circle>

        </svg>

        <!-- الخطوة الأولى -->

        <div class="step-card step-card--1">

          <div class="step-card__badge">
            1
          </div>

          <div class="step-card__icon-wrapper">
            <i
              class="bi bi-palette"
              aria-hidden="true"
            ></i>
          </div>

          <h3 class="step-card__title">
            اختر التصميم
          </h3>

          <p class="step-card__desc">
            تصفح آلاف التصاميم واختر ما يناسب ذوقك.
          </p>

        </div>

        <!-- الخطوة الثانية -->

        <div class="step-card step-card--2">

          <div class="step-card__badge">
            2
          </div>

          <div class="step-card__icon-wrapper">
            <i
              class="bi bi-box-seam"
              aria-hidden="true"
            ></i>
          </div>

          <h3 class="step-card__title">
            اختر المنتج
          </h3>

          <p class="step-card__desc">
            حدد المنتج الذي ترغب بطباعة التصميم عليه.
          </p>

        </div>

        <!-- الخطوة الثالثة -->

        <div class="step-card step-card--3">

          <div class="step-card__badge">
            3
          </div>

          <div class="step-card__icon-wrapper">
            <i
              class="bi bi-eye"
              aria-hidden="true"
            ></i>
          </div>

          <h3 class="step-card__title">
            شاهد المعاينة
          </h3>

          <p class="step-card__desc">
            عاين المنتج واختر اللون والمقاس والكمية.
          </p>

        </div>

        <!-- الخطوة الرابعة -->

        <div class="step-card step-card--4">

          <div class="step-card__badge">
            4
          </div>

          <div class="step-card__icon-wrapper">
            <i
              class="bi bi-truck"
              aria-hidden="true"
            ></i>
          </div>

          <h3 class="step-card__title">
            استلم طلبك
          </h3>

          <p class="step-card__desc">
            نقوم بطباعة طلبك وشحنه حتى يصل إليك بأمان.
          </p>

        </div>

      </div>

    </div>

  </section>

  <!-- ==================================================
       DESIGNER BANNER SECTION
       ================================================== -->

  <section
    class="designer-banner"
    id="join"
  >

    <div class="designer-banner__container">

      <div
        class="designer-banner__card"
        aria-labelledby="designer-title"
      >

        <span
          class="designer-banner__orb designer-banner__orb--cyan"
          aria-hidden="true"
        ></span>

        <span
          class="designer-banner__orb designer-banner__orb--purple"
          aria-hidden="true"
        ></span>

        <div class="designer-banner__left-side">

          <div class="designer-banner__visual">

            <span
              class="designer-banner__visual-ring"
              aria-hidden="true"
            ></span>

            <div class="designer-banner__mini-card designer-banner__mini-card--earnings">
              <span class="designer-banner__mini-icon">
                <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
              </span>

              <span>
                أرباحك تنمو
              </span>
            </div>

            <img
              src="{{ asset('front/images/home/highLaptopWithoutBackGround.jpeg') }}"
              alt="لوحة المصمم ومنتجات PALPRINTS"
              class="mockup-img"
            >

            <div class="designer-banner__mini-card designer-banner__mini-card--community">
              <span class="designer-banner__avatars" aria-hidden="true">
                <i class="bi bi-person-fill"></i>
                <i class="bi bi-person-fill"></i>
                <i class="bi bi-person-fill"></i>
              </span>

              <span class="designer-banner__mini-copy">
                <strong>+500</strong>
                <small>مصمم مبدع</small>
              </span>
            </div>

          </div>

        </div>

        <div class="designer-banner__right-side">

          <div class="designer-banner__eyebrow">
            <i class="bi bi-stars" aria-hidden="true"></i>
            <span>مساحتك للإبداع</span>
          </div>

          <h2
            class="designer-banner__title"
            id="designer-title"
          >
            <span>هل أنت مصمم؟</span>
            <span class="designer-banner__title-accent">
              حوّل إبداعك إلى دخل.
            </span>
          </h2>

          <p class="designer-banner__desc">
            اعرض تصاميمك أمام جمهور يحب الاختلاف، واترك لنا مهمة الطباعة والتغليف والتوصيل.
          </p>

          <div class="designer-banner__benefits">
            <span>
              <i class="bi bi-check2" aria-hidden="true"></i>
              ارفع تصميمك بسهولة
            </span>

            <span>
              <i class="bi bi-check2" aria-hidden="true"></i>
              نطبع ونوصل عنك
            </span>

            <span>
              <i class="bi bi-check2" aria-hidden="true"></i>
              اربح من كل طلب
            </span>
          </div>

          <div class="designer-banner__action-wrapper">

            <a
              href="#"
              class="designer-banner__btn"
            >
              <span class="btn-icon">
                <i
                  class="bi bi-person-plus"
                  aria-hidden="true"
                ></i>
              </span>

              <span class="btn-text">
                انضم كمصمم
              </span>

              <i
                class="bi bi-arrow-left designer-banner__btn-arrow"
                aria-hidden="true"
              ></i>
            </a>

            <span class="designer-banner__helper">
              الانضمام مجاني • ابدأ خلال دقائق
            </span>

          </div>

        </div>

      </div>

    </div>

  </section>

  <!-- ==================================================
       TESTIMONIALS SECTION
       ================================================== -->

  <section
    class="testimonials-section"
    id="testimonials"
    aria-labelledby="testimonials-title"
  >

    <div class="testimonials__container">

      <div class="testimonials__header">

        <p class="testimonials__eyebrow">
          ماذا يقول عملاؤنا
        </p>

        <h2
          class="testimonials__title"
          id="testimonials-title"
        >
          <span>أحبّنا الكثيرون،</span>
          <span class="testimonials__title-accent">ووثق بنا المئات.</span>
        </h2>

        <p class="testimonials__description">
          آراء حقيقية من عملاء حقيقيين أحبّوا جودة وخدمة وتجربة
          <strong>PALPRINTS.</strong>
        </p>

      </div>

      <div
        class="testimonials__grid testimonials__carousel"
        aria-roledescription="carousel"
        aria-label="آراء عملاء PALPRINTS"
        tabindex="0"
      >

        <!-- الرأي الأول -->

        <article class="testimonial-card">

          <div
            class="testimonial-card__quote-icon"
            aria-hidden="true"
          >
            ”
          </div>

          <p class="testimonial-card__text">
            جودة المنتجات تفوق التوقعات والخامات ممتازة جداً.
          </p>

          <div class="testimonial-card__author">

            <div class="testimonial-card__avatar-wrapper">
              <img
                src="{{ asset('front/images/home/user (1).png') }}"
                alt="ليلى منصور"
                class="testimonial-card__avatar"
              >
            </div>

            <div class="testimonial-card__info">

              <h3 class="testimonial-card__name">
                ليلى منصور
              </h3>

              <span class="testimonial-card__location">
                غزة - خانيونس
              </span>

            </div>

          </div>

        </article>

        <!-- الرأي الثاني -->

        <article class="testimonial-card">

          <div
            class="testimonial-card__quote-icon"
            aria-hidden="true"
          >
            ”
          </div>

          <p class="testimonial-card__text">
            تصاميمي كانت واضحة والدقة في الطباعة ممتازة جداً.
          </p>

          <div class="testimonial-card__author">

            <div class="testimonial-card__avatar-wrapper">
              <img
                src="{{ asset('front/images/home/user (1).png') }}"
                alt="محمد علاء"
                class="testimonial-card__avatar"
              >
            </div>

            <div class="testimonial-card__info">

              <h3 class="testimonial-card__name">
                محمد علاء
              </h3>

              <span class="testimonial-card__location">
                غزة - غزة
              </span>

            </div>

          </div>

        </article>

        <!-- الرأي الثالث -->

        <article class="testimonial-card">

          <div
            class="testimonial-card__quote-icon"
            aria-hidden="true"
          >
            ”
          </div>

          <p class="testimonial-card__text">
            خدمة سريعة والتوصيل كان أسرع مما توقعت، شكراً لكم!
          </p>

          <div class="testimonial-card__author">

            <div class="testimonial-card__avatar-wrapper">
              <img
                src="{{ asset('front/images/home/user (1).png') }}"
                alt="سارة خالد"
                class="testimonial-card__avatar"
              >
            </div>

            <div class="testimonial-card__info">

              <h3 class="testimonial-card__name">
                سارة خالد
              </h3>

              <span class="testimonial-card__location">
                غزة - النصيرات
              </span>

            </div>

          </div>

        </article>

        <!-- الرأي الرابع -->

        <article class="testimonial-card">

          <div
            class="testimonial-card__quote-icon"
            aria-hidden="true"
          >
            ”
          </div>

          <p class="testimonial-card__text">
            منتجات رائعة وجودة الطباعة مذهلة،
            سأطلب مرة أخرى بالتأكيد!
          </p>

          <div class="testimonial-card__author">

            <div class="testimonial-card__avatar-wrapper">
              <img
                src="{{ asset('front/images/home/user (1).png') }}"
                alt="أحمد المصري"
                class="testimonial-card__avatar"
              >
            </div>

            <div class="testimonial-card__info">

              <h3 class="testimonial-card__name">
                أحمد المصري
              </h3>

              <span class="testimonial-card__location">
                غزة - دير البلح
              </span>

            </div>

          </div>

        </article>

      </div>

      <div
        class="testimonials__controls"
        aria-label="التنقل بين آراء العملاء"
      >

        <button
          type="button"
          class="testimonials__nav-btn testimonials__nav-btn--prev"
          aria-label="الرأي السابق"
        >
          <i
            class="bi bi-arrow-left"
            aria-hidden="true"
          ></i>
        </button>

        <div
          class="testimonials__dots"
          aria-label="اختيار رأي العميل"
        ></div>

        <span
          class="testimonials__counter"
          aria-live="polite"
          aria-atomic="true"
        >
          <span class="testimonials__current">1</span>
          <span aria-hidden="true">/</span>
          <span class="testimonials__total">4</span>
        </span>

        <button
          type="button"
          class="testimonials__nav-btn testimonials__nav-btn--next"
          aria-label="الرأي التالي"
        >
          <i
            class="bi bi-arrow-right"
            aria-hidden="true"
          ></i>
        </button>

      </div>

    </div>

  </section>

@endsection

@push('scripts')
  <script src="{{ asset('front/js/master.js') }}"></script>
@endpush
