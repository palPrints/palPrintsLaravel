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
          <p>أرسلنا رابط التأكيد إلى بريدك الإلكتروني. اضغط عليه للمتابعة، وإن لم يصلك يمكنك طلب رابط جديد.</p>
        </header>

        @if (session('status') === 'verification-link-sent')
          <p class="form-status is-success" role="status">تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني.</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
          @csrf
          <button class="submit-button" type="submit">
            <span>إعادة إرسال رابط التأكيد</span>
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="back-link" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> تسجيل الخروج</button>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
