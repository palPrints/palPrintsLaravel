@extends('printProvider.layouts.app')

@section('title', 'مركز المساعدة')

@section('bodyClass', 'print-account-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/shared/supportChat.css') }}?v={{ filemtime(public_path('front/shared/supportChat.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/account.css') }}?v={{ filemtime(public_path('front/css/printProvider/account.css')) }}">
@endpush

@php
    $faqs = [
        ['كيف أغيّر كلمة المرور؟', 'من الإعدادات، افتح قسم الأمان وأدخل كلمة المرور الحالية والجديدة.'],
        ['كيف أضيف منتجًا إلى مطبعتي؟', 'من صفحة خدمات الطباعة اضغط «إضافة منتج من الكتالوج» ثم فعّل المنتج وعدّل إعداداته.'],
        ['متى يصل الرد على تذكرتي؟', 'عادةً يرد فريق الدعم خلال يوم عمل واحد.'],
    ];
@endphp

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('print-provider.dashboard') }}">الرئيسية</a>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span>مركز المساعدة</span>
    </div>

    <header class="page-heading support-heading">
        <span class="heading-icon"><i class="bi bi-headphones" aria-hidden="true"></i></span>
        <div><h1>مركز المساعدة</h1><p>نساعدك في حل المشكلات ومتابعة طلبات الدعم من مكان واحد.</p></div>
        <span class="acct-role-badge"><i class="bi bi-printer" aria-hidden="true"></i><span>حساب مطبعة</span></span>
    </header>

    @if (session('status'))
        <div class="acct-flash" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i>{{ session('status') }}</div>
    @endif

    <section class="acct-channels" aria-label="قنوات الدعم">
        <a class="acct-channel" href="#newTicket" data-new-ticket>
            <i class="bi bi-plus-circle" aria-hidden="true"></i><span><strong>تذكرة جديدة</strong><small>أرسل مشكلتك وتابع الرد عليها</small></span><b><i class="bi bi-chat-dots" aria-hidden="true"></i> فتح تذكرة</b>
        </a>
        <a class="acct-channel" href="mailto:{{ $contactEmail }}" data-copy="{{ $contactEmail }}" data-copy-label="نسخ البريد">
            <i class="bi bi-envelope" aria-hidden="true"></i><span><strong>البريد الإلكتروني</strong><small>{{ $contactEmail }}</small></span><b>إرسال رسالة</b>
        </a>
        <a class="acct-channel" href="tel:{{ str_replace(' ', '', (string) $contactPhone) }}" data-copy="{{ $contactPhone }}" data-copy-label="نسخ الرقم">
            <i class="bi bi-telephone" aria-hidden="true"></i><span><strong>اتصل بنا</strong><small dir="ltr">{{ $contactPhone }}</small></span><b>اتصال</b>
        </a>
    </section>

    <div class="acct-support-grid">
        <section class="acct-panel active" id="newTicket" @if (! $errors->any()) hidden @endif>
        <button type="button" class="pp-newticket-close" data-close-new-ticket aria-label="إغلاق النموذج"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            <header class="acct-section-head"><span><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span><div><h2>فتح تذكرة دعم</h2><p>أرسل تفاصيل المشكلة وسنتابعها معك.</p></div></header>

            @if ($errors->any())
                <div class="acct-errors" role="alert"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
            @endif

            <form class="acct-form" method="POST" action="{{ route('print-provider.support.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="acct-grid">
                    <label class="acct-field"><span>نوع المشكلة</span><span class="acct-input"><i class="bi bi-list-check" aria-hidden="true"></i>
                        <select name="category" required>
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </span></label>
                    <label class="acct-field"><span>رقم الطلب أو المرجع</span><span class="acct-input"><i class="bi bi-hash" aria-hidden="true"></i><input name="reference" type="text" value="{{ old('reference') }}" placeholder="اختياري" dir="ltr"></span></label>
                    <label class="acct-field is-wide"><span>عنوان المشكلة</span><span class="acct-input"><i class="bi bi-type" aria-hidden="true"></i><input name="subject" type="text" value="{{ old('subject') }}" minlength="5" maxlength="255" required></span></label>
                    <label class="acct-field is-wide"><span>تفاصيل المشكلة</span><span class="acct-input is-textarea"><i class="bi bi-card-text" aria-hidden="true"></i><textarea name="message" rows="4" minlength="15" maxlength="2000" required>{{ old('message') }}</textarea></span></label>
                </div>
                <footer class="acct-actions"><button type="reset" class="acct-button is-ghost">مسح</button><button type="submit" class="acct-button is-primary"><i class="bi bi-send" aria-hidden="true"></i>إرسال الطلب</button></footer>
            </form>
        </section>

        <aside class="acct-side">
            <section class="acct-panel active">
                <header class="acct-section-head"><span><i class="bi bi-clock-history" aria-hidden="true"></i></span><div><h2>طلباتك الأخيرة</h2><p>تابع حالة تذاكر الدعم.</p></div></header>
                @forelse ($tickets as $ticket)
                    @php $ticketStatus = \App\Support\SupportTickets::statusKey($ticket); @endphp
                    <a href="{{ route('print-provider.support.show', $ticket) }}" class="acct-ticket" data-no-press>
                        <div><strong>#{{ $ticket->ticket_number }}</strong><small>{{ $ticket->subject }}</small></div>
                        <span @class(['is-open' => $ticketStatus !== 'resolved'])>{{ ['new' => 'قيد المتابعة', 'open' => 'قيد المعالجة', 'resolved' => 'تم الحل'][$ticketStatus] }}</span>
                    </a>
                @empty
                    <p class="acct-empty"><i class="bi bi-inbox" aria-hidden="true"></i><span>لا توجد طلبات دعم أرسلتها بعد.</span></p>
                @endforelse
            </section>

            <section class="acct-panel active">
                <header class="acct-section-head"><span><i class="bi bi-question-circle" aria-hidden="true"></i></span><div><h2>الأسئلة الشائعة</h2><p>إجابات سريعة قبل فتح تذكرة.</p></div></header>
                <div class="acct-faq">
                    @foreach ($faqs as [$question, $answer])
                        <details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front/shared/supportContact.js') }}?v={{ filemtime(public_path('front/shared/supportContact.js')) }}"></script>
    <script src="{{ asset('front/shared/supportNewTicket.js') }}?v={{ filemtime(public_path('front/shared/supportNewTicket.js')) }}"></script>
    <script src="{{ asset('front/js/printProvider/account.js') }}?v={{ filemtime(public_path('front/js/printProvider/account.js')) }}"></script>
@endpush
