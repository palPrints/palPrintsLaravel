{{--
    Ported from palPrintFront/custProfile.html. The original page had its own
    standalone sidebar/topbar/footer shell; here it's fitted into the site's
    shared storefront header/sidebar (customer.layouts.app) instead, same as
    the orders page. The profile fields (name/email/phone/city/address) are
    still hardcoded demo data — same "demo data" situation the other pages
    have before this is wired to a real customer/profile table.
--}}
@extends('customer.layouts.app')

@section('title', 'الملف الشخصي')
@section('meta-description', 'الملف الشخصي لعميل PalPrints')
@section('body-class', 'storefront-page profile-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/profile.css') }}?v={{ filemtime(public_path('front/css/customer/profile.css')) }}">
@endpush

@section('content')
    <section class="profile-content" aria-labelledby="pageTitle">
        <div class="page-heading">
            <h1 id="pageTitle">الملف الشخصي</h1>
            <p>حدّث بياناتك لتجربة تسوق أسهل.</p>
        </div>

        <div class="alert-info page-alert">أكملي رقم هاتفك وعنوان التوصيل لتسريع تجهيز طلباتك القادمة.</div>

        <section class="profile-summary app-card">
            <div class="identity">
                <div class="avatar-wrap">
                    <div class="avatar" id="avatar"><span>ع م</span><img id="avatarImage" alt="صورة الملف الشخصي"></div>
                    <label for="avatarInput" class="avatar-camera" aria-label="اختيار صورة شخصية"><i class="bi bi-camera"></i></label>
                    <input type="file" id="avatarInput" accept="image/*" hidden>
                </div>
                <div class="identity-text">
                    <h2 id="displayName">علا المصري</h2>
                    <p>عميل PalPrints</p>
                    <span>أهلاً بك في ملفك الشخصي.</span>
                    <span class="badge-success"><i class="bi bi-patch-check-fill"></i> البريد الإلكتروني موثّق</span>
                </div>
            </div>
        </section>

        <form class="profile-form app-card" id="profileForm">
            <section class="form-section" aria-labelledby="personalTitle">
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-person"></i></span>
                    <div><h2 id="personalTitle">البيانات الشخصية</h2><p>معلومات التواصل الخاصة بك.</p></div>
                </div>

                <div class="field-grid">
                    <div class="field"><label class="pp-label" for="fullName">الاسم الكامل</label><input id="fullName" class="pp-input" name="fullName" type="text" value="علا المصري" required></div>
                    <div class="field"><label class="pp-label" for="email">البريد الإلكتروني</label><input id="email" class="pp-input" name="email" type="email" value="ola@example.com" dir="ltr" required></div>
                    <div class="field"><label class="pp-label" for="phone">رقم الهاتف</label><input id="phone" class="pp-input" name="phone" type="tel" placeholder="أدخل رقم الهاتف"></div>
                    <div class="field"><label class="pp-label" for="city">المدينة</label><input id="city" class="pp-input" name="city" type="text" value="غزة"></div>
                </div>
            </section>

            <div class="form-divider"></div>

            <section class="form-section" aria-labelledby="addressTitle">
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-geo-alt"></i></span>
                    <div><h2 id="addressTitle">عنوان التوصيل</h2><p>لتصلك طلباتك بسهولة.</p></div>
                </div>

                <div class="field-grid one-column">
                    <div class="field"><label class="pp-label" for="address">العنوان بالتفصيل</label><input id="address" class="pp-input" name="address" type="text" placeholder="الحي، الشارع، وأقرب معلم"></div>
                    <div class="field"><label class="pp-label" for="notes">ملاحظات إضافية (اختياري)</label><textarea id="notes" class="pp-textarea" name="notes" rows="2" placeholder="أضف أي تفاصيل تساعد في الوصول إليك."></textarea></div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn-brand">حفظ التغييرات</button>
                <button type="reset" class="btn-brand-outline">تراجع</button>
            </div>
        </form>
    </section>

    <div class="toast" id="toast" role="status" aria-live="polite" hidden><i class="bi bi-check-circle-fill"></i><span>تم حفظ التغييرات بنجاح</span></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/profile.js') }}?v={{ filemtime(public_path('front/js/customer/profile.js')) }}"></script>
@endpush
