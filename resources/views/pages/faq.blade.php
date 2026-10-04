@extends('layouts.home-page')

@section('title', 'الأسئلة الشائعة')

@section('content')
  <section class="about-block">
    <h1>الأسئلة الشائعة</h1>
    <p>أجوبة سريعة على أكثر ما يسأل عنه العملاء والمصممون والمطابع. إذا لم تجد جوابك، <a class="about-link" href="{{ route('contact') }}">تواصل معنا</a>.</p>
  </section>

  <section class="about-block about-faq">
    <details>
      <summary>ما هي PalPrints؟</summary>
      <p>منصة فلسطينية للطباعة عند الطلب، تجمع العملاء والمصممين والمطابع وشركاء التوصيل في مكان واحد. تعرّف أكثر في <a class="about-link" href="{{ route('about') }}">صفحة عن المنصة</a>.</p>
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
      <summary>كيف أتواصل مع الدعم؟</summary>
      <p>من صفحة <a class="about-link" href="{{ route('contact') }}">تواصل معنا</a> ستجد طرق التواصل المتاحة.</p>
    </details>
  </section>
@endsection
