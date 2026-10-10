@extends('layouts.home-page')

@section('title', 'تواصل معنا')

@section('content')
  <section class="about-block">
    <h1>تواصل معنا</h1>
    <p>فريق PalPrints جاهز لمساعدتك في أي استفسار عن الطلبات أو التصاميم أو التسجيل في المنصة.</p>
  </section>

  @php
    $contactEmail = \App\Support\PlatformSettings::get('general', 'admin_email');
    $contactPhone = \App\Support\PlatformSettings::get('general', 'contact_phone');
  @endphp
  @if ($contactEmail || $contactPhone)
    <section class="about-block">
      <h2>تواصل مباشر</h2>
      <p>تستطيع مراسلتنا أو الاتصال بنا حتى لو لم يكن لديك حساب.</p>
      <ul class="about-list">
        @if ($contactEmail)
          <li>البريد الإلكتروني: <a class="about-link" href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></li>
        @endif
        @if ($contactPhone)
          <li>الهاتف: <a class="about-link" href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}" dir="ltr">{{ $contactPhone }}</a></li>
        @endif
      </ul>
    </section>
  @endif

  <section class="about-block">
    <h2>للمسجّلين في المنصة</h2>
    <ul class="about-list">
      <li>لديك طلب أو استفسار عن طلب؟ افتح تذكرة من صفحة الدعم في حسابك: <a class="about-link" href="{{ $toRole('customer', 'customer.support') }}">الدعم للعملاء</a>.</li>
      <li>مصمم؟ تواصل من صفحة الدعم في <a class="about-link" href="{{ $toRole('designer', 'designer.support') }}">لوحة المصمم</a>.</li>
      <li>صاحب مطبعة؟ تواصل من صفحة الدعم في <a class="about-link" href="{{ $toRole('print_provider', 'print-provider.support') }}">لوحة المطبعة</a>.</li>
      <li>قبل أن تسأل، قد تجد جوابك في <a class="about-link" href="{{ route('faq') }}">الأسئلة الشائعة</a>.</li>
    </ul>
  </section>

  <section class="about-block about-block--closing">
    <p>ليس لديك حساب بعد؟</p>
    <div class="about-actions">
      <a class="about-button about-button--primary" href="{{ route('register') }}">أنشئ حسابًا</a>
      <a class="about-button about-button--outline" href="{{ route('login') }}">تسجيل الدخول</a>
    </div>
  </section>
@endsection
