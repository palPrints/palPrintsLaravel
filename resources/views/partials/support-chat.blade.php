{{--
    The chat of one support ticket, shared by the admin, customer, designer and print shop pages (supportChat.js).
    Needs: $thread (SupportTickets::thread), $messagesUrl, $replyUrl, $backUrl. Admin pages also pass $statusUrl and $person.
--}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('front/shared/supportChat.css') }}?v={{ filemtime(public_path('front/shared/supportChat.css')) }}">
@endpush

@php $isAdmin = isset($statusUrl); @endphp

<section class="pp-chat" id="ppChat"
    data-messages-url="{{ $messagesUrl }}"
    data-reply-url="{{ $replyUrl }}"
    @if ($isAdmin) data-status-url="{{ $statusUrl }}" data-admin="1" @endif>
    <header class="pp-chat__head">
        <a class="pp-chat__back" href="{{ $backUrl }}"><i class="bi bi-arrow-right" aria-hidden="true"></i><span>العودة</span></a>
        <div class="pp-chat__title">
            <h1>{{ $thread['subject'] }}</h1>
            <small>#{{ $thread['number'] }} · {{ $thread['category'] }}</small>
            @if ($isAdmin && isset($person))
                <small class="pp-chat__peer"><i class="bi bi-person-circle" aria-hidden="true"></i><strong>{{ $person['name'] }}</strong> · {{ $person['role_label'] }} · <a href="mailto:{{ $person['email'] }}" dir="ltr">{{ $person['email'] }}</a></small>
            @endif
        </div>
        <span class="pp-chat__status" id="ppChatStatus"></span>
        @unless ($isAdmin)
            <a class="pp-chat__new" href="{{ $backUrl }}#newTicket"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>تذكرة جديدة</span></a>
        @endunless
        @if ($isAdmin)
            <button type="button" class="pp-chat__resolve" id="ppChatResolve"></button>
        @endif
    </header>

    <div class="pp-chat__messages" id="ppChatMessages" aria-live="polite"></div>

    <form class="pp-chat__composer" id="ppChatForm">
        <textarea id="ppChatText" rows="1" maxlength="2000" placeholder="{{ $isAdmin ? 'اكتب ردك على المستخدم...' : 'اكتب رسالتك لفريق الدعم...' }}" aria-label="الرسالة" required></textarea>
        <button type="submit" aria-label="إرسال"><i class="bi bi-send" aria-hidden="true"></i><span>إرسال</span></button>
    </form>
    <p class="pp-chat__closed" id="ppChatClosed" hidden><i class="bi bi-check-circle" aria-hidden="true"></i>
        @if ($isAdmin)
            هذه التذكرة محلولة. اضغط «إعادة فتح» أعلى الصفحة إذا بدك تكمل المحادثة.
        @else
            تم حل هذا الطلب. إذا عندك مشكلة جديدة <a href="{{ $backUrl }}#newTicket">افتح تذكرة دعم جديدة</a>.
        @endif
    </p>
</section>

<script type="application/json" id="ppChatData">{!! json_encode($thread, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

@push('scripts')
    <script src="{{ asset('front/shared/supportChat.js') }}?v={{ filemtime(public_path('front/shared/supportChat.js')) }}"></script>
@endpush
