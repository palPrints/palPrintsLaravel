<!-- ==================================================
       FOOTER
       ================================================== -->

  <footer
    class="main-footer"
    id="contact"
  >

    <div class="footer__container">

      <div class="footer__card">

        <div class="footer__top">

          <!-- معلومات المنصة -->

          <div class="footer__col footer__col--brand">

            <div class="footer__logo">
              <a href="{{ route('home') }}" aria-label="PalPrints">
              <img
                src="{{ asset('front/assets/images/palprints-logo.png') }}"
                alt="PalPrints"
              >
              </a>
            </div>

            <p class="footer__description">
              منصة الطباعة عند الطلب
              <br>
              تربط بين المصممين والعملاء
              <br>
              لتقديم منتجات فريدة بجودة
              <br>
              عالية وتجربة استثنائية.
            </p>

            <div class="footer__socials">

              <a
                href="#"
                class="social-link"
                aria-label="Instagram"
              >
                <i
                  class="bi bi-instagram"
                  aria-hidden="true"
                ></i>
              </a>

              <a
                href="#"
                class="social-link"
                aria-label="Facebook"
              >
                <i
                  class="bi bi-facebook"
                  aria-hidden="true"
                ></i>
              </a>

              <a
                href="#"
                class="social-link"
                aria-label="Twitter"
              >
                <i
                  class="bi bi-twitter-x"
                  aria-hidden="true"
                ></i>
              </a>

              <a
                href="#"
                class="social-link"
                aria-label="TikTok"
              >
                <i
                  class="bi bi-tiktok"
                  aria-hidden="true"
                ></i>
              </a>

            </div>

          </div>

          <!-- التسوق -->

          <div class="footer__col">

            <h2 class="footer__title">
              تسوق
            </h2>

            <ul class="footer__links">
              <li><a href="#">تيشيرتات</a></li>
              <li><a href="#">هوديز</a></li>
              <li><a href="#">حقائب</a></li>
              <li><a href="#">طواقي</a></li>
              <li><a href="#">دفاتر</a></li>
              <li><a href="#">أكواب</a></li>
            </ul>

          </div>

          <!-- الروابط السريعة -->

          <div class="footer__col">

            <h2 class="footer__title">
              روابط سريعة
            </h2>

            <ul class="footer__links">
              <li><a href="{{ url('/') }}">الرئيسية</a></li>
              <li><a href="{{ url('/#designs') }}">التصاميم</a></li>
              <li><a href="{{ url('/#about') }}">عن المنصة</a></li>
              <li><a href="{{ url('/#about') }}">كيف تعمل؟</a></li>
              <li><a href="#">الأسئلة الشائعة</a></li>
              <li><a href="{{ url('/#contact') }}">تواصل معنا</a></li>
            </ul>

          </div>

          <!-- للمصممين -->

          <div class="footer__col">

            <h2 class="footer__title">
              للمصممين
            </h2>

            <ul class="footer__links">
              <li><a href="#">انضم كمصمم</a></li>
              <li><a href="#">لوحة تحكم المصمم</a></li>
              <li><a href="#">دليل المصمم</a></li>
              <li><a href="#">سياسة المصمم</a></li>
            </ul>

          </div>

          <!-- المساعدة -->

          <div class="footer__col">

            <h2 class="footer__title">
              مساعدة
            </h2>

            <ul class="footer__links">
              <li><a href="#">مركز المساعدة</a></li>
              <li><a href="#">سياسة الخصوصية</a></li>
              <li><a href="#">الشروط والأحكام</a></li>
              <li><a href="#">سياسة الإرجاع</a></li>
              <li><a href="#">تتبع الطلب</a></li>
            </ul>

          </div>

        </div>

      </div>

      <!-- الجزء السفلي للفوتر -->

      <div class="footer__bottom">
        <!-- حقوق النشر -->

        <div class="footer__copyright">
          جميع الحقوق محفوظة © 2026 PALPRINTS
        </div>

      </div>

    </div>

  </footer>
