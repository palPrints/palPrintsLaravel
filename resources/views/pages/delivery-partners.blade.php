@extends('layouts.home-page')

@section('title', 'شركاء التوصيل')

@section('content')
  <section class="about-block">
    <h1>شركاء التوصيل</h1>
    <p>نعمل مع شركاء توصيل يوصلون طلبات PalPrints من المطبعة إلى باب العميل. إذا كنت شركة توصيل وتود الانضمام إلى شبكتنا، يسعدنا أن نسمع منك.</p>
  </section>

  <section class="about-block">
    <h2>للانضمام كشريك توصيل</h2>
    <p>تواصل معنا وعرّفنا بشركتك والمناطق التي تغطيها، وسنعود إليك.</p>
    <div class="about-actions">
      <a class="about-button about-button--primary" href="{{ route('contact') }}">تواصل معنا</a>
      <a class="about-button about-button--outline" href="{{ route('shipping') }}">الشحن والتوصيل</a>
    </div>
  </section>
@endsection
