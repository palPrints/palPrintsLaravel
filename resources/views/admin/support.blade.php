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
    <nav class="admin-settings-breadcrumb" aria-label="مسار التنقل"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a><i class="bi bi-chevron-left"></i><span>الدعم الفني</span></nav>
    <header class="support-clean-title"><div><h1>الدعم الفني</h1><p>إدارة مشاكل العملاء والمصممين والمطابع.</p></div><span><i class="bi bi-chat-dots"></i><b id="supportNewCount">{{ $newCount }}</b> مشاكل جديدة</span></header>

    <section class="support-clean-card support-clean-list" aria-labelledby="incomingProblemsTitle">
        <header><div><h2 id="incomingProblemsTitle">المشاكل الواردة</h2></div></header>
        <div class="support-clean-table" role="table" aria-label="جدول المشاكل الواردة">
        <div class="support-clean-table-head" role="row" aria-hidden="true"><span>المستخدم</span><span>المشكلة</span><span>التصنيف</span><span>الحالة</span><span>وقت الإرسال</span><span>الإجراء</span></div>
        <div id="supportRequestList" role="rowgroup">
          @forelse ($tickets as $index => $ticket)
              <button class="support-clean-row{{ $index === 0 ? ' active' : '' }}" type="button"
                  data-support-request
                  data-role="{{ $ticket['role'] }}"
                  data-status="{{ $ticket['status'] }}"
                  data-id="{{ $ticket['id'] }}"
                  data-name="{{ $ticket['name'] }}"
                  data-role-label="{{ $ticket['role_label'] }}"
                  data-initials="{{ $ticket['initials'] }}"
                  data-email="{{ $ticket['email'] }}"
                  data-subject="{{ $ticket['subject'] }}"
                  data-category="{{ $ticket['category'] }}"
                  data-time="{{ $ticket['time'] }}"
                  data-message="{{ $ticket['message'] }}"
                  data-reply="{{ $ticket['reply'] }}"
                  data-review-url="{{ $ticket['reviewUrl'] }}"
                  data-status-url="{{ $ticket['statusUrl'] }}">
                  <span class="support-clean-user"><b class="{{ $roleClasses[$ticket['role']] ?? '' }}">{{ $ticket['initials'] }}</b><span><strong>{{ $ticket['name'] }}</strong><small>{{ $ticket['role_label'] }}</small></span></span>
                  <strong>{{ $ticket['subject'] }}</strong>
                  <span>{{ $ticket['category'] }}</span>
                  <span class="support-clean-status is-{{ $ticket['status'] }}">{{ $statusLabels[$ticket['status']] ?? $ticket['status'] }}</span>
                  <time>{{ $ticket['time'] }}</time>
                  <i class="bi bi-chevron-left"></i>
              </button>
          @empty
          @endforelse
        </div></div>
        <p class="support-list-empty" id="supportRequestsEmpty" @if ($tickets->isNotEmpty()) hidden @endif><i class="bi bi-inbox" aria-hidden="true"></i><span>{{ $tickets->isEmpty() ? 'لا توجد طلبات دعم مرسلة بعد.' : 'لا توجد مشاكل مطابقة.' }}</span></p>
      </section>

      <dialog class="support-reference-dialog" id="supportProblemDialog" aria-labelledby="problemDetailsTitle">
        <div class="support-reference-content">
          <header><h2 id="problemDetailsTitle">تفاصيل المشكلة</h2><button type="button" id="closeSupportDialog" aria-label="إغلاق"><i class="bi bi-x-lg"></i></button></header>
          <section class="support-reference-heading"><div><h3 id="supportDetailSubject"></h3><div><span id="supportDetailCategory"></span><code id="supportDetailId"></code></div></div><span id="supportDialogStatus"></span></section>
          <dl class="support-reference-metadata">
            <div><dt>المستخدم</dt><dd><span id="supportDetailAvatar"></span><b id="supportDetailName"></b><em id="supportDetailRole" class="request-role"></em></dd></div>
            <div><dt>البريد الإلكتروني</dt><dd><a id="supportDetailEmail" href="#"></a></dd></div>
            <div><dt>وقت الإرسال</dt><dd id="supportDetailTime"></dd></div>
          </dl>
          <section class="support-reference-message"><h3>وصف المشكلة</h3><p id="supportDetailMessage"></p><span id="supportMessageSender" hidden></span></section>
          <div class="support-reply-history" id="supportReplyHistory" hidden><span><i class="bi bi-headset"></i></span><div><strong>إدارة PalPrints</strong><p id="supportPreviousReply"></p><time>الآن</time></div></div>
          <form class="support-reference-reply" id="supportReplyForm" hidden><label for="supportReplyText">الرد على المستخدم</label><textarea id="supportReplyText" rows="4" placeholder="اكتب الرد هنا..." required></textarea><div><button type="button" id="cancelSupportReply">إلغاء</button><button type="submit"><i class="bi bi-send"></i>إرسال الرد</button></div></form>
          <footer><button class="support-resolve-button" id="resolveSupportRequest" type="button"><i class="bi bi-check-circle"></i>تحديد كمحلولة</button><button class="support-reply-button" id="showSupportReply" type="button"><i class="bi bi-reply"></i>الرد على المستخدم</button></footer>
          <select id="supportRequestStatus" hidden><option value="new">جديدة</option><option value="open">قيد المعالجة</option><option value="resolved">تم الحل</option></select>
        </div>
      </dialog>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminDashboard.js').'?v='.filemtime(public_path('front/js/admin/adminDashboard.js')) }}"></script>
    <script src="{{ asset('front/js/admin/adminTools.js').'?v='.filemtime(public_path('front/js/admin/adminTools.js')) }}"></script>
@endpush
