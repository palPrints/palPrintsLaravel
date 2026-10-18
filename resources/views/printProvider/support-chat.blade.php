@extends('printProvider.layouts.app')

@section('title', 'طلب الدعم')

@section('bodyClass', 'print-account-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/account.css') }}?v={{ filemtime(public_path('front/css/printProvider/account.css')) }}">
@endpush

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('print-provider.dashboard') }}">الرئيسية</a>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span>مركز المساعدة</span>
    </div>

    <header class="page-heading support-heading">
        <span class="heading-icon"><i class="bi bi-headphones" aria-hidden="true"></i></span>
        <div><h1>مركز المساعدة</h1><p>نساعدك في حل المشكلات ومتابعة طلبات الدعم من مكان واحد.</p></div>
        <span class="acct-role-badge"><i class="bi bi-printer" aria-hidden="true"></i><span>حساب مطبعة</span></span>
    </header>

    @include('partials.support-chat')
@endsection
