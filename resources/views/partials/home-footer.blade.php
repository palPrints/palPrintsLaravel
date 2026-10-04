{{-- فوتر الصفحة الرئيسية وصفحاتها التعريفية. يحتاج $toRole من الصفحة المضمِّنة. --}}
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
            <li><a href="{{ $toRole('customer', 'customer.tshirts') }}">تيشيرتات</a></li>
            <li><a href="{{ $toRole('customer', 'customer.hoodies') }}">هوديز</a></li>
            <li><a href="{{ $toRole('customer', 'customer.mugs') }}">أكواب وهدايا</a></li>
            <li><a href="{{ $toRole('customer', 'customer.paperPrinting') }}">الطباعة علينا</a></li>
          </ul>
        </section>

        <section class="home-footer__column">
          <h2>انضم إلينا</h2>
          <ul>
            <li><a href="{{ route('register', ['account_type' => 'designer']) }}">سجّل كمصمم</a></li>
            <li><a href="{{ route('register', ['account_type' => 'print_provider']) }}">سجّل مطبعتك</a></li>
            <li><a href="{{ route('delivery-partners') }}">شركاء التوصيل</a></li>
          </ul>
        </section>

        <section class="home-footer__column">
          <h2>المساعدة</h2>
          <ul>
            <li><a href="{{ $toRole('customer', 'customer.orders') }}">تتبّع طلبي</a></li>
            <li><a href="{{ route('returns') }}">سياسة الاسترجاع</a></li>
            <li><a href="{{ route('faq') }}">الأسئلة الشائعة</a></li>
            <li><a href="{{ route('contact') }}">تواصل معنا</a></li>
          </ul>
        </section>

        <section class="home-footer__column">
          <h2>معلومات</h2>
          <ul>
            <li><a href="{{ route('about') }}">عن PalPrints</a></li>
            <li><a href="{{ route('privacy') }}">سياسة الخصوصية</a></li>
            <li><a href="{{ route('terms') }}">الشروط والأحكام</a></li>
            <li><a href="{{ route('shipping') }}">الشحن والتوصيل</a></li>
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
