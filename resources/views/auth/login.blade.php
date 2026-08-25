<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="تسجيل الدخول إلى منصة PALPRINTS.">
  <meta name="theme-color" content="#f8fafc">
  <meta name="color-scheme" content="light dark">
  <title data-i18n-document-title="loginPageTitle">تسجيل الدخول | PALPRINTS</title>
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
  <link rel="stylesheet" href="{{ asset('front/css/auth/styles.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="{{ asset('front/css/auth/visual-refresh.css') }}">
  <link rel="stylesheet" href="{{ asset('front/css/auth/login-reference.css') }}?v={{ filemtime(public_path('front/css/auth/login-reference.css')) }}">
  <script src="{{ asset('front/js/auth/auth-ui.js') }}" defer></script>
  <script src="{{ asset('front/js/auth/login.js') }}" defer></script>
</head>
<body class="login-page auth-page" data-auth-page="login">
  <svg class="login-clip-defs" width="0" height="0" aria-hidden="true" focusable="false">
    <defs>
      <clipPath id="loginHeroClip" clipPathUnits="objectBoundingBox">
        <path d="M .519 0 C .519 .09 .473 .16 .473 .25 C .473 .34 .527 .43 .527 .52 C .527 .61 .475 .69 .475 .78 C .475 .87 .511 .93 .511 1 L 1 1 L 1 0 Z" />
      </clipPath>
      <clipPath id="loginHeroClipLtr" clipPathUnits="objectBoundingBox">
        <path d="M .481 0 C .481 .09 .527 .16 .527 .25 C .527 .34 .473 .43 .473 .52 C .473 .61 .525 .69 .525 .78 C .525 .87 .489 .93 .489 1 L 0 1 L 0 0 Z" />
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

  <main class="main-content" id="main-content" tabindex="-1">
    <div class="auth-layout">
      <aside class="auth-visual-panel" aria-labelledby="login-visual-title">
        <div class="auth-visual-inner">
          <span class="hero-dots hero-dots--top" aria-hidden="true"></span>
          <span class="hero-dots hero-dots--mid" aria-hidden="true"></span>
          <img class="auth-products-image" src="{{ asset('front/assets/images/login-page-products.png') }}" alt="منتجات PALPRINTS" data-i18n-alt="productsImageAlt">
          <div class="auth-visual-copy">
            <p class="auth-visual-kicker" data-i18n="loginVisualKicker">منصة الطباعة حسب الطلب</p>
            <h2 id="login-visual-title" data-i18n="loginVisualTitle">كل فكرة يمكن أن تصبح شيئًا حقيقيًا</h2>
            <p class="auth-visual-description" data-i18n="loginVisualDescription">انضم إلى PALPRINTS واكتشف عالمًا يجمع التصميم، الطباعة، والإبداع.</p>
            <ul class="auth-visual-benefits" aria-label="مزايا PALPRINTS" data-i18n-aria-label="loginVisualBenefitsLabel">
              <li><i class="bi bi-check-lg icon" aria-hidden="true"></i><span data-i18n="loginBenefitOne">وصول سريع إلى حسابك</span></li>
              <li><i class="bi bi-check-lg icon" aria-hidden="true"></i><span data-i18n="loginBenefitTwo">متابعة الطلبات والحالة</span></li>
              <li><i class="bi bi-check-lg icon" aria-hidden="true"></i><span data-i18n="loginBenefitThree">تجربة موحّدة لكل أنواع الحسابات</span></li>
            </ul>
          </div>
          <ul class="hero-social" aria-label="حسابات PALPRINTS على مواقع التواصل">
            <li><a href="#" aria-label="X (Twitter)"><i class="bi bi-twitter" aria-hidden="true"></i></a></li>
            <li><a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a></li>
            <li><a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a></li>
            <li><a href="#" aria-label="YouTube"><i class="bi bi-youtube" aria-hidden="true"></i></a></li>
          </ul>
        </div>
      </aside>

      <div class="auth-form-panel">
        <span class="hero-dots hero-dots--corner" aria-hidden="true"></span>
        <div class="login-shell">
          <header class="register-heading">
            <p class="eyebrow" data-i18n="welcomeBack">مرحبًا بعودتك</p>
            <h1 id="login-title" data-i18n="signIn">تسجيل الدخول</h1>
            <p class="register-subtitle" data-i18n="loginSubtitle">أدخل بيانات حسابك للمتابعة إلى PALPRINTS.</p>
          </header>

        <section class="login-card" aria-labelledby="login-title">
          <form class="login-form" id="loginForm" method="POST" action="{{ route('login') }}" data-native-auth-form>
            @csrf
            <div class="field-group">
              <label class="stacked-label" for="loginEmail" data-i18n="email">البريد الإلكتروني</label>
              <div class="stacked-field">
                <input id="loginEmail" name="email" type="email" value="{{ old('email') }}" placeholder="example@palprints.com" autocomplete="username" required aria-describedby="loginEmailError">
                <span class="field-affix" aria-hidden="true"><i class="bi bi-envelope"></i></span>
              </div>
              <p class="field-error" id="loginEmailError" aria-live="polite">@error('email'){{ $message }}@enderror</p>
            </div>

            <div class="field-group">
              <label class="stacked-label" for="loginPassword" data-i18n="password">كلمة المرور</label>
              <div class="stacked-field">
                <input class="password-input" id="loginPassword" name="password" type="password" placeholder="••••••••••••" autocomplete="current-password" required aria-describedby="loginPasswordError">
                <button class="field-affix password-toggle" type="button" data-password-target="loginPassword" aria-label="إظهار كلمة المرور">
                  <i class="bi bi-eye icon external-ui-icon icon-eye" aria-hidden="true"></i>
                  <i class="bi bi-eye-slash icon external-ui-icon icon-eye-off" aria-hidden="true"></i>
                </button>
              </div>
              <p class="field-error" id="loginPasswordError" aria-live="polite">@error('password'){{ $message }}@enderror</p>
            </div>

            <div class="login-options">
              <label class="remember-row" for="rememberMe">
                <input id="rememberMe" name="remember" type="checkbox">
                <span data-i18n="rememberMe">تذكرني</span>
              </label>
              <a class="forgot-password" href="{{ route('password.request') }}" id="forgotPasswordLink" data-i18n="forgotPassword">نسيت كلمة المرور؟</a>
            </div>

            <div class="form-status @if(session('status')) is-success @elseif($errors->has('social')) is-error @endif" id="loginFormStatus" role="status" aria-live="polite" @if(session('status') || $errors->has('social')) style="display:block" @endif>{{ $errors->first('social') ?: session('status') }}</div>

            <button class="primary-button submit-button" type="submit">
              <span data-i18n="signIn">تسجيل الدخول</span>
              <span class="button-loader" aria-hidden="true"></span>
            </button>

            <div class="social-divider" role="separator"><span></span><strong data-i18n="or">أو</strong><span></span></div>

            <div class="social-auth-row">
            <a class="google-button" id="googleLoginButton" href="{{ route('social.redirect', ['provider' => 'google']) }}">
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
            <a class="google-button apple-button" id="appleLoginButton" href="{{ route('social.redirect', ['provider' => 'apple']) }}">
              <span class="apple-icon-slot" aria-hidden="true">
                <i class="bi bi-apple" aria-hidden="true"></i>
              </span>
              <span data-i18n="continueWithApple">المتابعة باستخدام Apple</span>
            </a>
            </div>
          </form>

          <p class="login-footer">
            <span data-i18n="needAccount">ليس لديك حساب؟</span>
            <a href="{{ route('register') }}" data-i18n="createAccount">إنشاء حساب جديد</a>
          </p>
        </section>
        </div>
      </div>
    </div>
  </main>

</body>
</html>
