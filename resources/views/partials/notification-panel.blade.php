{{--
    The inside of the bell menu, the same for every account type (customer, designer, print shop, admin).
    $items: the unread notifications to list, $readRoute: route that marks one read and goes to its link,
    $allUrl: the full notifications page, $unread: how many unread there are, $listId: optional id of the list.
--}}
<div class="pp-notif__head">
    <strong>الإشعارات</strong>
    @if (($unread ?? 0) > 0)<span>{{ $unread > 99 ? '99+' : $unread }} جديد</span>@endif
</div>
<div class="pp-notif__list" @isset($listId) id="{{ $listId }}" @endisset>
    @forelse ($items as $notification)
        {{-- Opening a notification marks it read (it leaves this list) and goes to its link. --}}
        <form method="POST" action="{{ route($readRoute, $notification) }}">
            @csrf
            <button type="submit" class="pp-notif__item is-unread" role="menuitem" data-no-press>
                <span class="pp-notif__icon"><i class="bi {{ \App\Support\NotificationStyle::icon($notification->type) }}" aria-hidden="true"></i></span>
                <span class="pp-notif__body">
                    <strong>{{ $notification->title }}</strong>
                    <small>{{ $notification->message }}</small>
                    <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->locale('ar')->diffForHumans() }}</time>
                </span>
            </button>
        </form>
    @empty
        <p class="pp-notif__empty"><i class="bi bi-bell-slash" aria-hidden="true"></i>لا توجد إشعارات جديدة</p>
    @endforelse
</div>
<a class="pp-notif__all" href="{{ $allUrl }}" role="menuitem">عرض جميع الإشعارات</a>
