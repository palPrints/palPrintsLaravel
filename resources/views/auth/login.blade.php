<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  @include('auth.partials.head', [
      'title' => 'تسجيل الدخول',
      'description' => 'تسجيل الدخول إلى منصة PalPrints للطباعة حسب الطلب',
      'css' => 'login-system',
  ])
  <link rel="stylesheet" href="{{ asset('front/shared/theme-dark.css') }}?v={{ filemtime(public_path('front/shared/theme-dark.css')) }}">
  <script src="{{ asset('front/shared/theme-dark.js') }}?v={{ filemtime(public_path('front/shared/theme-dark.js')) }}"></script>
</head>

<body class="login-page">
  @php
    $formMessage = $errors->first('social') ?: session('error') ?: session('status');
    $formIsError = $errors->has('social') || session()->has('error');
  @endphp

  <main class="auth-layout">
    <section class="auth-visual" aria-label="منتجات مطبوعة من PalPrints">
      <img
        src="{{ asset('front/auth/images/pal-print-login-real.png') }}"
        alt="موظفة في مطبعة حديثة تتفقد قميصاً مطبوعاً بجانب مجموعة من منتجات PalPrints"
        class="auth-visual__image"
      >

      <div class="auth-visual__overlay" aria-hidden="true"></div>

      <div class="auth-visual__content">
        <h2>من الفكرة <span>للطباعة</span></h2>
        <p><strong>كل ما تحتاجه لتحويل تصميمك إلى منتج مميز</strong></p>
        <p>اختر منتجك، أضف تصميمك، واترك لنا مهمة الطباعة بجودة واهتمام بكل تفصيل.</p>
      </div>
    </section>

    <section class="auth-panel" aria-labelledby="login-title">
      <a href="{{ route('home') }}" class="auth-wordmark" aria-label="العودة إلى الصفحة الرئيسية لـ PalPrints">
        <img src="{{ asset('front/auth/images/palprints-wordmark-approved.png') }}" alt="PalPrints">
      </a>

      <div class="auth-panel__inner">
        <div class="auth-heading">
          <h1 id="login-title">مرحباً <span>بعودتك</span></h1>
          <p>سجّل الدخول للمتابعة إلى حسابك في PalPrints.</p>
        </div>

        <form class="login-form" id="loginForm" action="{{ route('login') }}" method="POST" novalidate>
          @csrf
          <input type="hidden" name="locale" value="ar">

          <div @class(['form-field', 'is-invalid' => $errors->has('email')])>
            <label for="email">البريد الإلكتروني</label>
            <div class="input-control">
              <i class="bi bi-envelope" aria-hidden="true"></i>
              <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="name@example.com"
                autocomplete="username"
                inputmode="email"
                required
                aria-describedby="emailError"
                @error('email') aria-invalid="true" @enderror
              >
            </div>
            <span class="field-error" id="emailError" aria-live="polite">@error('email'){{ $message }}@enderror</span>
          </div>

          <div @class(['form-field', 'is-invalid' => $errors->has('password')])>
            <label for="password">كلمة المرور</label>
            <div class="input-control">
              <i class="bi bi-lock" aria-hidden="true"></i>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="أدخل كلمة المرور"
                autocomplete="current-password"
                minlength="6"
                required
                aria-describedby="passwordError"
                @error('password') aria-invalid="true" @enderror
              >
              <button type="button" class="password-toggle" id="passwordToggle" aria-label="إظهار كلمة المرور" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            <span class="field-error" id="passwordError" aria-live="polite">@error('password'){{ $message }}@enderror</span>
          </div>

          <div class="form-options">
            <label class="remember-option">
              <input type="checkbox" name="remember" @checked(old('remember'))>
              <span>تذكرني</span>
            </label>
            <a href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
          </div>

          <button type="submit" class="submit-button">
            <span>تسجيل الدخول</span>
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>

          <div class="social-login" aria-label="خيارات تسجيل الدخول عبر حساب اجتماعي">
            <a class="social-login__button social-login__button--google" href="{{ route('social.redirect', ['provider' => 'google']) }}" aria-label="تسجيل الدخول باستخدام Google">
              <i class="bi bi-google" aria-hidden="true"></i>
            </a>
            <a class="social-login__button social-login__button--apple" href="{{ route('social.redirect', ['provider' => 'apple']) }}" aria-label="تسجيل الدخول باستخدام Apple">
              <i class="bi bi-apple" aria-hidden="true"></i>
            </a>
          </div>

          <p class="signup-prompt">
            ليس لديك حساب؟
            <a href="{{ route('register') }}">أنشئ حساباً جديداً</a>
          </p>

          <p @class(['form-status', 'is-error' => $formIsError, 'is-success' => ! $formIsError && $formMessage]) id="formStatus" role="{{ $formIsError ? 'alert' : 'status' }}" aria-live="polite">{{ $formMessage }}</p>

        </form>
      </div>
    </section>
  </main>

  <script src="{{ asset('front/auth/js/login.js') }}?v={{ filemtime(public_path('front/auth/js/login.js')) }}"></script>
</body>
</html>
