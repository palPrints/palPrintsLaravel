@extends('designer.layouts.app')

@section('title', 'طلب الدعم')
@section('body-class', 'settings-page support-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/designer/css/settings.css') }}?v={{ filemtime(public_path('front/designer/css/settings.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/designer/css/support.css') }}?v={{ filemtime(public_path('front/designer/css/support.css')) }}">
@endpush

@section('content')
<main class="support-main" id="designerMain">
    <div class="designer-breadcrumb">
        <a href="{{ route('designer.dashboard') }}">الرئيسية</a>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span>مركز المساعدة</span>
    </div>

    <header class="designer-page-heading support-heading">
        <span class="designer-heading-icon"><i class="bi bi-headphones" aria-hidden="true"></i></span>
        <div><h1>مركز المساعدة</h1><p>نساعدك في حل المشكلات ومتابعة طلبات الدعم من مكان واحد.</p></div>
        <span class="settings-role-badge"><i class="bi bi-person-badge"></i><span>حساب مصمم</span></span>
    </header>

    @include('partials.support-chat')
</main>
@endsection
