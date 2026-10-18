@extends('admin.layouts.app')

@section('title', 'الدعم الفني')
@section('body-class', 'admin-tools-page')
@section('main-id', 'adminSupportMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/printingEarnings.css').'?v='.filemtime(public_path('front/css/admin/printingEarnings.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminDashboard.css').'?v='.filemtime(public_path('front/css/admin/adminDashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminTools.css').'?v='.filemtime(public_path('front/css/admin/adminTools.css')) }}">
@endpush

@section('content')
<main class="admin-main admin-tools-main support-clean-main" id="adminSupportMain">
    <nav class="admin-breadcrumb" aria-label="مسار التنقل">
        <a href="{{ route('admin.dashboard') }}">الرئيسية</a>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span aria-current="page">الدعم الفني</span>
    </nav>
    <header class="support-clean-title"><div><h1>الدعم الفني</h1><p>إدارة مشاكل العملاء والمصممين والمطابع.</p></div></header>

    @include('partials.support-chat', [
        'thread' => $thread,
        'person' => $person,
        'backUrl' => route('admin.support'),
        'messagesUrl' => route('admin.support.messages', $ticket),
        'replyUrl' => route('admin.support.reply', $ticket),
        'statusUrl' => route('admin.support.status', $ticket),
    ])
</main>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminDashboard.js').'?v='.filemtime(public_path('front/js/admin/adminDashboard.js')) }}"></script>
@endpush
