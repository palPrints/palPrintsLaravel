<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="استعادة كلمة مرور حساب PALPRINTS."><meta name="theme-color" content="#f5f8ff"><meta name="color-scheme" content="light dark">
  <title>نسيت كلمة المرور | PALPRINTS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="{{ asset('front/css/auth/forgot-password.css') }}">
  <script src="{{ asset('front/js/auth/auth-flow.js') }}" defer></script>
  <script src="{{ asset('front/js/auth/page-i18n.js') }}" defer></script>
</head>
<body class="auth-flow-page forgot-password-page" data-auth-flow="forgot-password-page">
  <a class="skip-link" href="#main-content" data-i18n="skipToContent">انتقل إلى المحتوى الرئيسي</a>
  <div class="auth-topbar">
    <div class="auth-topbar__container">
      <a class="brand" href="{{ route('home') }}" aria-label="PALPRINTS"><img class="brand-logo brand-logo-light" src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="شعار PALPRINTS" width="140" height="55"><img class="brand-logo brand-logo-dark" src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="" aria-hidden="true" width="140" height="55"></a>
      <nav class="auth-topbar__actions" aria-label="خيارات الحساب والواجهة" data-i18n-aria-label="headerNavLabel">
        <button class="header-control language-toggle" id="languageToggle" type="button" aria-label="تغيير اللغة إلى الإنجليزية" data-i18n-aria-label="languageToggleLabel"><i class="bi bi-globe2 icon external-ui-icon icon-language" aria-hidden="true"></i><span id="languageToggleText">EN</span></button>
        <button class="header-control theme-toggle" id="themeToggle" type="button" aria-label="تفعيل الوضع الليلي" aria-pressed="false"><i class="bi bi-moon-stars" id="themeToggleIcon" aria-hidden="true"></i><span class="visually-hidden" data-i18n="themeToggleText">تبديل المظهر</span></button>
      </nav>
    </div>
  </div>
  <div class="forgot-decoration" aria-hidden="true"><span class="forgot-orbit orbit-a"></span><span class="forgot-orbit orbit-b"></span><span class="forgot-blob blob-a"></span><span class="forgot-blob blob-b"></span><span class="forgot-dots dots-a"></span><span class="forgot-dots dots-b"></span></div>
  <main class="forgot-main" id="main-content" tabindex="-1">
    <section class="forgot-card auth-flow-card" aria-labelledby="forgot-title">
      <span class="forgot-step">1 من 3</span>
      <div class="forgot-symbol" aria-hidden="true"><i class="bi bi-envelope-fill"></i><span><i class="bi bi-lock-fill"></i></span></div>
      <header class="auth-flow-heading"><p class="eyebrow" data-i18n="eyebrow">استعادة الحساب</p><h1 id="forgot-title" data-i18n="pageHeading">نسيت كلمة المرور؟</h1><p class="register-subtitle" data-i18n="pageSubtitle">لا تقلق، أدخل بريدك الإلكتروني وسنرسل إليك رمز تحقق لإعادة تعيين كلمة المرور.</p></header>
      <form class="forgot-form" id="forgotPasswordForm" method="POST" action="{{ route('password.email') }}" data-native-laravel>
        @csrf
        <div class="field-group"><label class="forgot-label" for="recoveryEmail"><span data-i18n="email">البريد الإلكتروني</span><b aria-hidden="true">*</b></label><div class="forgot-input-wrap"><i class="bi bi-envelope" aria-hidden="true"></i><input id="recoveryEmail" name="email" type="email" value="{{ old('email') }}" placeholder="example@email.com" autocomplete="email" required aria-describedby="recoveryEmailError"></div><p class="field-error" id="recoveryEmailError" aria-live="polite">@error('email'){{ $message }}@enderror</p></div>
        <div class="form-status @if(session('status')) is-success @endif" id="flowStatus" role="status" aria-live="polite" @if(session('status')) style="display:block" @endif>{{ session('status') }}</div>
        <button class="forgot-submit submit-button" type="submit"><i class="bi bi-send" aria-hidden="true"></i><span data-i18n="sendCode">إرسال رمز التحقق</span><span class="button-loader" aria-hidden="true"></span></button>
      </form>
      <div class="forgot-divider"><span data-i18n="or">أو</span></div>
      <a class="forgot-back auth-back-link" href="{{ route('login') }}"><span data-i18n="backToLogin">العودة إلى تسجيل الدخول</span><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
    </section>
  </main>
  <footer class="forgot-footer"><p>© <span id="currentYear">2026</span> PALPRINTS</p><nav aria-label="روابط قانونية"><a href="{{ route('home') }}">المساعدة</a><span></span><a href="{{ route('terms') }}">الشروط والأحكام</a><span></span><a href="{{ route('privacy') }}">سياسة الخصوصية</a></nav></footer>
</body>
</html>
