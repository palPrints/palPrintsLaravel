@extends('layouts.home-page')

@section('title', 'سياسة الاسترجاع')

@section('content')
  <section class="about-block">
    <h1>سياسة الاسترجاع</h1>
    <p>بما أن كل منتج في PalPrints يُطبع حسب الطلب وبتصميم مخصص، فإن شروط الاسترجاع والاستبدال تعتمد على حالة الطلب ونوع المشكلة.</p>
  </section>

  <section class="about-block">
    <h2>التفاصيل قيد الإعداد</h2>
    <p>سيتم نشر النص النهائي لسياسة الاسترجاع (المدة والحالات المشمولة وآلية الطلب) هنا قبل إطلاق المنصة. إلى حين ذلك، إذا واجهتك مشكلة في طلبك فتواصل معنا وسنساعدك.</p>
    <div class="about-actions">
      <a class="about-button about-button--primary" href="{{ route('contact') }}">تواصل معنا</a>
      <a class="about-button about-button--outline" href="{{ $toRole('customer', 'customer.orders') }}">طلباتي</a>
    </div>
  </section>
@endsection
