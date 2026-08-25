<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="إعادة تعيين كلمة المرور لحساب PALPRINTS.">
  <meta name="theme-color" content="#f8f9ff">
  <meta name="color-scheme" content="light dark">
  <title>إعادة تعيين كلمة المرور | PALPRINTS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="{{ asset('front/css/auth/styles.css') }}">
  <link rel="stylesheet" href="{{ asset('front/css/auth/reset-password.css') }}">
  <script src="{{ asset('front/js/auth/auth-flow.js') }}" defer></script>
  <script src="{{ asset('front/js/auth/page-i18n.js') }}" defer></script>
</head>
<body class="login-page auth-flow-page reset-password-page" data-auth-flow="reset-password-page">
  <a class="skip-link" href="#main-content" data-i18n="skipToContent">انتقل إلى المحتوى الرئيسي</a>
  <div class="auth-topbar">
    <div class="auth-topbar__container">
      <a class="brand" href="{{ route('home') }}" aria-label="PALPRINTS"><img class="brand-logo brand-logo-light" src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="شعار PALPRINTS" width="140" height="55"><img class="brand-logo brand-logo-dark" src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="" aria-hidden="true" width="140" height="55"></a>
      <nav class="auth-topbar__actions" aria-label="خيارات الحساب والواجهة" data-i18n-aria-label="headerNavLabel">
        <button class="header-control language-toggle" id="languageToggle" type="button" aria-label="تغيير اللغة إلى الإنجليزية" data-i18n-aria-label="languageToggleLabel"><i class="bi bi-globe2" aria-hidden="true"></i><span id="languageToggleText">EN</span></button>
        <button class="header-control theme-toggle" id="themeToggle" type="button" aria-label="تفعيل الوضع الليلي" aria-pressed="false"><i class="bi bi-moon-stars" id="themeToggleIcon" aria-hidden="true"></i><span class="visually-hidden" data-i18n="themeToggleText">تبديل المظهر</span></button>
      </nav>
    </div>
  </div>
  <div class="reset-background" aria-hidden="true">
    <span class="orbit orbit-left"><i></i></span><span class="orbit orbit-top"><i></i></span>
    <span class="orbit orbit-right"><i></i></span><span class="glow glow-left"></span><span class="glow glow-right"></span>
    <span class="soft-circle soft-circle-one"></span><span class="soft-circle soft-circle-two"></span>
    <span class="dots dots-one"></span><span class="dots dots-two"></span><span class="dots dots-three"></span>
  </div>
  <main id="main-content" class="reset-main" tabindex="-1">
    <section class="reset-card" aria-labelledby="reset-title">
      <a class="reset-logo-link" href="{{ route('home') }}" aria-label="PALPRINTS">
        <img class="reset-logo" src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="شعار PALPRINTS" width="210">
      </a>
      <div class="brand-divider" aria-hidden="true"><span></span><i></i><span></span></div>
      <header class="auth-flow-heading">
        <h1 id="reset-title" data-i18n="pageHeading">إعادة تعيين كلمة المرور</h1>
        <p class="register-subtitle" data-i18n="pageSubtitle">يرجى إدخال كلمة المرور الجديدة وتأكيدها.</p>
      </header>
      <form class="reset-form" id="resetPasswordForm" method="POST" action="{{ route('password.store') }}" data-native-laravel>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">
        <div class="field-group">
          <label class="reset-label" for="newPassword"><span class="label-dot" aria-hidden="true"></span><span data-i18n="newPassword">كلمة المرور الجديدة</span></label>
          <div class="reset-input-wrap">
            <i class="bi bi-lock reset-lock" aria-hidden="true"></i>
            <input class="password-input" id="newPassword" name="password" type="password" placeholder="أدخل كلمة المرور الجديدة" autocomplete="new-password" required aria-describedby="newPasswordError">
            <button class="password-toggle" type="button" data-password-target="newPassword" aria-label="إظهار كلمة المرور"><i class="bi bi-eye icon-eye" aria-hidden="true"></i><i class="bi bi-eye-slash icon-eye-off" aria-hidden="true"></i></button>
          </div>
          <p class="field-error" id="newPasswordError" aria-live="polite">@error('password'){{ $message }}@enderror</p>
        </div>
        <div class="field-group">
          <label class="reset-label" for="confirmNewPassword"><span class="label-dot" aria-hidden="true"></span><span data-i18n="confirmPassword">تأكيد كلمة المرور</span></label>
          <div class="reset-input-wrap">
            <i class="bi bi-lock reset-lock" aria-hidden="true"></i>
            <input class="password-input" id="confirmNewPassword" name="password_confirmation" type="password" placeholder="أعد إدخال كلمة المرور" autocomplete="new-password" required aria-describedby="confirmNewPasswordError">
            <button class="password-toggle" type="button" data-password-target="confirmNewPassword" aria-label="إظهار كلمة المرور"><i class="bi bi-eye icon-eye" aria-hidden="true"></i><i class="bi bi-eye-slash icon-eye-off" aria-hidden="true"></i></button>
          </div>
          <p class="field-error" id="confirmNewPasswordError" aria-live="polite">@error('password_confirmation'){{ $message }}@enderror</p>
        </div>
        <div class="form-status" id="flowStatus" role="status" aria-live="polite"></div>
        <button class="reset-submit submit-button" type="submit"><i class="bi bi-arrow-left" aria-hidden="true"></i><span data-i18n="savePassword">إعادة تعيين كلمة المرور</span><span class="button-loader" aria-hidden="true"></span></button>
      </form>
      <a class="back-login auth-back-link" href="{{ route('login') }}"><span data-i18n="backToLogin">العودة إلى تسجيل الدخول</span><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
    </section>
  </main>
</body>
</html>
