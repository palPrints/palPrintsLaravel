<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  @include('auth.partials.head', [
      'title' => 'إعادة تعيين كلمة المرور',
      'description' => 'اختر كلمة مرور جديدة لحسابك في PalPrints',
      'css' => 'forgot-password',
  ])
</head>
<body>
  <main class="recovery-layout">
    <section class="recovery-panel" aria-labelledby="resetTitle">
      <a href="{{ route('home') }}" class="recovery-wordmark" aria-label="العودة إلى الصفحة الرئيسية">
        <img src="{{ asset('front/auth/images/palprints-wordmark-approved.png') }}" alt="PalPrints">
      </a>

      <div class="recovery-card">
        <div class="recovery-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></div>
        <header class="recovery-heading">
          <span class="eyebrow">حماية حسابك</span>
          <h1 id="resetTitle">إعادة تعيين <span>كلمة المرور</span></h1>
          <p>اختر كلمة مرور قوية وجديدة لحماية حسابك.</p>
        </header>

        <form id="resetPasswordForm" class="reset-form" method="POST" action="{{ route('password.store') }}" novalidate>
          @csrf
          <input type="hidden" name="token" value="{{ $request->route('token') }}">
          <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

          @error('email')
            <p class="form-status is-error" role="alert">{{ $message }}</p>
          @enderror

          <div @class(['form-field', 'is-invalid' => $errors->has('password')])>
            <label for="newPassword">كلمة المرور الجديدة</label>
            <div class="input-control">
              <i class="bi bi-lock" aria-hidden="true"></i>
              <input id="newPassword" name="password" type="password" autocomplete="new-password" minlength="8" placeholder="8 أحرف على الأقل" aria-describedby="newPasswordError" required @error('password') aria-invalid="true" @enderror>
              <button class="password-toggle" type="button" data-target="newPassword" aria-label="إظهار كلمة المرور" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
            <span class="field-error" id="newPasswordError" aria-live="polite">@error('password'){{ $message }}@enderror</span>
          </div>

          <div @class(['form-field', 'is-invalid' => $errors->has('password_confirmation')])>
            <label for="confirmPassword">تأكيد كلمة المرور</label>
            <div class="input-control">
              <i class="bi bi-shield-check" aria-hidden="true"></i>
              <input id="confirmPassword" name="password_confirmation" type="password" autocomplete="new-password" placeholder="أعد كتابة كلمة المرور" aria-describedby="confirmPasswordError" required>
              <button class="password-toggle" type="button" data-target="confirmPassword" aria-label="إظهار كلمة المرور" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
            <span class="field-error" id="confirmPasswordError" aria-live="polite">@error('password_confirmation'){{ $message }}@enderror</span>
          </div>

          <button class="submit-button" type="submit">
            <span>تعيين كلمة المرور الجديدة</span>
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>
          <p class="security-note"><i class="bi bi-shield-check" aria-hidden="true"></i> استخدم 8 أحرف على الأقل ولا تشارك كلمة مرورك مع أحد.</p>
        </form>

        <a class="back-link" href="{{ route('login') }}"><i class="bi bi-arrow-right" aria-hidden="true"></i> العودة إلى تسجيل الدخول</a>
      </div>
    </section>
  </main>

  <script src="{{ asset('front/auth/js/reset-password.js') }}?v={{ filemtime(public_path('front/auth/js/reset-password.js')) }}"></script>
@include('partials.page-loader')
</body>
</html>
