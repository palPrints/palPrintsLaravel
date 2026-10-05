{{--
    Ported from palPrintFront/custProfile.html. The original page had its own
    standalone sidebar/topbar/footer shell; here it's fitted into the site's
    shared storefront header/sidebar (customer.layouts.app) instead, same as
    the orders page. Name/email/phone/avatar are wired to the users table via
    Customer\ProfileController. City/address/notes stay as demo placeholders
    — the users table has no columns for them yet.
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

        @if(session('status') === 'verification-link-sent' || session('verification_sent'))
            <div class="alert-success page-alert">أرسلنا رابط التوثيق إلى بريدك الإلكتروني ({{ $customer->email }}). افتحي الرسالة واضغطي على الرابط. إذا لم تجديها، افحصي مجلد الرسائل غير المرغوبة (Spam).</div>
        @elseif(session('status') === 'email-verified')
            <div class="alert-success page-alert">تم توثيق بريدك الإلكتروني بنجاح.</div>
        @endif

        @if(blank($customer->phone))
            <div class="alert-info page-alert">أكملي رقم هاتفك لتسريع تجهيز طلباتك القادمة.</div>
        @endif

        @php
            $nameParts = preg_split('/\s+/', trim($customer->name)) ?: [];
            $initials = mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[1] ?? '', 0, 1);
            $avatarUrl = $customer->avatar_path ? asset('storage/' . $customer->avatar_path) : null;
            $hasEditErrors = $errors->hasAny(['fullName', 'email', 'phone', 'avatar']);
        @endphp

        <section class="profile-summary app-card">
            <div class="identity">
                <div class="avatar-wrap">
                    <div class="avatar{{ $avatarUrl ? ' has-image' : '' }}" id="avatar">
                        <span>{{ $initials !== '' ? $initials : 'ع م' }}</span>
                        <img id="avatarImage" alt="صورة الملف الشخصي" @if($avatarUrl) src="{{ $avatarUrl }}" @endif>
                    </div>
                    <label for="avatarInput" class="avatar-camera" aria-label="اختيار صورة شخصية"><i class="bi bi-camera"></i></label>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" form="profileForm" hidden>
                </div>
                <div class="identity-text">
                    <h2 id="displayName">{{ $customer->name }}</h2>
                    <p>عميل PalPrints</p>
                    <span>أهلاً بك في ملفك الشخصي.</span>
                    <div class="email-status">
                        @if($customer->email_verified_at)
                            <span class="badge-success"><i class="bi bi-patch-check-fill"></i> البريد الإلكتروني موثّق</span>
                        @else
                            <span class="badge-warning"><i class="bi bi-exclamation-circle-fill"></i> البريد الإلكتروني غير موثّق</span>
                            <form method="POST" action="{{ route('verification.send') }}" class="email-status__form">
                                @csrf
                                <button type="submit" class="email-status__button"><i class="bi bi-envelope-check" aria-hidden="true"></i> وثّقيه الآن</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <div class="profile-view app-card" id="profileView" @if($hasEditErrors) hidden @endif>
            <section class="form-section" aria-labelledby="personalTitle">
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-person"></i></span>
                    <div><h2 id="personalTitle">البيانات الشخصية</h2><p>معلومات التواصل الخاصة بك.</p></div>
                    <button type="button" class="btn-brand-outline btn-sm profile-view__edit" id="profileEditButton"><i class="bi bi-pencil" aria-hidden="true"></i> تعديل البيانات</button>
                </div>

                <div class="field-grid">
                    <div class="field"><span class="pp-label">الاسم الكامل</span><p class="pp-value" id="viewFullName">{{ $customer->name }}</p></div>
                    <div class="field"><span class="pp-label">البريد الإلكتروني</span><p class="pp-value" id="viewEmail" dir="ltr">{{ $customer->email }}</p></div>
                    <div class="field"><span class="pp-label">رقم الهاتف</span><p class="pp-value {{ $customer->phone ? '' : 'pp-value--muted' }}" id="viewPhone">{{ $customer->phone ?: 'لم يتم إدخال رقم الهاتف بعد' }}</p></div>
                    <div class="field"><span class="pp-label">المدينة</span><p class="pp-value" id="viewCity">غزة</p></div>
                </div>
            </section>

            <div class="form-divider"></div>

            <section class="form-section" aria-labelledby="addressTitle">
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-geo-alt"></i></span>
                    <div><h2 id="addressTitle">عنوان التوصيل</h2><p>لتصلك طلباتك بسهولة.</p></div>
                </div>

                <div class="field-grid one-column">
                    <div class="field"><span class="pp-label">العنوان بالتفصيل</span><p class="pp-value pp-value--muted" id="viewAddress">لم يتم إدخال العنوان بعد</p></div>
                    <div class="field"><span class="pp-label">ملاحظات إضافية</span><p class="pp-value pp-value--muted" id="viewNotes">لا توجد ملاحظات</p></div>
                </div>
            </section>
        </div>

        <form class="profile-form app-card" id="profileForm" method="POST" action="{{ route('customer.profile.update') }}" enctype="multipart/form-data" @unless($hasEditErrors) hidden @endunless>
            @csrf
            @method('PATCH')
            <section class="form-section" aria-labelledby="personalTitle">
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-person"></i></span>
                    <div><h2 id="personalTitle">البيانات الشخصية</h2><p>معلومات التواصل الخاصة بك.</p></div>
                </div>

                <div class="field-grid">
                    <div class="field">
                        <label class="pp-label" for="fullName">الاسم الكامل</label>
                        <input id="fullName" class="pp-input" name="fullName" type="text" value="{{ old('fullName', $customer->name) }}" required>
                        @error('fullName') <span class="pp-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label class="pp-label" for="email">البريد الإلكتروني</label>
                        <input id="email" class="pp-input" name="email" type="email" value="{{ old('email', $customer->email) }}" dir="ltr" required>
                        @error('email') <span class="pp-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label class="pp-label" for="phone">رقم الهاتف</label>
                        <input id="phone" class="pp-input{{ $errors->has('phone') ? ' is-invalid' : '' }}" name="phone" type="tel" inputmode="numeric" maxlength="10" pattern="05[69][0-9]{7}" title="يجب أن يبدأ رقم الهاتف بـ 059 أو 056 ويتكوّن من 10 أرقام." value="{{ old('phone', $customer->phone) }}" placeholder="059xxxxxxx">
                        @error('phone') <span class="pp-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field"><label class="pp-label" for="city">المدينة</label><input id="city" class="pp-input" name="city" type="text" value="غزة"></div>
                </div>
                @error('avatar') <span class="pp-error">{{ $message }}</span> @enderror
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
                <button type="button" class="btn-brand-outline" id="profileCancelButton">تراجع</button>
            </div>
        </form>
    </section>

    <div class="toast" id="toast" role="status" aria-live="polite" hidden data-flash="{{ session('status') === 'profile-updated' ? '1' : '' }}"><i class="bi bi-check-circle-fill"></i><span>تم حفظ التغييرات بنجاح</span></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/profile.js') }}?v={{ filemtime(public_path('front/js/customer/profile.js')) }}"></script>
@endpush
