<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  @include('auth.partials.head', [
      'title' => 'نسيت كلمة المرور',
      'description' => 'استعادة كلمة مرور حساب PalPrints',
      'css' => 'forgot-password',
  ])
</head>
<body>
  @php
    $sent = session('status') !== null;
    $sentEmail = session('reset_email') ?: old('email');
  @endphp

  <main class="recovery-layout">
    <section class="recovery-panel" aria-labelledby="recoveryTitle">
      <a href="{{ route('home') }}" class="recovery-wordmark" aria-label="العودة إلى الصفحة الرئيسية">
        <img src="{{ asset('front/auth/images/palprints-wordmark-approved.png') }}" alt="PalPrints">
      </a>

      <div class="recovery-card">
        <div class="recovery-icon" aria-hidden="true"><i class="bi bi-key"></i></div>
        <header class="recovery-heading">
          <span class="eyebrow">استعادة الحساب</span>
          <h1 id="recoveryTitle">نسيت كلمة <span>المرور؟</span></h1>
          <p>أدخل البريد الإلكتروني المرتبط بحسابك وسنرسل لك رابط إعادة تعيين كلمة المرور.</p>
        </header>

        <form id="recoveryForm" method="POST" action="{{ route('password.email') }}" novalidate @if ($sent) hidden @endif>
          @csrf
          <div @class(['form-field', 'is-invalid' => $errors->has('email')])>
            <label for="recoveryEmail">البريد الإلكتروني</label>
            <div class="input-control">
              <i class="bi bi-envelope" aria-hidden="true"></i>
              <input id="recoveryEmail" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" placeholder="name@example.com" aria-describedby="emailError" required @error('email') aria-invalid="true" @enderror>
            </div>
            <span class="field-error" id="emailError" aria-live="polite">@error('email'){{ $message }}@enderror</span>
          </div>

          <button class="submit-button" type="submit">
            <span>إرسال رابط إعادة التعيين</span>
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>
          <p class="security-note"><i class="bi bi-shield-check" aria-hidden="true"></i> الرابط صالح لفترة محدودة حفاظًا على أمان حسابك.</p>
        </form>

        <div class="success-state" id="successState" role="status" aria-live="polite" @unless ($sent) hidden @endunless>
          <span class="success-icon"><i class="bi bi-envelope-check"></i></span>
          <h2>تحقق من بريدك الإلكتروني</h2>
          <p>{{ session('status') ?: 'أرسلنا رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.' }}</p>
          @if ($sentEmail)
            <p><strong>{{ $sentEmail }}</strong></p>
            <form method="POST" action="{{ route('password.email') }}">
              @csrf
              <input type="hidden" name="email" value="{{ $sentEmail }}">
              <button type="submit" id="resendButton">إعادة إرسال الرابط</button>
            </form>
          @endif
        </div>

        <a class="back-link" href="{{ route('login') }}"><i class="bi bi-arrow-right" aria-hidden="true"></i> العودة إلى تسجيل الدخول</a>
      </div>
    </section>
  </main>

  <script src="{{ asset('front/auth/js/forgot-password.js') }}?v={{ filemtime(public_path('front/auth/js/forgot-password.js')) }}"></script>
</body>
</html>
