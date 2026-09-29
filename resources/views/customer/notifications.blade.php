{{--
    Real notifications page backed by the `user_notifications` table via
    CustomerNotificationController — no demo data. Structured like
    settings.blade.php / orders.blade.php (pp-breadcrumb, pp-page-heading,
    app-card) to stay visually consistent with the rest of the account area.
--}}
@extends('customer.layouts.app')

@section('title', 'الإشعارات')
@section('meta-description', 'كل إشعارات حساب عميل PalPrints')
@section('body-class', 'storefront-page notifications-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/notifications.css') }}?v={{ filemtime(public_path('front/css/customer/notifications.css')) }}">
@endpush

@php
    $icons = [
        'design_published' => 'bi-patch-check',
        'design_review' => 'bi-hourglass-split',
        'design_rejected' => 'bi-x-octagon',
        'withdrawal_review' => 'bi-cash-coin',
        'approval.submitted' => 'bi-send-check',
        'support.reply' => 'bi-headset',
    ];
@endphp

@section('content')
    <section class="profile-content" aria-labelledby="pageTitle">
        <div class="pp-breadcrumb">
            <a href="{{ route('customer.store') }}">الرئيسية</a>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span>الإشعارات</span>
        </div>
        <header class="pp-page-heading">
            <span class="pp-heading-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
            <div>
                <h1 id="pageTitle">الإشعارات</h1>
                <p>{{ $unreadCount > 0 ? 'لديك '.$unreadCount.' إشعار غير مقروء.' : 'لا توجد إشعارات غير مقروءة.' }}</p>
            </div>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn-brand"><i class="bi bi-check2-all"></i> تعليم الكل كمقروء</button>
                </form>
            @endif
        </header>

        <div class="app-card notifications-page-list" aria-label="قائمة الإشعارات">
            @forelse($notifications as $notification)
                <form method="POST" action="{{ route('customer.notifications.read', $notification) }}">
                    @csrf
                    <button type="submit" class="notification-item{{ $notification->is_read ? '' : ' is-unread' }}">
                        <span class="notification-item__icon"><i class="bi {{ $icons[$notification->type] ?? 'bi-bell' }}" aria-hidden="true"></i></span>
                        <span class="notification-item__body">
                            <strong>{{ $notification->title }}</strong>
                            <small>{{ $notification->message }}</small>
                            <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->locale('ar')->diffForHumans() }}</time>
                        </span>
                    </button>
                </form>
            @empty
                <div class="notifications-page-empty">
                    <span class="notifications-page-empty__icon"><i class="bi bi-bell-slash"></i></span>
                    <h2>لا توجد إشعارات حتى الآن</h2>
                    <p>لما يصير في تحديث على طلباتك أو حسابك، رح يظهر هون.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <nav class="pagination notifications-pagination" aria-label="صفحات الإشعارات">
                @if($notifications->onFirstPage())
                    <button type="button" disabled aria-label="الصفحة السابقة"><i class="bi bi-chevron-right"></i></button>
                @else
                    <a href="{{ $notifications->previousPageUrl() }}" aria-label="الصفحة السابقة"><i class="bi bi-chevron-right"></i></a>
                @endif
                <span class="notifications-pagination__status">صفحة {{ $notifications->currentPage() }} من {{ $notifications->lastPage() }}</span>
                @if($notifications->hasMorePages())
                    <a href="{{ $notifications->nextPageUrl() }}" aria-label="الصفحة التالية"><i class="bi bi-chevron-left"></i></a>
                @else
                    <button type="button" disabled aria-label="الصفحة التالية"><i class="bi bi-chevron-left"></i></button>
                @endif
            </nav>
        @endif
    </section>
@endsection
