@php
    $viewer = auth()->user();
    $toRole = fn (string $role, string $route) => $viewer && ! $viewer->hasRole($role) ? route('dashboard') : route($route);
@endphp
<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PALPRINTS</title>
    <link rel="stylesheet" href="{{ asset('front/home/css/home.css') }}?v={{ hash_file('sha256', public_path('front/home/css/home.css')) }}" />
    <link rel="stylesheet" href="{{ asset('front/shared/theme-dark.css') }}?v={{ filemtime(public_path('front/shared/theme-dark.css')) }}" />
    <script src="{{ asset('front/shared/theme-dark.js') }}?v={{ filemtime(public_path('front/shared/theme-dark.js')) }}"></script>
  </head>
  <body class="home-page">
    <header class="home-admin-header">
      <a class="home-admin-header__logo" href="{{ route('home') }}" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('front/home/images/palprints-wordmark-transparent.png') }}" alt="PalPrints" />
      </a>
      <nav class="home-admin-header__nav" id="homeNavigation" aria-label="التنقل الرئيسي">
        <a class="is-active" href="{{ route('home') }}">الرئيسية</a>
        <a href="#what-to-print">المنتجات</a>
        <a href="#how-it-works">كيف نعمل</a>
        <a href="#about-palprints">عن المنصة</a>
      </nav>
      <nav class="home-admin-header__actions" aria-label="إجراءات الحساب">
        <a class="home-admin-header__button home-admin-header__button--start" href="{{ $toRole('customer', 'customer.store') }}">ابدأ الآن</a>
        <a class="home-admin-header__button home-admin-header__button--login" href="{{ route('login') }}">تسجيل الدخول</a>
        <button class="home-admin-header__menu" type="button" aria-label="فتح قائمة التنقل" aria-controls="homeNavigation" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
        </button>
      </nav>
    </header>

    <main>
      <section class="home-hero" aria-labelledby="home-hero-title">
        <img
          class="home-hero__photo"
          src="{{ asset('front/home/images/home-hero-clean-background.png') }}"
          width="1672"
          height="941"
          alt="ورشة طباعة حديثة"
        />
        <p class="home-hero__wordmark" aria-hidden="true">PalPrints</p>
        <img
          class="home-hero__subject"
          src="{{ asset('front/home/images/home-hero-man-ipad-cutout.png') }}"
          width="1671"
          height="941"
          alt="مصمم يعمل بالقلم على جهاز لوحي"
        />
        <div class="home-hero__content">
          <h1 id="home-hero-title">من الإبداع إلى منتج جاهز بين يديك</h1>
          <p>مساحة متكاملة لاكتشاف المنتجات والتصاميم، عرض الإبداعات، وتقديم خدمات الطباعة ضمن تجربة واحدة.</p>
          <a class="home-hero__cta" href="{{ $toRole('customer', 'customer.store') }}">
            <span>استكشف PALPRINTS</span>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12H4m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </a>
        </div>
      </section>

      <section class="site-stats" aria-labelledby="site-stats-title">
        <div class="site-stats__inner">
          <header class="site-stats__header">
            <h2 id="site-stats-title">إحصائيات PalPrints</h2>
            <p>مجتمع متكامل يجمع الإبداع والطباعة في مكان واحد</p>
          </header>

          <ul class="site-stats__list">
            <li class="site-stat">
              <div class="site-stat__head">
<span class="site-stat__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2" /><path d="M5.5 19.5v-1a4 4 0 0 1 4-4h5a4 4 0 0 1 4 4v1" /><circle cx="4.6" cy="9.4" r="2" /><circle cx="19.4" cy="9.4" r="2" /><path d="M1.5 17v-.6a3 3 0 0 1 3-3M22.5 17v-.6a3 3 0 0 0-3-3" /></svg>
              </span>
              <p class="site-stat__number" data-count="500" data-suffix="+">500+</p>
</div>
              <h3>مستخدم</h3>
              <p class="site-stat__text">مستخدمون يجمعهم الإبداع والطباعة في منصة واحدة.</p>
            </li>

            <li class="site-stat">
              <div class="site-stat__head">
<span class="site-stat__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 11V7.8a1.6 1.6 0 0 0-.8-1.4l-6.9-4a1.6 1.6 0 0 0-1.6 0l-6.9 4a1.6 1.6 0 0 0-.8 1.4v8a1.6 1.6 0 0 0 .8 1.4l6.9 4a1.6 1.6 0 0 0 1.6 0l1.4-.8" /><path d="M3.6 6.9 12 11.7l8.4-4.8M12 21.6v-9.9" /><circle cx="18" cy="18" r="4" /><path d="m16.2 18 1.3 1.3 2.3-2.4" /></svg>
              </span>
              <p class="site-stat__number" data-count="350" data-suffix="+">350+</p>
</div>
              <h3>طلب مكتمل</h3>
              <p class="site-stat__text">طلبات خُصّصت وطُبعت ووصلت إلى أصحابها.</p>
            </li>

            <li class="site-stat">
              <div class="site-stat__head">
<span class="site-stat__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21.5 7.2 14.6 8.6 8.4h6.8l1.4 6.2z" /><path d="M12 21.5v-8.2" /><circle cx="12" cy="12.2" r="1.3" /><rect x="4.6" y="3.4" width="2.8" height="2.8" rx=".5" /><rect x="16.6" y="3.4" width="2.8" height="2.8" rx=".5" /><path d="M7.4 4.8h9.2M8.6 8.4 6.6 6.2M15.4 8.4l2-2.2" /></svg>
              </span>
              <p class="site-stat__number" data-count="120" data-suffix="+">120+</p>
</div>
              <h3>تصميم متاح</h3>
              <p class="site-stat__text">تصاميم متنوعة جاهزة للتخصيص والطباعة.</p>
            </li>
          </ul>
        </div>
      </section>

      <section class="what-to-print" id="what-to-print" aria-labelledby="what-to-print-title">
        <div class="what-to-print__inner">
          <header class="what-to-print__header">
            <p class="what-to-print__label">ما يمكنك طباعته على PALPRINTS</p>
            <h2 id="what-to-print-title">من تصميمك إلى منتجك</h2>
            <p>اكتشف المنتجات التي يمكنك تخصيصها بتصاميمك، وحوّل أفكارك إلى شيء ملموس.</p>
          </header>

          <div class="products-carousel">
            <button type="button" class="products-carousel__arrow products-carousel__arrow--prev" aria-label="السابق">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5 8 12l7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>

            <div class="products-carousel__track" id="productsCarouselTrack">
              <a class="product-card" href="{{ $toRole('customer', 'customer.tshirts') }}">
                <span class="product-card__media">
                  <img src="{{ asset('front/home/images/products/tshirt-navy-abstract.png') }}" alt="تيشيرت كحلي بطباعة هندسية ملونة" loading="lazy" />
                </span>
                <span class="product-card__name">تيشيرتات</span>
              </a>

              <a class="product-card" href="{{ $toRole('customer', 'customer.hoodies') }}">
                <span class="product-card__media">
                  <img src="{{ asset('front/home/images/products/hoodie-black-art.png') }}" alt="هودي أسود بطباعة فنية ملونة" loading="lazy" />
                </span>
                <span class="product-card__name">هوديز</span>
              </a>

              <a class="product-card" href="{{ $toRole('customer', 'customer.mugs') }}">
                <span class="product-card__media">
                  <img src="{{ asset('front/home/images/mugs/cat-good-day.png') }}" alt="أكواب" loading="lazy" />
                </span>
                <span class="product-card__name">أكواب</span>
              </a>

              <a class="product-card" href="{{ $toRole('customer', 'customer.store') }}">
                <span class="product-card__media">
                  <img src="{{ asset('front/home/images/products/tote-bag-botanical.png') }}" alt="حقيبة قماشية بنقشة زهور وألوان ترابية" loading="lazy" />
                </span>
                <span class="product-card__name">حقائب قماشية</span>
              </a>

              <a class="product-card" href="{{ $toRole('customer', 'customer.store') }}">
                <span class="product-card__media">
                  <img src="{{ asset('front/home/images/products/notebook-botanical.png') }}" alt="دفتر حلزوني بنقشة نباتية وألوان باستيل" loading="lazy" />
                </span>
                <span class="product-card__name">دفاتر</span>
              </a>

              <a class="product-card" href="{{ $toRole('customer', 'customer.store') }}">
                <span class="product-card__media">
                  <img src="{{ asset('front/home/images/products/poster-botanical.png') }}" alt="بوستر نباتي بألوان ترابية ومشابك سوداء" loading="lazy" />
                </span>
                <span class="product-card__name">بوسترات</span>
              </a>

              <a class="product-card" href="{{ $toRole('customer', 'customer.store') }}">
                <span class="product-card__media">
                  <img src="{{ asset('front/home/images/products/phone-case-floral.png') }}" alt="كفر موبايل مزين بزهور وردية وبنفسجية" loading="lazy" />
                </span>
                <span class="product-card__name">كفرات موبايل</span>
              </a>

              <a class="product-card" href="{{ $toRole('customer', 'customer.stickers') }}">
                <span class="product-card__media product-card__media--sticker">
                  <img src="{{ asset('front/home/images/stickers/illustrated/cherry-bow.png') }}" alt="ستيكر كرز مع فيونكة وردية" loading="lazy" />
                </span>
                <span class="product-card__name">ستيكرات</span>
              </a>
            </div>

            <button type="button" class="products-carousel__arrow products-carousel__arrow--next" aria-label="التالي">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>
          </div>
        </div>
      </section>

      <section class="partner-cta" id="how-it-works" aria-label="كيف تعمل PALPRINTS">
        <div class="partner-cta__inner">
          <h2 class="partner-cta__video-title">شاهد كيف تعمل PALPRINTS</h2>

          <div class="partner-cta__video">
            <video controls playsinline preload="metadata" aria-label="كيف تعمل منصة PALPRINTS">
              <source src="{{ asset('front/home/videos/PalPrints_How_It_Works.mp4') }}" type="video/mp4" />
              متصفحك لا يدعم تشغيل الفيديو.
            </video>
          </div>

          <article class="partner-card partner-card--designer">
            <div class="partner-card__content">
              <h2>ارفع تصاميمك مرة،<br />واربح من كل قطعة تنباع</h2>
              <p>بدون مخزون وبدون معدات طباعة. أنت تصمم والمنصة تتكفل بالطباعة والتوصيل والدفع.</p>

              <ul class="partner-card__benefits">
                <li>صمّم مرة، وبيعتك وإيراداتك تكبر</li>
                <li>لوحة أرباح ومبيعات لحظية</li>
                <li>تصاميمك على منتجات مختلفة</li>
                <li>إشعار فوري عند اعتماد التصميم</li>
              </ul>

              <div class="partner-card__actions">
                <a class="partner-card__button partner-card__button--primary" href="{{ $toRole('designer', 'designer.designs.create') }}">
                  ابدأ التصميم مجانًا
                  <span aria-hidden="true">↗</span>
                </a>
                <a class="partner-card__button partner-card__button--ghost" href="{{ $toRole('designer', 'designer.dashboard') }}">كيف تُحسب الأرباح؟</a>
              </div>
            </div>

            <div class="partner-card__photo" aria-hidden="true">
              <img
                src="{{ asset('front/home/images/designer-partner-cutout.png') }}"
                srcset="{{ asset('front/home/images/designer-partner-cutout-640.png') }} 640w, {{ asset('front/home/images/designer-partner-cutout.png') }} 1122w"
                sizes="(max-width: 760px) 50vw, 410px"
                width="1122"
                height="1402"
                alt=""
                loading="lazy"
              />
            </div>
          </article>

          <article class="partner-card partner-card--printer">
            <h2>عندك مطبعة؟<br />استقبل طلبات جاهزة للتنفيذ</h2>
            <p>بنرسلك بس الطلبات على المنتجات اللي بتدعمها مطبعتك، مع ملفات الطباعة وبيانات الشحن.</p>

            <dl class="partner-card__stats">
              <div><dt>18</dt><dd>طلب يومي تجريبي</dd></div>
              <div><dt>+1,200</dt><dd>طلب شهريًا</dd></div>
              <div><dt>48h</dt><dd>متوسط وقت التنفيذ</dd></div>
            </dl>

            <a class="partner-card__button partner-card__button--blue" href="{{ route('register') }}">سجّل مطبعتك</a>
          </article>
        </div>
      </section>

      <section class="about-us" id="about-palprints" aria-labelledby="about-us-title">
        <div class="about-us__inner">
          <div class="about-us__content">
            <p class="about-us__eyebrow">من نحن؟</p>
            <h2 id="about-us-title">ثلاثة أطراف… في منصة واحدة</h2>
            <p class="about-us__text">PalPrints منصة متكاملة للطباعة حسب الطلب، تجمع العملاء والمصممين والمطابع في مكان واحد؛ ليختار العميل ويخصّص منتجه، ويعرض المصمم إبداعه، وتحوّل المطبعة الأفكار إلى منتجات مطبوعة بجودة واحترافية.</p>
            <ul class="about-us__cards" aria-label="أطراف منصة PalPrints">
              <li class="about-card">
                <span class="about-card__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l-1 12H6L5 8Z" /><path d="M9 8V6.5a3 3 0 0 1 6 0V8" /></svg>
                </span>
                <h3>العملاء</h3>
                <p>يختارون منتجهم ويخصّصونه، أو يطلبون تصميمًا جاهزًا ليصلهم مطبوعًا.</p>
              </li>
              <li class="about-card">
                <span class="about-card__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21.5 7.2 14.6 8.6 8.4h6.8l1.4 6.2z" /><path d="M12 21.5v-8.2" /><circle cx="12" cy="12.2" r="1.3" /><rect x="4.6" y="3.4" width="2.8" height="2.8" rx=".5" /><rect x="16.6" y="3.4" width="2.8" height="2.8" rx=".5" /><path d="M7.4 4.8h9.2M8.6 8.4 6.6 6.2M15.4 8.4l2-2.2" /></svg>
                </span>
                <h3>المصممون</h3>
                <p>يعرضون إبداعهم ويبيعونه على منتجات متنوعة، دون مخزون أو معدات.</p>
              </li>
              <li class="about-card">
                <span class="about-card__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9V3.5h10V9" /><rect x="3.5" y="9" width="17" height="8" rx="2" /><path d="M7 14h10v6.5H7z" /></svg>
                </span>
                <h3>المطابع</h3>
                <p>تستقبل طلبات جاهزة للتنفيذ وتحوّل الأفكار إلى منتجات مطبوعة بجودة واحترافية.</p>
              </li>
            </ul>
            <a class="about-us__button" href="#how-it-works">
              <span>تعرّف على PalPrints</span>
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12H4m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </a>
          </div>
        </div>
      </section>
    </main>

    <footer class="home-footer" aria-label="تذييل الموقع">
      <div class="home-footer__inner">
        <div class="home-footer__content">
          <section class="home-footer__brand" aria-label="عن PALPRINTS">
            <a class="home-footer__wordmark" href="{{ route('home') }}" aria-label="PALPRINTS - الصفحة الرئيسية">
              <img src="{{ asset('front/home/images/palprints-wordmark-approved.png') }}" alt="PALPRINTS" />
            </a>
            <p>منصة فلسطينية للطباعة عند الطلب، تجمع المصممين والمطابع والعملاء بشراكات التوصيل في مكان واحد.</p>
            <div class="home-footer__highlights" aria-label="مميزات PALPRINTS">
              <span>طباعة حسب الطلب</span>
              <span>دعم المصممين المحليين</span>
              <span>توصيل إلى بابك</span>
            </div>
          </section>

          <nav class="home-footer__nav" aria-label="روابط تذييل الموقع">
            <section class="home-footer__column">
              <h2>تسوّق</h2>
              <ul>
                <li><a href="{{ $toRole('customer', 'customer.store') }}">كل التصاميم</a></li>
                <li><a href="{{ $toRole('customer', 'customer.store') }}">تيشيرتات وهوديز</a></li>
                <li><a href="{{ $toRole('customer', 'customer.mugs') }}">أكواب وهدايا</a></li>
                <li><a href="{{ $toRole('customer', 'customer.store') }}">الطباعة علينا</a></li>
              </ul>
            </section>

            <section class="home-footer__column">
              <h2>انضم إلينا</h2>
              <ul>
                <li><a href="{{ route('register') }}">سجّل كمصمم</a></li>
                <li><a href="{{ route('register') }}">سجّل مطبعتك</a></li>
                <li><a href="{{ route('login') }}">شركاء التوصيل</a></li>
              </ul>
            </section>

            <section class="home-footer__column">
              <h2>المساعدة</h2>
              <ul>
                <li><a href="{{ $toRole('customer', 'customer.orders') }}">تتبّع طلبي</a></li>
                <li><a href="{{ $toRole('customer', 'customer.support') }}">سياسة الاسترجاع</a></li>
                <li><a href="{{ $toRole('customer', 'customer.support') }}">الأسئلة الشائعة</a></li>
                <li><a href="{{ $toRole('customer', 'customer.support') }}">تواصل معنا</a></li>
              </ul>
            </section>

            <section class="home-footer__column">
              <h2>معلومات</h2>
              <ul>
                <li><a href="{{ $toRole('customer', 'customer.support') }}">سياسة الخصوصية</a></li>
                <li><a href="{{ $toRole('customer', 'customer.support') }}">الشروط والأحكام</a></li>
                <li><a href="{{ $toRole('customer', 'customer.support') }}">الشحن والتوصيل</a></li>
              </ul>
            </section>
          </nav>
        </div>

        <div class="home-footer__bottom">
          <p>بالإبداع شراكة دائمة · © 2026 PALPRINTS</p>
          <div class="home-footer__payments" aria-label="وسائل الدفع المتاحة" dir="ltr">
            <span>VISA</span>
            <span>Mastercard</span>
            <span>PalPay</span>
            <span>Jawwal Pay</span>
          </div>
        </div>
      </div>
    </footer>

    <script src="{{ asset('front/home/js/home.js') }}?v={{ filemtime(public_path('front/home/js/home.js')) }}"></script>
  @include('partials.page-loader')
  </body>
</html>
