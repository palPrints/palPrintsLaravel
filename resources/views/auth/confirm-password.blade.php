<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  @include('auth.partials.head', [
      'title' => 'تأكيد كلمة المرور',
      'description' => 'تأكيد كلمة المرور قبل المتابعة',
      'css' => 'forgot-password',
  ])
</head>
<body>
  <main class="recovery-layout">
    <section class="recovery-panel" aria-labelledby="confirmTitle">
      <a href="{{ route('home') }}" class="recovery-wordmark" aria-label="العودة إلى الصفحة الرئيسية">
        <img src="{{ asset('front/auth/images/palprints-wordmark-approved.png') }}" alt="PalPrints">
      </a>

      <div class="recovery-card">
        <div class="recovery-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></div>
        <header class="recovery-heading">
          <span class="eyebrow">منطقة آمنة</span>
          <h1 id="confirmTitle">أكّد <span>كلمة المرور</span></h1>
          <p>هذه منطقة آمنة، يرجى تأكيد كلمة المرور قبل المتابعة.</p>
        </header>

        <form method="POST" action="{{ route('password.confirm') }}" novalidate>
          @csrf
          <div @class(['form-field', 'is-invalid' => $errors->has('password')])>
            <label for="password">كلمة المرور</label>
            <div class="input-control">
              <i class="bi bi-lock" aria-hidden="true"></i>
              <input id="password" name="password" type="password" autocomplete="current-password" placeholder="أدخل كلمة المرور" required>
            </div>
            <span class="field-error" aria-live="polite">@error('password'){{ $message }}@enderror</span>
          </div>

          <button class="submit-button" type="submit">
            <span>تأكيد</span>
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>
        </form>
      </div>
    </section>
  </main>
@include('partials.page-loader')
</body>
</html>
