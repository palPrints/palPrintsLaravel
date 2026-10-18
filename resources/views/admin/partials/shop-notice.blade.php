{{-- How the shop an order was just routed to hears about it (ShopNotifier): Telegram goes out on its own, WhatsApp is a ready wa.me message for the admin. --}}
@if ($chat = session('notice_whatsapp'))
    @if ($chat['telegram'] ?? false)
        <p class="notices-flash is-telegram" role="status"><i class="bi bi-telegram" aria-hidden="true"></i> يُرسل الطلب تلقائيًا إلى {{ $chat['shop'] }} على تيليجرام مع ملفات الطباعة وأزرار القبول والرفض.</p>
    @endif
    @if ($chat['url'])
        <div class="notices-flash is-whatsapp">
            <span><i class="bi bi-whatsapp" aria-hidden="true"></i> أبلغ {{ $chat['shop'] }} بالطلب على واتساب، الرسالة جاهزة وتحتاج فقط إلى إرسال.</span>
            <a class="notice-whatsapp-btn" href="{{ $chat['url'] }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp" aria-hidden="true"></i> إرسال للمطبعة</a>
        </div>
    @elseif (! ($chat['telegram'] ?? false))
        <p class="notices-flash is-whatsapp" role="status"><i class="bi bi-info-circle" aria-hidden="true"></i> لم تفعّل {{ $chat['shop'] }} استقبال الطلبات على واتساب أو تيليجرام، لذلك سيصلها الطلب في صفحة الطلبات فقط.</p>
    @endif
@endif
