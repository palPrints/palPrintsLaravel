<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  @include('auth.partials.head', [
      'title' => 'تأكيد البريد الإلكتروني',
      'description' => 'تأكيد البريد الإلكتروني لحسابك في PalPrints',
      'css' => 'forgot-password',
  ])
</head>
<body>
  <main class="recovery-layout">
    <section class="recovery-panel" aria-labelledby="verifyTitle">
      <a href="{{ route('home') }}" class="recovery-wordmark" aria-label="العودة إلى الصفحة الرئيسية">
        <img src="{{ asset('front/auth/images/palprints-wordmark-approved.png') }}" alt="PalPrints">
      </a>

      <div class="recovery-card">
        <div class="recovery-icon" aria-hidden="true"><i class="bi bi-envelope-check"></i></div>
        <header class="recovery-heading">
          <span class="eyebrow">خطوة أخيرة</span>
          <h1 id="verifyTitle">أكّد بريدك <span>الإلكتروني</span></h1>
          <p>أرسلنا رمزًا من 6 أرقام إلى بريدك الإلكتروني. أدخله هنا لتوثيق حسابك. وإن لم يصلك رمز بعد، اضغط «إرسال الرمز» بالأسفل.</p>
        </header>

        @if (session('status') === 'verification-code-sent')
          <p class="form-status is-success" role="status">أرسلنا رمز التوثيق إلى بريدك الإلكتروني. الرمز صالح لمدة 10 دقائق.</p>
        @elseif (session('status') === 'verification-code-wait')
          <p class="form-status" role="status">أرسلنا لك رمزًا قبل قليل. انتظر {{ (int) session('verification_wait') }} ثانية قبل طلب رمز جديد.</p>
        @endif

        <form method="POST" action="{{ route('verification.verify') }}" novalidate>
          @csrf
          <div @class(['form-field', 'is-invalid' => $errors->has('code')])>
            <label for="verifyCode">رمز التوثيق</label>
            <div class="input-control">
              <i class="bi bi-123" aria-hidden="true"></i>
              <input id="verifyCode" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" aria-describedby="codeError" required autofocus @error('code') aria-invalid="true" @enderror>
            </div>
            <span class="field-error" id="codeError" aria-live="polite">@error('code'){{ $message }}@enderror</span>
          </div>
          <button class="submit-button" type="submit">
            <span>توثيق البريد الإلكتروني</span>
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>
        </form>

        <form method="POST" action="{{ route('verification.send') }}">
          @csrf
          <button class="back-link" type="submit"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> إرسال الرمز / إعادة الإرسال</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="back-link" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> تسجيل الخروج</button>
        </form>
      </div>
    </section>
  </main>
@include('partials.page-loader')
</body>
</html>
