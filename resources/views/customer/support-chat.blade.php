@extends('customer.layouts.app')

@section('title', 'طلب الدعم')
@section('meta-description', 'محادثة مع فريق دعم PalPrints')
@section('body-class', 'storefront-page support-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/support.css') }}?v={{ filemtime(public_path('front/css/customer/support.css')) }}">
@endpush

@section('content')
    <section class="profile-content" aria-label="محادثة الدعم">
        <div class="pp-breadcrumb">
            <a href="{{ route('customer.store') }}">الرئيسية</a>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span>مركز المساعدة</span>
        </div>
        <header class="pp-page-heading">
            <span class="pp-heading-icon"><i class="bi bi-headphones" aria-hidden="true"></i></span>
            <div><h1 id="pageTitle">مركز المساعدة</h1><p>نساعدك في حل المشكلات ومتابعة طلبات الدعم من مكان واحد.</p></div>
            <span class="pp-role-badge"><i class="bi bi-person-badge"></i><span>حساب عميل</span></span>
        </header>
        @include('partials.support-chat')
    </section>
@endsection
