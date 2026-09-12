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
                <img src="{{ asset('front/assets/images/customer/palprints-wordmark-transparent.png') }}" alt="PalPrints" class="site-footer__logo">
            </a>
            <p>من فكرة إلى واقع.<br>صنع بكل حب لكم.</p>
        </div>
        <div class="site-footer__column">
            <h2>المتجر</h2>
            <a href="{{ route('customer.store') }}#products">كل المنتجات</a>
            <a href="{{ route('customer.store') }}#products">التصنيفات</a>
            <a href="{{ route('customer.store') }}#products">الإكسسوارات</a>
            <a href="{{ route('customer.store') }}#products">الأكثر مبيعًا</a>
        </div>
        <div class="site-footer__column">
            <h2>حسابك</h2>
            <a href="#">الملف الشخصي</a>
            <a href="#">طلباتي</a>
            <a href="#">المفضلة</a>
            <a href="#">سلة المشتريات</a>
        </div>
        <div class="site-footer__column">
            <h2>المساعدة</h2>
            <a href="#">الدعم الفني</a>
            <a href="#">الأسئلة الشائعة</a>
            <a href="{{ route('privacy') }}">سياسة الخصوصية</a>
            <a href="{{ route('terms') }}">الشروط والأحكام</a>
        </div>
        <div class="site-footer__column site-footer__column--contact">
            <h2>تواصل معنا</h2>
            <a href="mailto:support@palprints.com"><i class="bi bi-envelope" aria-hidden="true"></i> support@palprints.com</a>
            <a href="tel:+970599000000"><i class="bi bi-telephone" aria-hidden="true"></i> <span dir="ltr">+970 59 900 0000</span></a>
            <span><i class="bi bi-geo-alt" aria-hidden="true"></i> رام الله، فلسطين</span>
        </div>
    </div>
    <div class="site-footer__bottom">
        <p>© {{ now()->year }} PalPrints. جميع الحقوق محفوظة.</p>
        <div class="site-footer__social" aria-label="حسابات التواصل الاجتماعي">
            <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
            <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
            <a href="#" aria-label="TikTok"><i class="bi bi-tiktok" aria-hidden="true"></i></a>
            <a href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
        </div>
    </div>
</footer>
