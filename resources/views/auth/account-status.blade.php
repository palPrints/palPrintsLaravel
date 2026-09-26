<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  @include('auth.partials.head', [
      'title' => 'حالة الحساب',
      'description' => 'متابعة حالة مراجعة حسابك في PalPrints',
      'css' => 'forgot-password',
  ])
</head>
<body>
  @php
    $status = $accountApprovalStatus ?? 'draft';
    $needsChanges = in_array($status, ['rejected', 'changes_requested'], true);

    [$icon, $eyebrow, $title, $description] = match (true) {
        $status === 'approved' => ['bi-patch-check', 'حسابك جاهز', 'تم اعتماد حسابك', 'يمكنك الآن استخدام جميع خصائص حسابك في PalPrints.'],
        in_array($status, ['submitted', 'under_review'], true) => ['bi-hourglass-split', 'قيد المراجعة', 'بانتظار مراجعة حسابك', 'تم استلام طلبك، ويقوم فريق PalPrints الآن بمراجعة بيانات حسابك.'],
        $needsChanges => ['bi-exclamation-triangle', 'يلزم تعديل', 'حسابك يحتاج إلى تعديل', 'راجع ملاحظات الإدارة وعدّل بياناتك ثم أعد إرسال الطلب.'],
        default => ['bi-person-vcard', 'حالة الحساب', 'أكمل ملفك وأرسل الطلب', 'أكمل بيانات ملفك الشخصي ثم أرسله للمراجعة لتفعيل حسابك.'],
    };

    // 0 = not submitted yet, 1 = under review, 2 = approved
    $stage = match (true) {
        $status === 'approved' => 2,
        in_array($status, ['submitted', 'under_review'], true) => 1,
        default => 0,
    };
    $steps = ['تم استلام الطلب', 'قيد المراجعة', 'تفعيل الحساب'];
  @endphp

  <main class="recovery-layout">
    <section class="recovery-panel" aria-labelledby="statusTitle">
      <a href="{{ route('home') }}" class="recovery-wordmark" aria-label="العودة إلى الصفحة الرئيسية">
        <img src="{{ asset('front/auth/images/palprints-wordmark-approved.png') }}" alt="PalPrints">
      </a>

      <div class="recovery-card">
        <div class="recovery-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></div>
        <header class="recovery-heading">
          <span class="eyebrow">{{ $eyebrow }}</span>
          <h1 id="statusTitle">{{ $title }}</h1>
          <p>{{ $description }}</p>
        </header>

        @if ($accountReviewNote)
          <p class="form-status is-error" role="alert"><strong>ملاحظة الإدارة:</strong> {{ $accountReviewNote }}</p>
        @endif

        <ol class="status-steps" aria-label="مراحل مراجعة الحساب">
          @foreach ($steps as $index => $label)
            <li @class(['status-step', 'is-done' => $index < $stage || ($index === 0 && $stage > 0), 'is-current' => $index === $stage])>
              <span class="status-step__marker" aria-hidden="true">{{ $index < $stage ? '✓' : $index + 1 }}</span>
              <span>{{ $label }}</span>
            </li>
          @endforeach
        </ol>

        <dl class="status-details">
          <div><dt>نوع الحساب</dt><dd>{{ $accountRoleLabel ?? '—' }}</dd></div>
          <div><dt>رقم الطلب</dt><dd dir="ltr">{{ $accountReference ?? '—' }}</dd></div>
        </dl>

        <a class="submit-button" href="{{ ($needsChanges || $stage === 0) && $accountRole === 'designer' ? route('designer.profile') : route('dashboard') }}">
          <span>{{ $needsChanges || $stage === 0 ? 'إكمال الملف الشخصي' : 'متابعة' }}</span>
          <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>

        <a class="back-link" href="{{ route('home') }}"><i class="bi bi-arrow-right" aria-hidden="true"></i> تصفح المنصة كزائر</a>
      </div>
    </section>
  </main>
</body>
</html>
