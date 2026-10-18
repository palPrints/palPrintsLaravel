@extends('admin.layouts.app')

@section('title', 'الدعم الفني')
@section('body-class', 'admin-tools-page admin-support-inbox-page')
@section('main-id', 'adminSupportMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/printingEarnings.css').'?v='.filemtime(public_path('front/css/admin/printingEarnings.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminDashboard.css').'?v='.filemtime(public_path('front/css/admin/adminDashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminTools.css').'?v='.filemtime(public_path('front/css/admin/adminTools.css')) }}">
@endpush

@php
    $statusLabels = ['new' => 'جديدة', 'open' => 'قيد المعالجة', 'resolved' => 'تم الحل'];
    $roleClasses = ['customer' => '', 'designer' => 'is-purple', 'printer' => 'is-orange'];
@endphp

@section('content')
<main class="admin-main admin-tools-main support-clean-main" id="adminSupportMain">
    @include('admin.partials.breadcrumb', ['label' => 'الدعم الفني'])
    <header class="support-clean-title"><div><h1>الدعم الفني</h1><p>إدارة مشاكل العملاء والمصممين والمطابع.</p></div><span><i class="bi bi-chat-dots"></i><b id="supportNewCount">{{ $newCount }}</b> مشاكل جديدة</span></header>

    <section class="sinbox" aria-label="المشاكل الواردة">
        @if ($tickets->isEmpty())
            <div class="sinbox-empty">
                <span><i class="bi bi-inbox" aria-hidden="true"></i></span>
                <h2>لا توجد طلبات دعم بعد</h2>
                <p>لما يرسل عميل أو مصمم أو مطبعة طلب مساعدة بيظهر هنا، وبتوصلك إشعار فورًا.</p>
            </div>
        @else
            <header class="sinbox-bar">
                <div class="sinbox-tabs" role="tablist" aria-label="تصفية الحالة">
                    @foreach ([['active', 'النشطة'], ['new', 'جديدة'], ['open', 'قيد المعالجة'], ['resolved', 'تم الحل'], ['all', 'الكل']] as [$key, $label])
                        <button type="button" class="{{ $loop->first ? 'active' : '' }}" data-request-filter="{{ $key }}" role="tab">{{ $label }} <b>{{ $counts[$key] }}</b></button>
                    @endforeach
                </div>
            </header>

            <div class="sinbox-table" role="table" aria-label="جدول المشاكل الواردة">
                <div class="sinbox-head" role="row" aria-hidden="true"><span>المستخدم</span><span>المشكلة</span><span>الحالة</span><span>آخر نشاط</span><span></span></div>
                <div id="supportRequestList" role="rowgroup">
                    @foreach ($tickets as $ticket)
                        <a class="sinbox-row{{ $ticket['status'] === 'new' ? ' is-unread' : '' }}" href="{{ $ticket['url'] }}"
                            data-support-request
                            data-role="{{ $ticket['role'] }}"
                            data-status="{{ $ticket['status'] }}"
                            data-name="{{ $ticket['name'] }}"
                            data-subject="{{ $ticket['subject'] }}"
                            data-category="{{ $ticket['category'] }}"
                            data-message="{{ $ticket['message'] }}">
                            <span class="sinbox-user"><b class="{{ $roleClasses[$ticket['role']] ?? '' }}">{{ $ticket['initials'] }}</b><span><strong>{{ $ticket['name'] }}</strong><small>{{ $ticket['role_label'] }}</small></span></span>
                            <span class="sinbox-subject"><strong>{{ $ticket['subject'] }}</strong><small>{{ $ticket['category'] }} · #{{ $ticket['number'] }}</small></span>
                            <span class="sinbox-status is-{{ $ticket['status'] }}">{{ $statusLabels[$ticket['status']] ?? $ticket['status'] }}</span>
                            <span class="sinbox-time"><time>{{ $ticket['last'] }}</time><small><i class="bi bi-chat-dots" aria-hidden="true"></i> {{ $ticket['count'] }}</small></span>
                            <span class="sinbox-open">عرض المحادثة <i class="bi bi-chevron-left" aria-hidden="true"></i></span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="sinbox-empty is-small" id="supportRequestsEmpty" hidden>
                <span><i class="bi bi-search" aria-hidden="true"></i></span>
                <h2>لا توجد مشاكل مطابقة</h2>
                <p>ما في تذاكر بهاي الحالة حاليًا.</p>
            </div>
        @endif
    </section>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminDashboard.js').'?v='.filemtime(public_path('front/js/admin/adminDashboard.js')) }}"></script>
    <script src="{{ asset('front/js/admin/adminTools.js').'?v='.filemtime(public_path('front/js/admin/adminTools.js')) }}"></script>
@endpush
