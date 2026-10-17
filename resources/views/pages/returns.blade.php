@extends('layouts.home-page')

@section('title', 'سياسة الاسترجاع')

@section('content')
  @if ($customText = \App\Support\SitePages::custom('returns'))
    {!! \App\Support\SitePages::render($customText) !!}
  @else
  <section class="about-block">
    <h1>سياسة الاسترجاع</h1>
    <p>بما أن كل منتج في PalPrints يُطبع حسب الطلب وبتصميم مخصص، فإن شروط الإلغاء والاسترجاع والاستبدال تعتمد على حالة الطلب ونوع المشكلة.</p>
  </section>

  <section class="about-block">
    <h2>إلغاء الطلب</h2>
    <ul class="about-list">
      <li>تستطيع إلغاء طلبك من صفحة <a class="about-link" href="{{ $toRole('customer', 'customer.orders') }}">طلباتي</a> ما دام لم يبدأ تنفيذه.</li>
      <li>بعد أن تبدأ الطباعة لا يمكن الإلغاء، لأن المنتج يُصنع خصيصًا لك.</li>
      <li>إن رُفض إشعار الدفع أو اعتذرت المطبعة عن التنفيذ فسيصلك إشعار بالسبب، ويتواصل معك فريق الدعم لحل الأمر.</li>
    </ul>
  </section>

  <section class="about-block">
    <h2>متى يُقبل الاسترجاع أو الاستبدال؟</h2>
    <p>لا نقبل الاسترجاع لمجرد تغيّر الرأي لأن المنتج مخصص لك. نقبله في هذه الحالات:</p>
    <ul class="about-list">
      <li>عيب في الطباعة أو في المنتج نفسه (مثل لطخات أو قصّ خاطئ أو قماش تالف).</li>
      <li>اختلاف واضح بين ما طلبته وما وصلك (لون أو مقاس أو تصميم غير المطلوب).</li>
      <li>تلف حدث أثناء الشحن.</li>
    </ul>
    <p>قدّم طلبك خلال <strong>{{ \App\Support\PlatformSettings::get('site', 'return_days', 7) }}</strong> أيام من استلام الطلب.</p>
  </section>

  <section class="about-block">
    <h2>كيف تقدّم طلبًا؟</h2>
    <ol class="about-steps-list">
      <li>
        <h3>تواصل معنا بأسرع وقت بعد الاستلام</h3>
        <p>أرسل طلبًا من صفحة الدعم في حسابك، وأرفق رقم الطلب.</p>
      </li>
      <li>
        <h3>أرفق صورًا واضحة</h3>
        <p>صوّر المشكلة والمنتج والتغليف إن كان التلف من الشحن.</p>
      </li>
      <li>
        <h3>نراجع ونرد عليك</h3>
        <p>نراجع حالتك ونقرر إعادة الطباعة أو الاستبدال أو إعادة المبلغ، ونرد عليك من خلال الإشعارات.</p>
      </li>
    </ol>
  </section>

  <section class="about-block">
    <h2>ملاحظات</h2>
    <ul class="about-list">
      <li>الألوان على الشاشة قد تختلف قليلًا عن المطبوع، ولا يُعد ذلك عيبًا.</li>
      <li>التصميم الذي ترفعه أنت يتحمل مسؤولية جودته ودقته صاحبه (مثل دقة الصورة)، وتظهر لك معاينة قبل الطلب.</li>
    </ul>
  </section>

  <section class="about-block about-block--closing">
    <h3>واجهت مشكلة في طلبك؟</h3>
    <div class="about-actions">
      <a class="about-button about-button--primary" href="{{ $toRole('customer', 'customer.support') }}">افتح طلب دعم</a>
      <a class="about-button about-button--outline" href="{{ $toRole('customer', 'customer.orders') }}">طلباتي</a>
    </div>
  </section>
  @endif
@endsection
