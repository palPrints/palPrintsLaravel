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
    <link rel="stylesheet" href="{{ asset('front/home/css/home.css') }}?v={{ filemtime(public_path('front/home/css/home.css')) }}" />
    <link rel="stylesheet" href="{{ asset('front/shared/theme-dark.css') }}?v={{ filemtime(public_path('front/shared/theme-dark.css')) }}" />
    <script src="{{ asset('front/shared/theme-dark.js') }}?v={{ filemtime(public_path('front/shared/theme-dark.js')) }}"></script>
  </head>
  <body class="home-page">
    <header class="home-admin-header">
      <a class="home-admin-header__logo" href="{{ route('home') }}" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('front/home/images/palprints-wordmark-transparent.png') }}" alt="PalPrints" />
      </a>
      <nav class="home-admin-header__nav" aria-label="التنقل الرئيسي">
        <a class="is-active" href="{{ route('home') }}">الرئيسية</a>
        <a href="#what-to-print">المنتجات</a>
        <a href="#featured-designs">اطبع لمناسبتك</a>
        <a href="#how-it-works">كيف نعمل</a>
        <a href="#about-palprints">عن المنصة</a>
      </nav>
      <nav class="home-admin-header__actions" aria-label="إجراءات الحساب">
        <a class="home-admin-header__button home-admin-header__button--start" href="{{ $toRole('customer', 'customer.store') }}">ابدأ الآن</a>
        <a class="home-admin-header__button home-admin-header__button--login" href="{{ route('login') }}">تسجيل الدخول</a>
      </nav>
    </header>

    <main>
      <section class="home-hero" aria-label="ورشة طباعة الملابس">
        <div class="home-hero__slides">
          <img
            class="home-hero__image home-hero__image--first"
            src="{{ asset('front/home/images/home-hero-printshop.jpg') }}"
            alt="فريق يعمل داخل ورشة لطباعة الملابس حسب الطلب"
          />
          <img
            class="home-hero__image home-hero__image--second"
            src="{{ asset('front/home/images/home-hero-design-studio.png') }}"
            alt="مصممة تعمل على تصاميم ملابس في استوديو إبداعي"
          />
        </div>
        <div class="home-hero__content">
          <h1>حوّل أفكارك الإبداعية إلى <span>منتجات</span> تعبّر عنك.</h1>
          <p>اكتشف، خصّص، واجعل كل تصميم أقرب إليك.</p>
          <a class="home-hero__cta" href="{{ $toRole('customer', 'customer.store') }}">
            <span>استكشف PALPRINTS</span>
            <span aria-hidden="true">←</span>
          </a>
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

      <section class="featured-designs" id="featured-designs" aria-labelledby="featured-designs-title">
        <div class="featured-designs__inner">
          <header class="featured-designs__header">
            <div>
              <h2 id="featured-designs-title">تصفح حسب مناسبتك</h2>
            </div>
          </header>

          <div class="featured-designs__grid occasion-grid">
            <a class="occasion-card" href="{{ $toRole('customer', 'customer.store') }}?occasion=wedding-engagement" aria-label="تصفح منتجات زفاف وخطوبة">
              <span class="occasion-card__media">
                <img src="{{ asset('front/home/images/occasions/wedding-engagement-v2.png') }}" alt="خاتم زفاف في أجواء حفل أنيقة" loading="lazy" />
              </span>
              <span class="occasion-card__title">زفاف وخطوبة</span>
            </a>

            <a class="occasion-card" href="{{ $toRole('customer', 'customer.store') }}?occasion=newborn" aria-label="تصفح منتجات مولود جديد">
              <span class="occasion-card__media">
                <img src="{{ asset('front/home/images/occasions/newborn-occasion-v2.png') }}" alt="يدا مولود بملابس بيضاء ناعمة" loading="lazy" />
              </span>
              <span class="occasion-card__title">مولود جديد</span>
            </a>

            <a class="occasion-card" href="{{ $toRole('customer', 'customer.store') }}?occasion=graduation" aria-label="تصفح منتجات التخرج">
              <span class="occasion-card__media">
                <img src="{{ asset('front/home/images/occasions/graduation-v2.png') }}" alt="خريجة تحمل باقة ورد وترتدي قبعة التخرج" loading="lazy" />
              </span>
              <span class="occasion-card__title">تخرج</span>
            </a>

            <a class="occasion-card" href="{{ $toRole('customer', 'customer.store') }}?occasion=birthday" aria-label="تصفح منتجات أعياد الميلاد">
              <span class="occasion-card__media">
                <img src="{{ asset('front/home/images/occasions/birthday-v2.png') }}" alt="هدية عيد ميلاد مع بطاقة وزهور" loading="lazy" />
              </span>
              <span class="occasion-card__title">أعياد ميلاد</span>
            </a>
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
              <img src="{{ asset('front/home/images/designer-partner-cutout.png') }}" alt="" loading="lazy" />
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
    </main>

    <footer class="home-footer" aria-label="تذييل الموقع">
      <div class="home-footer__inner">
        <div class="home-footer__content">
          <section class="home-footer__brand" id="about-palprints" aria-label="عن PALPRINTS">
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
  </body>
</html>
