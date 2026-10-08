@extends('admin.layouts.app')

@section('title', 'الإشعارات')
@section('body-class', 'admin-tools-page')
@section('main-id', 'adminNotificationsMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminDashboard.css').'?v='.filemtime(public_path('front/css/admin/adminDashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminTools.css').'?v='.filemtime(public_path('front/css/admin/adminTools.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminNotifications.css').'?v='.filemtime(public_path('front/css/admin/adminNotifications.css')) }}">
@endpush

@section('content')
<main class="admin-main admin-tools-main admin-notifications" id="adminNotificationsMain">
    @include('admin.partials.breadcrumb', ['label' => 'الإشعارات'])

    <header class="an-heading">
        <div>
            <h1>الإشعارات</h1>
            <p>{{ $unreadCount > 0 ? 'لديك '.$unreadCount.' إشعار غير مقروء.' : 'لا توجد إشعارات غير مقروءة.' }}</p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button type="submit" class="an-read-all"><i class="bi bi-check2-all" aria-hidden="true"></i> تعليم الكل كمقروء</button>
            </form>
        @endif
    </header>

    <section class="an-list" aria-label="قائمة الإشعارات">
        @forelse ($notifications as $notification)
            @php($item = $categories[\App\Support\AdminNotifier::categoryOf($notification->type)] ?? null)
            <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                @csrf
                <button type="submit" @class(['an-item', 'is-unread' => ! $notification->is_read])>
                    <span class="an-icon"><i class="bi {{ $item['icon'] ?? 'bi-bell' }}" aria-hidden="true"></i></span>
                    <span class="an-copy">
                        <strong>{{ $notification->title }}</strong>
                        <small>{{ $notification->message }}</small>
                    </span>
                    <span class="an-meta">
                        <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->locale('ar')->diffForHumans() }}</time>
                        @unless ($notification->is_read)<i class="an-dot" aria-label="غير مقروء"></i>@endunless
                    </span>
                </button>
            </form>
        @empty
            <div class="an-empty">
                <i class="bi bi-bell-slash" aria-hidden="true"></i>
                <p>لا توجد إشعارات حتى الآن.</p>
            </div>
        @endforelse
    </section>

    @if ($notifications->hasPages())
        <div class="an-pagination">{{ $notifications->links() }}</div>
    @endif
</main>
@endsection
