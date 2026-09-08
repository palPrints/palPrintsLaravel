{{--
    Ported from palPrintFront/storefront.html footer.
    "الملف الشخصي / طلباتي / المفضلة / الدعم الفني" links are placeholders ("#")
    until those customer pages are built in Laravel.
--}}
<footer class="store-footer">
    <div class="store-footer__inner">
        <div class="store-footer__brand">
            <a href="{{ route('customer.store') }}" class="store-footer__logo-link" aria-label="PalPrints">
                <span class="store-footer__wordmark" aria-hidden="true"><span>PAL</span><strong>PRINTS</strong></span>
            </a>
            <p>منتجات مطبوعة بتصاميم مميزة وجودة تناسب ذوقك.</p>
            <div class="store-footer__social" aria-label="حسابات التواصل الاجتماعي">
                <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                <a href="#" aria-label="TikTok"><i class="bi bi-tiktok" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="store-footer__column">
            <h2>المتجر</h2>
            <a href="#products">كل المنتجات</a>
            <a href="#products">الملابس</a>
            <a href="#products">الإكسسوارات</a>
        </div>
        <div class="store-footer__column">
            <h2>حسابك</h2>
            <a href="#">الملف الشخصي</a>
            <a href="#">طلباتي</a>
            <a href="#">المفضلة</a>
        </div>
        <div class="store-footer__column">
            <h2>المساعدة</h2>
            <a href="#">الدعم الفني</a>
            <a href="{{ route('privacy') }}">سياسة الخصوصية</a>
            <a href="{{ route('terms') }}">الشروط والأحكام</a>
        </div>
    </div>
    <div class="store-footer__bottom">
        <p>© {{ now()->year }} PalPrints. جميع الحقوق محفوظة.</p>
        <span>صُنع بحب للطباعة والإبداع</span>
    </div>
</footer>
