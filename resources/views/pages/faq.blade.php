@extends('layouts.home-page')

@section('title', 'الأسئلة الشائعة')

@section('content')
  <section class="about-block">
    <h1>الأسئلة الشائعة</h1>
    <p>أجوبة سريعة على أكثر ما يسأل عنه العملاء والمصممون والمطابع. إذا لم تجد جوابك، <a class="about-link" href="{{ route('contact') }}">تواصل معنا</a>.</p>
  </section>

  @if ($customText = \App\Support\SitePages::custom('faq'))
    <section class="about-block about-faq">{!! \App\Support\SitePages::renderFaq($customText) !!}</section>
  @else
  <section class="about-block about-faq">
    <details>
      <summary>ما هي PalPrints؟</summary>
      <p>منصة فلسطينية للطباعة عند الطلب، تجمع العملاء والمصممين والمطابع في مكان واحد. تعرّف أكثر في <a class="about-link" href="{{ route('about') }}">صفحة عن المنصة</a>.</p>
    </details>
    <details>
      <summary>كيف أطلب منتجًا مخصصًا؟</summary>
      <p>اختر المنتج، ثم اختر تصميمًا جاهزًا أو صمّم بنفسك في الاستوديو، وأضفه إلى السلة وأكمل الطلب بالدفع وطريقة التوصيل.</p>
    </details>
    <details>
      <summary>هل أستطيع رفع تصميمي الخاص؟</summary>
      <p>نعم، يمكنك رفع تصميمك والتعديل عليه داخل استوديو التصميم قبل إضافته إلى السلة.</p>
    </details>
    <details>
      <summary>كيف أتابع حالة طلبي؟</summary>
      <p>بعد تسجيل الدخول افتح صفحة <a class="about-link" href="{{ $toRole('customer', 'customer.orders') }}">طلباتي</a> لترى حالة كل طلب من الدفع حتى التسليم.</p>
    </details>
    <details>
      <summary>كيف أنضم كمصمم؟</summary>
      <p><a class="about-link" href="{{ route('register', ['account_type' => 'designer']) }}">أنشئ حساب مصمم</a>، وبعد اعتماد حسابك يمكنك رفع تصاميمك ومتابعة المبيعات والأرباح من لوحة المصمم.</p>
    </details>
    <details>
      <summary>كيف تنضم مطبعتي إلى المنصة؟</summary>
      <p><a class="about-link" href="{{ route('register', ['account_type' => 'print_provider']) }}">سجّل مطبعتك</a>، وبعد اعتماد الحساب تحدد المنتجات التي تدعمها مطبعتك وتبدأ باستقبال الطلبات الجاهزة للتنفيذ.</p>
    </details>
    <details>
      <summary>ما طرق الدفع المتاحة؟</summary>
      <p>الدفع عبر التحويل بإحدى الطرق المتاحة في صفحة الدفع (بال باي، جوال باي، بنك فلسطين)، ثم ترفع صورة إشعار التحويل مع الطلب. تراجعه الإدارة وتعتمده قبل بدء التنفيذ.</p>
    </details>
    <details>
      <summary>متى يبدأ تنفيذ طلبي؟</summary>
      <p>بعد أن تعتمد الإدارة إشعار الدفع، يُوجَّه الطلب إلى مطبعة مناسبة، وتبدأ التنفيذ عند قبولها له. تتابع كل ذلك من صفحة طلباتي.</p>
    </details>
    <details>
      <summary>هل أستطيع إلغاء طلبي؟</summary>
      <p>نعم، ما دام لم يبدأ تنفيذه، من صفحة طلباتي. بعد بدء الطباعة لا يمكن الإلغاء لأن المنتج يُصنع خصيصًا لك. التفاصيل في <a class="about-link" href="{{ route('returns') }}">سياسة الاسترجاع</a>.</p>
    </details>
    <details>
      <summary>كم تكلفة الشحن وكم يستغرق التوصيل؟</summary>
      <p>تظهر رسوم الشحن في صفحة الدفع قبل تأكيد الطلب. تعتمد مدة الوصول على وقت تنفيذ المطبعة ثم التوصيل إلى منطقتك. اقرأ <a class="about-link" href="{{ route('shipping') }}">الشحن والتوصيل</a>.</p>
    </details>
    <details>
      <summary>وصلني منتج فيه عيب، ماذا أفعل؟</summary>
      <p>أرسل لنا طلب دعم مع رقم الطلب وصور واضحة للمشكلة، وسنراجع حالتك ونرد عليك. راجع <a class="about-link" href="{{ route('returns') }}">سياسة الاسترجاع</a>.</p>
    </details>
    <details>
      <summary>كيف يربح المصمم والمطبعة؟</summary>
      <p>من لوحة المصمم أو المطبعة تتابع الأرباح وتطلب سحب الرصيد المتاح، وتراجع الإدارة طلبات السحب قبل صرفها. تفاصيل العمولة والحد الأدنى للسحب في <a class="about-link" href="{{ route('terms') }}">الشروط والأحكام</a>.</p>
    </details>
    <details>
      <summary>لماذا لم يظهر تصميمي بعد رفعه؟</summary>
      <p>تُراجَع التصاميم من الإدارة قبل نشرها. ستصلك إشعارات بالموافقة أو بسبب الرفض، ويمكنك متابعة الحالة من صفحة تصاميمي.</p>
    </details>
    <details>
      <summary>كيف تُحمى بياناتي؟</summary>
      <p>نستخدم بياناتك لتنفيذ طلباتك وتأمين حسابك فقط، ولا نبيعها. التفاصيل في <a class="about-link" href="{{ route('privacy') }}">سياسة الخصوصية</a>.</p>
    </details>
    <details>
      <summary>كيف أتواصل مع الدعم؟</summary>
      <p>من صفحة <a class="about-link" href="{{ route('contact') }}">تواصل معنا</a> ستجد طرق التواصل المتاحة.</p>
    </details>
  </section>
  @endif
@endsection
