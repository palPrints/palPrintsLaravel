{{--
    الفوتر الموحّد لكل صفحات الموقع (الرئيسية وصفحات المتجر معًا).
    المحتوى (الأعمدة والروابط) مأخوذ من فوتر الصفحة الرئيسية القديم،
    والشكل (البطاقة، الشعار النصي، الألوان) مأخوذ من فوتر المتجر —
    بستايل CSS مستقل بملف front/css/shared/site-footer.css.
--}}
<footer class="site-footer" id="contact">
    <div class="site-footer__inner">
        <div class="site-footer__brand">
            <a href="{{ route('home') }}" class="site-footer__logo-link" aria-label="PalPrints">
                <span class="site-footer__wordmark" aria-hidden="true"><span>PAL</span><strong>PRINTS</strong></span>
            </a>
            <p>منصة الطباعة عند الطلب تربط بين المصممين والعملاء لتقديم منتجات فريدة بجودة عالية وتجربة استثنائية.</p>
            <div class="site-footer__social" aria-label="حسابات التواصل الاجتماعي">
                <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x" aria-hidden="true"></i></a>
                <a href="#" aria-label="TikTok"><i class="bi bi-tiktok" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="site-footer__column">
            <h2>تسوق</h2>
            <a href="#">تيشيرتات</a>
            <a href="#">هوديز</a>
            <a href="#">حقائب</a>
            <a href="#">طواقي</a>
            <a href="#">دفاتر</a>
            <a href="#">أكواب</a>
        </div>
        <div class="site-footer__column">
            <h2>روابط سريعة</h2>
            <a href="{{ url('/') }}">الرئيسية</a>
            <a href="{{ url('/#designs') }}">التصاميم</a>
            <a href="{{ url('/#about') }}">عن المنصة</a>
            <a href="{{ url('/#about') }}">كيف تعمل؟</a>
            <a href="#">الأسئلة الشائعة</a>
            <a href="{{ url('/#contact') }}">تواصل معنا</a>
        </div>
        <div class="site-footer__column">
            <h2>للمصممين</h2>
            <a href="#">انضم كمصمم</a>
            <a href="#">لوحة تحكم المصمم</a>
            <a href="#">دليل المصمم</a>
            <a href="#">سياسة المصمم</a>
        </div>
        <div class="site-footer__column">
            <h2>مساعدة</h2>
            <a href="#">مركز المساعدة</a>
            <a href="{{ route('privacy') }}">سياسة الخصوصية</a>
            <a href="{{ route('terms') }}">الشروط والأحكام</a>
            <a href="#">سياسة الإرجاع</a>
            <a href="#">تتبع الطلب</a>
        </div>
    </div>
    <div class="site-footer__bottom">
        <p>© {{ now()->year }} PalPrints. جميع الحقوق محفوظة.</p>
        <span>صُنع بحب للطباعة والإبداع</span>
    </div>
</footer>
