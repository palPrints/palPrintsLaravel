<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="متابعة حالة طلب حساب المصمم أو المطبعة في PALPRINTS.">
  <meta name="theme-color" content="#f8fafc">
  <meta name="color-scheme" content="light dark">
  <title>حالة الحساب | PALPRINTS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=block" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('front/css/auth/styles.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="{{ asset('front/css/auth/visual-refresh.css') }}">
  <link rel="stylesheet" href="{{ asset('front/css/auth/account-status-responsive.css') }}?v=8">
  <script src="{{ asset('front/js/auth/auth-flow.js') }}?v=2" defer></script>
  <script src="{{ asset('front/js/auth/page-i18n.js') }}" defer></script>
</head>
<body class="auth-flow-page account-status-page"
      data-auth-flow="account-status-page"
      data-account-role="{{ $accountRole ?? '' }}"
      data-account-status-value="{{ $accountApprovalStatus ?? 'pending' }}"
      data-account-reference="{{ $accountReference ?? '' }}">
  <a class="skip-link" href="#main-content" data-i18n="skipToContent">انتقل إلى المحتوى الرئيسي</a>
  <div class="auth-topbar">
    <div class="auth-topbar__container">
      <a class="brand" href="{{ route('home') }}" aria-label="PALPRINTS">
        <img class="brand-logo brand-logo-light" src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="شعار PALPRINTS" width="140" height="55">
        <img class="brand-logo brand-logo-dark" src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="" aria-hidden="true" width="140" height="55">
      </a>
      <nav class="auth-topbar__actions" aria-label="خيارات الواجهة" data-i18n-aria-label="headerNavLabel">
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
  <main class="account-status-main" id="main-content" tabindex="-1">
    <div class="account-status-shell">
      <section class="login-card auth-flow-card account-status-card" aria-labelledby="status-title">
          <header class="register-heading auth-flow-heading">
            <p class="eyebrow" id="statusEyebrow">حالة الحساب</p>
            <h1 id="status-title">بانتظار مراجعة حسابك</h1>
            <p class="register-subtitle" id="statusDescription">تم استلام طلبك بنجاح، ويقوم فريق PALPRINTS الآن بمراجعة بيانات حسابك.</p>
          </header>

          <section class="status-review-summary" aria-labelledby="status-now-title">
            <h2 id="status-now-title">ماذا يحدث الآن؟</h2>
            <p id="statusNowDescription">نراجع البيانات المرسلة للتأكد من جاهزية الحساب قبل تفعيل الصلاحيات.</p>
            <p class="status-next-note" id="statusNextNote">سنرسل لك إشعارًا عبر البريد الإلكتروني عند اكتمال المراجعة أو إذا احتجنا معلومات إضافية.</p>
          </section>

          <ol class="status-progress" id="statusProgress" aria-label="مراحل مراجعة الحساب">
            <li class="status-progress-step" id="statusStepReceived">
              <span class="status-progress-marker" aria-hidden="true">✓</span>
              <span class="status-progress-label" id="statusStepReceivedLabel">تم استلام الطلب</span>
            </li>
            <li class="status-progress-step" id="statusStepReview">
              <span class="status-progress-marker" aria-hidden="true">2</span>
              <span class="status-progress-label" id="statusStepReviewLabel">قيد المراجعة</span>
            </li>
            <li class="status-progress-step" id="statusStepActivated">
              <span class="status-progress-marker" aria-hidden="true">3</span>
              <span class="status-progress-label" id="statusStepActivatedLabel">تفعيل الحساب</span>
            </li>
          </ol>

          <dl class="status-details" id="statusDetails">
            <div><dt data-i18n="accountType">نوع الحساب</dt><dd id="statusRole">{{ $accountRoleLabel ?? '—' }}</dd></div>
            <div id="statusReferenceRow"><dt data-i18n="requestReference">رقم الطلب</dt><dd id="statusReference">{{ $accountReference ?? '—' }}</dd></div>
          </dl>
          <div class="status-actions">
            <a class="primary-button" id="statusPrimaryAction" href="{{ route('dashboard') }}" data-login-url="{{ route('dashboard') }}" data-register-url="{{ route('profile.edit') }}">متابعة</a>
            <a class="primary-button guest-browse-button" href="{{ route('home') }}" data-i18n="browseAsGuest">تصفح المنصة كزائر</a>
          </div>
      </section>
    </div>
  </main>
  <footer class="site-footer">
    <p><span>©</span> <span id="currentYear">2026</span> <strong>PALPRINTS</strong> <span data-i18n="allRightsReserved">جميع الحقوق محفوظة.</span></p>
  </footer>
</body>
</html>
