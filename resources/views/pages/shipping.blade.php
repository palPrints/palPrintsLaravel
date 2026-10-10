@extends('layouts.home-page')

@section('title', 'الشحن والتوصيل')

@section('content')
  @if ($customText = \App\Support\SitePages::custom('shipping'))
    {!! \App\Support\SitePages::render($customText) !!}
  @else
  <section class="about-block">
    <h1>الشحن والتوصيل</h1>
    <p>بعد أن تطبع المطبعة طلبك، يصلك الطلب على العنوان الذي أدخلته، ويمكنك متابعة حالته أولًا بأول من صفحة <a class="about-link" href="{{ $toRole('customer', 'customer.orders') }}">طلباتي</a>.</p>
  </section>

  <section class="about-block">
    <h2>مراحل الطلب حتى يصلك</h2>
    <ol class="about-steps-list">
      <li>
        <h3>بانتظار مراجعة الدفع</h3>
        <p>بعد إتمام الطلب ورفع إشعار الدفع، تراجع الإدارة الإشعار. لا يبدأ التنفيذ قبل اعتماده.</p>
      </li>
      <li>
        <h3>قيد التنفيذ</h3>
        <p>بعد اعتماد الدفع نوجّه طلبك إلى مطبعة مناسبة، وتقبله وتبدأ الطباعة.</p>
      </li>
      <li>
        <h3>جاهز للتسليم</h3>
        <p>تنتهي الطباعة ويصبح الطلب جاهزًا لتسليمه إلى شركة التوصيل.</p>
      </li>
      <li>
        <h3>قيد التوصيل</h3>
        <p>الطلب في الطريق إليك على العنوان الذي أدخلته.</p>
      </li>
      <li>
        <h3>تم التنفيذ</h3>
        <p>وصل الطلب إليك واكتملت العملية.</p>
      </li>
    </ol>
  </section>

  <section class="about-block">
    <h2>رسوم الشحن</h2>
    <p>تُضاف رسوم الشحن إلى الطلب وتظهر بوضوح في صفحة الدفع قبل أن تؤكد طلبك، فلا توجد رسوم مفاجئة بعد ذلك. الرسوم حاليًا ثابتة للطلب الواحد مهما كان عدد المنتجات فيه، وقيمتها <strong>{{ number_format((float) \App\Support\PlatformSettings::get('fees', 'shipping_cost', 5), 2) }} ₪</strong>.</p>
  </section>

  <section class="about-block">
    <h2>مدة التجهيز والتوصيل</h2>
    <p>تُطبع كل المنتجات عند الطلب، لذلك تعتمد مدة وصول طلبك على وقت تنفيذ المطبعة التي تتولى طلبك، ثم على مدة التوصيل إلى منطقتك. تتبع حالة الطلب من صفحة «طلباتي» لمعرفة المرحلة التي وصل إليها.</p>
  </section>

  <section class="about-block">
    <h2>عنوان التوصيل</h2>
    <ul class="about-list">
      <li>اكتب مدينتك وعنوانك بالتفصيل، وأدخل رقم هاتف تستطيع الرد عليه، فقد يتصل بك مندوب التوصيل.</li>
      <li>أي خطأ في العنوان أو الهاتف قد يؤخر وصول الطلب، ولا نتحمل التأخير الناتج عنه.</li>
      <li>تراجع العنوان قبل إتمام الدفع، فبعد بدء التنفيذ يصعب تغييره.</li>
    </ul>
  </section>

  <section class="about-block">
    <h2>استلام الطلب</h2>
    <ul class="about-list">
      <li>افحص الشحنة عند الاستلام، وإن وجدت تلفًا ظاهرًا أو خطأ في الطلب فتواصل معنا بأسرع وقت مع صور واضحة.</li>
      <li>للتفاصيل راجع <a class="about-link" href="{{ route('returns') }}">سياسة الاسترجاع</a>.</li>
    </ul>
  </section>

  <section class="about-block about-block--closing">
    <h3>عندك سؤال عن طلب قائم؟</h3>
    <div class="about-actions">
      <a class="about-button about-button--primary" href="{{ $toRole('customer', 'customer.orders') }}">طلباتي</a>
      <a class="about-button about-button--outline" href="{{ route('contact') }}">تواصل معنا</a>
    </div>
  </section>
  @endif
@endsection
