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
        @if (auth()->user()?->hasRole('designer'))
            <div class="site-footer__column">
                <h2>التصميم</h2>
                <a href="{{ route('designer.designs.create') }}">رفع تصميم جديد</a>
                <a href="{{ route('designer.designs.index') }}">تصاميمي</a>
                <a href="{{ route('designer.earnings') }}">الأرباح</a>
            </div>
            <div class="site-footer__column">
                <h2>حسابك</h2>
                <a href="{{ route('designer.dashboard') }}">لوحة التحكم</a>
                <a href="{{ route('designer.profile') }}">الملف الشخصي</a>
                <a href="{{ route('designer.settings') }}">الإعدادات</a>
            </div>
        @else
            <div class="site-footer__column">
                <h2>المتجر</h2>
                <a href="{{ route('customer.store') }}#products">كل المنتجات</a>
                <a href="{{ route('customer.tshirts') }}">تيشيرتات</a>
                <a href="{{ route('customer.hoodies') }}">هوديز</a>
                <a href="{{ route('customer.mugs') }}">أكواب</a>
                <a href="{{ route('customer.stickers') }}">ستيكرات</a>
            </div>
            <div class="site-footer__column">
                <h2>حسابك</h2>
                <a href="{{ route('customer.profile') }}">الملف الشخصي</a>
                <a href="{{ route('customer.orders') }}">طلباتي</a>
                <a href="{{ route('customer.favorites') }}">المفضلة</a>
                <a href="{{ route('customer.basket') }}">سلة المشتريات</a>
            </div>
        @endif
        <div class="site-footer__column">
            <h2>المساعدة</h2>
            <a href="{{ auth()->user()?->hasRole('designer') ? route('designer.support') : route('customer.support') }}">الدعم الفني</a>
            <a href="{{ route('faq') }}">الأسئلة الشائعة</a>
            <a href="{{ route('shipping') }}">الشحن والتوصيل</a>
            <a href="{{ route('returns') }}">سياسة الاسترجاع</a>
            <a href="{{ route('privacy') }}">سياسة الخصوصية</a>
            <a href="{{ route('terms') }}">الشروط والأحكام</a>
        </div>
        <div class="site-footer__column site-footer__column--contact">
            <h2>تواصل معنا</h2>
            @php
                $footerEmail = \App\Support\PlatformSettings::get('general', 'admin_email');
                $footerPhone = \App\Support\PlatformSettings::get('general', 'contact_phone');
            @endphp
            @if ($footerEmail)
                <a href="mailto:{{ $footerEmail }}"><i class="bi bi-envelope" aria-hidden="true"></i> {{ $footerEmail }}</a>
            @endif
            @if ($footerPhone)
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $footerPhone) }}"><i class="bi bi-telephone" aria-hidden="true"></i> <span dir="ltr">{{ $footerPhone }}</span></a>
            @endif
            <span><i class="bi bi-geo-alt" aria-hidden="true"></i> رام الله، فلسطين</span>
        </div>
    </div>
    <div class="site-footer__bottom">
        <p>© {{ now()->year }} PalPrints. جميع الحقوق محفوظة.</p>
        <div class="site-footer__social" aria-label="حسابات التواصل الاجتماعي">
            @php
                $footerSocials = [
                    'Instagram' => ['bi-instagram', \App\Support\PlatformSettings::get('site', 'instagram')],
                    'Facebook' => ['bi-facebook', \App\Support\PlatformSettings::get('site', 'facebook')],
                    'TikTok' => ['bi-tiktok', \App\Support\PlatformSettings::get('site', 'tiktok')],
                    'WhatsApp' => ['bi-whatsapp', ($wa = preg_replace('/\D/', '', (string) \App\Support\PlatformSettings::get('site', 'whatsapp'))) ? 'https://wa.me/'.$wa : null],
                ];
            @endphp
            @foreach ($footerSocials as $name => [$icon, $url])
                @if ($url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $name }}"><i class="bi {{ $icon }}" aria-hidden="true"></i></a>
                @endif
            @endforeach
        </div>
    </div>
</footer>
