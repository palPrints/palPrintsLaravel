<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f8fafc">
    <meta name="color-scheme" content="light dark">
    <title data-i18n-document-title="registerPageTitle">إنشاء حساب | PALPRINTS</title>
    <script>
        (() => {
            try {
                const language = localStorage.getItem('palprints-language') === 'en' ? 'en' : 'ar';
                const theme = localStorage.getItem('palprints-theme') === 'dark' ? 'dark' : 'light';
                document.documentElement.lang = language;
                document.documentElement.dir = language === 'en' ? 'ltr' : 'rtl';
                document.documentElement.dataset.theme = theme;
            } catch (_) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=block" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('front/css/auth/auth-system.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/auth/styles.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('front/css/auth/visual-refresh.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/auth/login-reference.css') }}?v={{ filemtime(public_path('front/css/auth/login-reference.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/auth/register.css') }}?v={{ filemtime(public_path('front/css/auth/register.css')) }}">
</head>
<body class="login-page auth-page register-page" data-auth-page="register">
    <svg class="register-clip-defs" width="0" height="0" aria-hidden="true" focusable="false">
        <defs>
            <clipPath id="registerHeroClipRtl" clipPathUnits="objectBoundingBox">
                <path d="M .075 0 C .075 .08 .02 .16 .02 .25 C .02 .34 .082 .43 .082 .52 C .082 .61 .025 .69 .025 .78 C .025 .87 .068 .94 .068 1 L 1 1 L 1 0 Z" />
            </clipPath>
            <clipPath id="registerHeroClipLtr" clipPathUnits="objectBoundingBox">
                <path d="M .925 0 C .925 .08 .98 .16 .98 .25 C .98 .34 .918 .43 .918 .52 C .918 .61 .975 .69 .975 .78 C .975 .87 .932 .94 .932 1 L 0 1 L 0 0 Z" />
            </clipPath>
        </defs>
    </svg>
    <a class="skip-link" href="#main-content" data-i18n="skipToContent">انتقل إلى المحتوى الرئيسي</a>
    <div class="auth-topbar">
        <div class="auth-topbar__container">
            <a class="brand" href="{{ route('home') }}" aria-label="PALPRINTS">
                <img class="brand-logo" src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="شعار PALPRINTS" width="172" height="97">
            </a>
            <nav class="auth-topbar__actions" aria-label="خيارات الحساب والواجهة" data-i18n-aria-label="headerNavLabel">
                <button class="header-control language-toggle" id="languageToggle" type="button" aria-label="تغيير اللغة إلى الإنجليزية" data-i18n-aria-label="languageToggleLabel">
                    <i class="bi bi-globe2 icon external-ui-icon icon-language" aria-hidden="true"></i>
                    <span id="languageToggleText">EN</span>
                </button>
                <button class="header-control theme-toggle" id="themeToggle" type="button" aria-label="تفعيل الوضع الليلي" aria-pressed="false">
                    <i class="bi bi-moon-stars" id="themeToggleIcon" aria-hidden="true"></i>
                    <span class="visually-hidden" data-i18n="themeToggleText">تبديل المظهر</span>
                </button>
            </nav>
        </div>
    </div>

    <main class="auth-main register-main" id="main-content">
        <section
            class="register-card"
            id="registerRoot"
            data-initial-step="{{ $errors->any() && old('account_type') ? 'details' : 'role' }}"
            data-initial-role="{{ old('account_type', '') }}"
        >
            <aside class="register-showcase">
                <div class="register-showcase__content">
                <div class="register-showcase__copy" aria-live="polite" aria-atomic="true">
                    <span class="register-showcase__eyebrow" data-i18n="registerVisualEyebrow">من الفكرة إلى منتج مطبوع</span>
                    <h2 id="registerShowcaseTitle">كل فكرة تستحق أن تُطبع باحتراف</h2>
                    <p id="registerShowcaseDescription">انضم إلى PalPrints واكتشف تجربة تجمع التصميم والطباعة والمنتجات في مكان واحد.</p>
                    <ul class="register-showcase__benefits" aria-label="مزايا الحساب" data-i18n-aria-label="registerBenefitsLabel">
                        <li id="registerBenefitOne">تصميمات مبتكرة</li>
                        <li id="registerBenefitTwo">طباعة احترافية</li>
                        <li id="registerBenefitThree">منتجات متنوعة</li>
                    </ul>
                </div>
                <div class="register-showcase__scene" id="registerShowcaseScene" aria-hidden="true">
                    <span class="register-showcase__orbit register-showcase__orbit--one"></span>
                    <span class="register-showcase__orbit register-showcase__orbit--two"></span>
                    <span class="register-showcase__spark register-showcase__spark--one"></span>
                    <span class="register-showcase__spark register-showcase__spark--two"></span>
                    <div class="register-showcase__product-wrap">
                        <span class="register-showcase__product-glow"></span>
                        <img class="register-showcase__products is-active" data-visual-image="general" src="{{ asset('front/assets/images/register-visual-general.png') }}?v={{ filemtime(public_path('front/assets/images/register-visual-general.png')) }}" alt="" fetchpriority="high" decoding="async">
                        <img class="register-showcase__products" data-visual-image="customer" src="{{ asset('front/assets/images/register-visual-customer.png') }}?v={{ filemtime(public_path('front/assets/images/register-visual-customer.png')) }}" alt="" decoding="async">
                        <img class="register-showcase__products" data-visual-image="designer" src="{{ asset('front/assets/images/register-visual-designer.png') }}?v={{ filemtime(public_path('front/assets/images/register-visual-designer.png')) }}" alt="" decoding="async">
                        <img class="register-showcase__products" data-visual-image="print_provider" src="{{ asset('front/assets/images/register-visual-print-provider.png') }}?v={{ filemtime(public_path('front/assets/images/register-visual-print-provider.png')) }}" alt="" decoding="async">
                    </div>
                </div>
                </div>
            </aside>

            <div class="register-panel">
            @if ($errors->any())
                <div class="auth-notice auth-notice--error register-errors" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" id="registerForm" data-native-auth-form novalidate>
                @csrf

                <section class="register-step" data-register-step="role" aria-labelledby="role-step-title">
                    <header class="register-heading">
                        <p class="auth-eyebrow" data-i18n="registerKicker">ابدأ رحلتك</p>
                        <h1 id="role-step-title" data-i18n="registerRoleTitle">كيف تريد استخدام PALPRINTS؟</h1>
                        <p data-i18n="registerRoleSubtitle">اختر نوع الحساب أولًا، ثم أدخل بيانات إنشاء الحساب.</p>
                    </header>

                    <div class="register-controls-card">
                    <div class="register-progress" aria-label="تقدم التسجيل">
                        <span class="register-progress__step is-active" data-progress="role">
                            <b>1</b>
                            <span data-i18n="roleStep">نوع الحساب</span>
                        </span>
                        <span class="register-progress__line" aria-hidden="true"></span>
                        <span class="register-progress__step" data-progress="details">
                            <b>2</b>
                            <span data-i18n="detailsStep">بياناتك</span>
                        </span>
                    </div>
                    <fieldset class="role-fieldset">
                        <legend class="visually-hidden" data-i18n="roleStep">نوع الحساب</legend>
                        <div class="role-grid">
                            <label class="role-card">
                                <input type="radio" name="account_type" value="customer" @checked(old('account_type') === 'customer')>
                                <span class="role-card__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <circle class="icon-soft" cx="12" cy="12" r="10"></circle>
                                        <circle class="icon-line" cx="12" cy="8" r="3.5"></circle>
                                        <path class="icon-line" d="M5 21a7 7 0 0 1 14 0"></path>
                                    </svg>
                                </span>
                                <span class="role-card__copy">
                                    <strong data-i18n="roleCustomer">عميل</strong>
                                    <small data-i18n="roleCustomerDescription">تسوّق واطبع المنتجات التي تناسبك.</small>
                                </span>
                                <span class="role-card__selector" aria-hidden="true"></span>
                            </label>

                            <label class="role-card">
                                <input type="radio" name="account_type" value="designer" @checked(old('account_type') === 'designer')>
                                <span class="role-card__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path class="icon-soft" d="M3 3h18v18H3z"></path>
                                        <path class="icon-line" d="m4 16-.8 4.8L8 20l11-11-4-4L4 16Z"></path>
                                        <path class="icon-line" d="m13.5 6.5 4 4M3 21h7"></path>
                                    </svg>
                                </span>
                                <span class="role-card__copy">
                                    <strong data-i18n="roleDesigner">مصمم</strong>
                                    <small data-i18n="roleDesignerDescription">اعرض تصاميمك وابدأ البيع.</small>
                                </span>
                                <span class="role-card__selector" aria-hidden="true"></span>
                            </label>

                            <label class="role-card">
                                <input type="radio" name="account_type" value="print_provider" @checked(old('account_type') === 'print_provider')>
                                <span class="role-card__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path class="icon-soft" d="M4 7h16a3 3 0 0 1 3 3v7H1v-7a3 3 0 0 1 3-3Z"></path>
                                        <path class="icon-line" d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <path class="icon-line" d="M7 14h10v7H7zM17 11h1"></path>
                                    </svg>
                                </span>
                                <span class="role-card__copy">
                                    <strong data-i18n="rolePrinter">مطبعة</strong>
                                    <small data-i18n="rolePrinterDescription">استقبل طلبات الطباعة وأدر أعمالك.</small>
                                </span>
                                <span class="role-card__selector" aria-hidden="true"></span>
                            </label>
                        </div>
                        @error('account_type')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error client-error" id="account_type_client_error" aria-live="polite"></p>
                    </fieldset>

                    <button class="auth-primary-button register-continue" id="continueButton" type="button" disabled>
                        <span data-i18n="continue">متابعة</span>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>

                    <p class="register-role-login">
                        <span data-i18n="hasAccount">لديك حساب بالفعل؟</span>
                        <a href="{{ route('login') }}" data-i18n="signIn">تسجيل الدخول</a>
                    </p>

                    </div>
                </section>

                <section class="register-step register-details" data-register-step="details" aria-labelledby="details-step-title" hidden>
                    <header class="register-heading register-heading--details">
                        <h1 id="details-step-title" data-i18n="detailsTitle">أدخل بياناتك الأساسية</h1>
                    </header>

                    <div class="register-controls-card register-controls-card--details">
                    <div class="register-progress" aria-label="تقدم التسجيل">
                        <span class="register-progress__step is-complete" data-progress="role">
                            <b>1</b>
                            <span data-i18n="roleStep">نوع الحساب</span>
                        </span>
                        <span class="register-progress__line is-complete" aria-hidden="true"></span>
                        <span class="register-progress__step is-active" data-progress="details">
                            <b>2</b>
                            <span data-i18n="detailsStep">بياناتك</span>
                        </span>
                    </div>
                    <div class="register-details__welcome">
                        <div>
                            <span data-i18n="registrationWelcome">أهلًا بك، أنت تنشئ</span>
                            <strong id="selectedRoleLabel">حسابًا جديدًا</strong>
                            <p id="detailsSubtitle">لن يستغرق الأمر أكثر من دقيقة.</p>
                        </div>
                        <button class="register-back" id="backButton" type="button">
                            <span data-i18n="changeRole">تغيير نوع الحساب</span>
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg>
                        </button>
                    </div>
                    <div class="register-fields">
                        <div class="field-group register-field--full">
                            <label for="name" data-i18n="fullName">الاسم الكامل</label>
                            <div class="input-shell">
                                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" minlength="3" maxlength="255" required aria-describedby="name_client_error" placeholder="مثال: أحمد محمد" data-i18n-placeholder="fullNamePlaceholder">
                            </div>
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                            <p class="field-error client-error" id="name_client_error" aria-live="polite"></p>
                        </div>

                        <div class="field-group register-field--full">
                            <label for="email" data-i18n="email">البريد الإلكتروني</label>
                            <div class="input-shell">
                                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required aria-describedby="email_client_error" placeholder="name@example.com" data-i18n-placeholder="emailPlaceholder">
                            </div>
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                            <p class="field-error client-error" id="email_client_error" aria-live="polite"></p>
                        </div>

                        <div class="field-group">
                            <label for="password" data-i18n="password">كلمة المرور</label>
                            <div class="input-shell">
                                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required aria-describedby="passwordHint password_client_error" placeholder="8 أحرف على الأقل" data-i18n-placeholder="passwordPlaceholder">
                                <button class="password-toggle" type="button" data-password-target="password" aria-label="إظهار كلمة المرور">
                                    <svg class="eye-on" aria-hidden="true" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                    <svg class="eye-off" aria-hidden="true" viewBox="0 0 24 24"><path d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.1 2.8M6.6 6.6C3.7 8.4 2 12 2 12s3.5 6 10 6a9.9 9.9 0 0 0 4.1-.9"></path></svg>
                                </button>
                            </div>
                            <span class="field-hint" id="passwordHint" data-i18n="passwordHint">استخدم حروفًا وأرقامًا لزيادة الأمان.</span>
                            @error('password')<p class="field-error">{{ $message }}</p>@enderror
                            <p class="field-error client-error" id="password_client_error" aria-live="polite"></p>
                        </div>

                        <div class="field-group">
                            <label for="password_confirmation" data-i18n="confirmPassword">تأكيد كلمة المرور</label>
                            <div class="input-shell">
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required aria-describedby="password_confirmation_client_error" placeholder="أعد كتابة كلمة المرور" data-i18n-placeholder="confirmPasswordPlaceholder">
                                <button class="password-toggle" type="button" data-password-target="password_confirmation" aria-label="إظهار كلمة المرور">
                                    <svg class="eye-on" aria-hidden="true" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                    <svg class="eye-off" aria-hidden="true" viewBox="0 0 24 24"><path d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.1 2.8M6.6 6.6C3.7 8.4 2 12 2 12s3.5 6 10 6a9.9 9.9 0 0 0 4.1-.9"></path></svg>
                                </button>
                            </div>
                            <p class="field-error client-error" id="password_confirmation_client_error" aria-live="polite"></p>
                        </div>
                    </div>

                    <label class="check-row register-terms">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
                        <span class="custom-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg></span>
                        <span>
                            <span data-i18n="agreeTo">أوافق على</span>
                            <a class="text-link" href="{{ route('terms') }}" target="_blank" data-i18n="termsAndConditions">الشروط والأحكام</a>
                            <span data-i18n="and">و</span>
                            <a class="text-link" href="{{ route('privacy') }}" target="_blank" data-i18n="privacyPolicy">سياسة الخصوصية</a>.
                        </span>
                    </label>
                    @error('terms')<p class="field-error register-terms-error">{{ $message }}</p>@enderror
                    <p class="field-error client-error register-terms-error" id="terms_client_error" aria-live="polite"></p>

                    <button class="auth-primary-button register-submit" type="submit">
                        <span id="submitLabel" data-i18n="createAccount">إنشاء الحساب</span>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>

                    <div class="social-divider" role="separator">
                        <span></span>
                        <strong data-i18n="or">أو</strong>
                        <span></span>
                    </div>
                    <div class="social-auth-row" aria-label="خيارات إنشاء الحساب">
                        <a class="google-button" href="{{ route('social.redirect', ['provider' => 'google', 'source' => 'register']) }}" data-social-register>
                            <span class="google-mark" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="30" height="30" focusable="false">
                                    <path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.63h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.58-5.17 3.58-8.8Z"/>
                                    <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.9l-3.88-3.03c-1.08.72-2.45 1.15-4.06 1.15-3.12 0-5.77-2.11-6.71-4.94H1.28v3.1A12 12 0 0 0 12 24Z"/>
                                    <path fill="#FBBC05" d="M5.29 14.28a7.2 7.2 0 0 1 0-4.56v-3.1H1.28a12 12 0 0 0 0 10.76l4.01-3.1Z"/>
                                    <path fill="#EA4335" d="M12 4.77c1.76 0 3.34.61 4.59 1.8l3.43-3.43C17.94 1.19 15.23 0 12 0A12 12 0 0 0 1.28 6.62l4.01 3.1C6.23 6.88 8.88 4.77 12 4.77Z"/>
                                </svg>
                            </span>
                            <span data-i18n="continueWithGoogle">المتابعة باستخدام Google</span>
                        </a>
                        <a class="google-button apple-button" href="{{ route('social.redirect', ['provider' => 'apple', 'source' => 'register']) }}" data-social-register>
                            <span class="apple-icon-slot" aria-hidden="true">
                                <i class="bi bi-apple" aria-hidden="true"></i>
                            </span>
                            <span data-i18n="continueWithApple">المتابعة باستخدام Apple</span>
                        </a>
                    </div>
                    <p class="register-social-status" role="status" aria-live="polite" data-social-status @if(! $errors->has('social')) hidden @endif>@error('social'){{ $message }}@enderror</p>

                    <p class="register-login-prompt">
                        <span data-i18n="hasAccount">لديك حساب بالفعل؟</span>
                        <a href="{{ route('login') }}" data-i18n="signIn">تسجيل الدخول</a>
                    </p>

                    <p class="register-privacy-note" data-i18n="dataPrivacyNote">نحفظ بياناتك بأمان ولن نشاركها مع أي طرف غير مخوّل.</p>
                    </div>
                </section>
            </form>
            </div>
        </section>
    </main>

    <script defer src="{{ asset('front/js/auth/auth-ui.js') }}"></script>
    <script defer src="{{ asset('front/js/auth/register.js') }}"></script>
</body>
</html>
