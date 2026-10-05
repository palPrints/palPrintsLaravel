@extends('printProvider.layouts.app')

@section('title', 'الإشعارات')

@section('bodyClass', 'print-notifications-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/notifications.css') }}?v={{ filemtime(public_path('front/css/printProvider/notifications.css')) }}">
@endpush

@php
    $icons = [
        'approval.submitted' => 'bi-send-check',
        'approval.approved' => 'bi-patch-check',
        'approval.rejected' => 'bi-x-octagon',
        'support.reply' => 'bi-headset',
        'withdrawal_review' => 'bi-cash-coin',
    ];
@endphp

@section('content')
    <nav class="breadcrumb" aria-label="مسار التنقل">
        <a href="{{ route('print-provider.dashboard') }}">لوحة التحكم</a>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span aria-current="page">الإشعارات</span>
    </nav>

    <header class="notifications-heading">
        <span class="notifications-heading__icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
        <div>
            <h1>الإشعارات</h1>
            <p>{{ $unreadCount > 0 ? 'لديك '.$unreadCount.' إشعار غير مقروء.' : 'لا توجد إشعارات غير مقروءة.' }}</p>
        </div>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('print-provider.notifications.read-all') }}">
                @csrf
                <button type="submit" class="notifications-heading__action"><i class="bi bi-check2-all" aria-hidden="true"></i> تعليم الكل كمقروء</button>
            </form>
        @endif
    </header>

    <section class="notifications-card" aria-label="قائمة الإشعارات">
        @forelse($notifications as $notification)
            <form method="POST" action="{{ route('print-provider.notifications.read', $notification) }}">
                @csrf
                <button type="submit" class="notification-row{{ $notification->is_read ? '' : ' is-unread' }}">
                    <span class="notification-row__icon"><i class="bi {{ $icons[$notification->type] ?? (str_starts_with((string) $notification->type, 'order') ? 'bi-bag-check' : 'bi-bell') }}" aria-hidden="true"></i></span>
                    <span class="notification-row__body">
                        <strong>{{ $notification->title }}</strong>
                        <small>{{ $notification->message }}</small>
                        <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->locale('ar')->diffForHumans() }}</time>
                    </span>
                    @unless($notification->is_read)<span class="notification-row__dot" aria-label="غير مقروء"></span>@endunless
                </button>
            </form>
        @empty
            <div class="notifications-empty">
                <span aria-hidden="true"><i class="bi bi-bell-slash"></i></span>
                <h2>لا توجد إشعارات حتى الآن</h2>
                <p>ستظهر هنا إشعارات اعتماد حسابك والطلبات الجديدة وردود الدعم.</p>
            </div>
        @endforelse
    </section>

    @if($notifications->hasPages())
        <nav class="notifications-pagination" aria-label="صفحات الإشعارات">
            @if($notifications->onFirstPage())
                <button type="button" disabled aria-label="الصفحة السابقة"><i class="bi bi-chevron-right"></i></button>
            @else
                <a href="{{ $notifications->previousPageUrl() }}" aria-label="الصفحة السابقة"><i class="bi bi-chevron-right"></i></a>
            @endif
            <span>صفحة {{ $notifications->currentPage() }} من {{ $notifications->lastPage() }}</span>
            @if($notifications->hasMorePages())
                <a href="{{ $notifications->nextPageUrl() }}" aria-label="الصفحة التالية"><i class="bi bi-chevron-left"></i></a>
            @else
                <button type="button" disabled aria-label="الصفحة التالية"><i class="bi bi-chevron-left"></i></button>
            @endif
        </nav>
    @endif
@endsection
