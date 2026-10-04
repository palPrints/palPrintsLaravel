@extends('layouts.home-page')

@section('title', 'الشحن والتوصيل')

@section('content')
  <section class="about-block">
    <h1>الشحن والتوصيل</h1>
    <p>بعد أن تطبع المطبعة طلبك، يتولى شركاء التوصيل إيصاله إليك، ويمكنك متابعة حالته أولًا بأول من صفحة <a class="about-link" href="{{ $toRole('customer', 'customer.orders') }}">طلباتي</a>.</p>
  </section>

  <section class="about-block">
    <h2>التفاصيل قيد الإعداد</h2>
    <p>سيتم نشر تفاصيل مناطق التوصيل وأسعار الشحن ومدد التسليم هنا قبل إطلاق المنصة. لأي استفسار عن طلب قائم تواصل معنا.</p>
    <div class="about-actions">
      <a class="about-button about-button--primary" href="{{ route('contact') }}">تواصل معنا</a>
    </div>
  </section>
@endsection
