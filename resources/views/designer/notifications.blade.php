@extends('designer.layouts.app')

@section('title', 'الإشعارات')
@section('body-class', 'dashboard-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/designer/css/dashboard.css') }}?v={{ filemtime(public_path('front/designer/css/dashboard.css')) }}">
@endpush

@php
    $icons = [
        'design_published' => 'bi-patch-check',
        'design_review' => 'bi-hourglass-split',
        'design_rejected' => 'bi-x-octagon',
        'withdrawal' => 'bi-cash-coin',
        'approval.submitted' => 'bi-send-check',
    ];
@endphp

@section('content')
    <main class="designer-content-main" id="designerMain">
        @include('designer.partials.flash')

        <div class="designer-breadcrumb">
            <a href="{{ route('designer.dashboard') }}">الرئيسية</a>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span>الإشعارات</span>
        </div>

        <header class="designer-page-heading">
            <span class="designer-heading-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
            <div>
                <h1>الإشعارات</h1>
                <p>{{ $unreadCount > 0 ? 'لديك '.$unreadCount.' إشعار غير مقروء.' : 'لا توجد إشعارات غير مقروءة.' }}</p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('designer.notifications.read-all') }}" class="designer-heading-action">
                    @csrf
                    <button type="submit" class="designer-primary-btn"><i class="bi bi-check2-all" aria-hidden="true"></i> تعليم الكل كمقروء</button>
                </form>
            @endif
        </header>

        <section class="notification-list" aria-label="قائمة الإشعارات">
            @forelse ($notifications as $notification)
                <form method="POST" action="{{ route('designer.notifications.read', $notification) }}">
                    @csrf
                    <button type="submit" @class(['notification-item', 'is-unread' => ! $notification->is_read])>
                        <span class="notification-icon"><i class="bi {{ $icons[$notification->type] ?? 'bi-bell' }}" aria-hidden="true"></i></span>
                        <span class="notification-copy">
                            <strong>{{ $notification->title }}</strong>
                            <small>{{ $notification->message }}</small>
                        </span>
                        <time class="notification-time" datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->locale('ar')->diffForHumans() }}</time>
                    </button>
                </form>
            @empty
                <div class="notification-empty">
                    <i class="bi bi-bell-slash" aria-hidden="true"></i>
                    <p>لا توجد إشعارات حتى الآن.</p>
                </div>
            @endforelse
        </section>

        @if ($notifications->hasPages())
            <nav class="designer-pagination" aria-label="التنقل بين الصفحات">
                @if ($notifications->onFirstPage())
                    <span class="designer-outline-btn is-disabled" aria-disabled="true"><i class="bi bi-chevron-right"></i> السابق</span>
                @else
                    <a class="designer-outline-btn" href="{{ $notifications->previousPageUrl() }}"><i class="bi bi-chevron-right"></i> السابق</a>
                @endif
                <span class="designer-pagination-status">صفحة {{ $notifications->currentPage() }} من {{ $notifications->lastPage() }}</span>
                @if ($notifications->hasMorePages())
                    <a class="designer-outline-btn" href="{{ $notifications->nextPageUrl() }}">التالي <i class="bi bi-chevron-left"></i></a>
                @else
                    <span class="designer-outline-btn is-disabled" aria-disabled="true">التالي <i class="bi bi-chevron-left"></i></span>
                @endif
            </nav>
        @endif
    </main>
@endsection
