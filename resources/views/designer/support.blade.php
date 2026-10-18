@extends('designer.layouts.app')

@section('title', 'مركز المساعدة')
@section('body-class', 'settings-page support-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/shared/supportChat.css') }}?v={{ filemtime(public_path('front/shared/supportChat.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/designer/css/settings.css') }}?v={{ filemtime(public_path('front/designer/css/settings.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/designer/css/support.css') }}?v={{ filemtime(public_path('front/designer/css/support.css')) }}">
@endpush

@php
    $faqs = [
        ['كيف أغيّر كلمة المرور؟', 'من الإعدادات، افتح قسم الأمان وأدخل كلمة المرور الحالية والجديدة.'],
        ['كيف أتابع مراجعة تصميمي؟', 'يمكنك متابعة حالة التصميم من قسم تصاميمي في القائمة الجانبية.'],
        ['متى يصل الرد على تذكرتي؟', 'عادةً يرد فريق الدعم خلال يوم عمل واحد.'],
    ];
@endphp

@section('content')
<main class="support-main" id="designerMain">
    @include('designer.partials.flash')

    <div class="designer-breadcrumb">
        <a href="{{ route('designer.dashboard') }}">الرئيسية</a>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span>مركز المساعدة</span>
    </div>

    <header class="designer-page-heading support-heading">
        <span class="designer-heading-icon"><i class="bi bi-headphones" aria-hidden="true"></i></span>
        <div><h1>مركز المساعدة</h1><p>نساعدك في حل المشكلات ومتابعة طلبات الدعم من مكان واحد.</p></div>
        <span class="settings-role-badge"><i class="bi bi-person-badge"></i><span>حساب مصمم</span></span>
    </header>

    <section class="support-channels" aria-label="قنوات الدعم">
        <a class="support-channel" href="#newTicket" data-new-ticket data-no-press>
            <i class="bi bi-plus-circle"></i><span><strong>تذكرة جديدة</strong><small>أرسل مشكلتك وتابع الرد عليها</small></span><b>فتح تذكرة</b>
        </a>
        <a class="support-channel" href="mailto:{{ $contactEmail }}" data-copy="{{ $contactEmail }}" data-copy-label="نسخ البريد" data-no-press>
            <i class="bi bi-envelope"></i><span><strong>البريد الإلكتروني</strong><small>{{ $contactEmail }}</small></span><b>إرسال رسالة</b>
        </a>
        <a class="support-channel" href="tel:{{ str_replace(' ', '', $contactPhone) }}" data-copy="{{ $contactPhone }}" data-copy-label="نسخ الرقم" data-no-press>
            <i class="bi bi-telephone"></i><span><strong>اتصل بنا</strong><small dir="ltr">{{ $contactPhone }}</small></span><b>اتصال</b>
        </a>
    </section>

    <div class="support-grid">
        <section class="support-panel" id="newTicket" @if (! $errors->any()) hidden @endif>
        <button type="button" class="pp-newticket-close" data-close-new-ticket aria-label="إغلاق النموذج"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            <header class="support-panel-head"><span><i class="bi bi-ticket-perforated"></i></span><div><h2>فتح تذكرة دعم</h2><p>أرسل تفاصيل المشكلة وسنتابعها معك.</p></div></header>

            @if ($errors->any())
                <div class="designer-form-errors" role="alert"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i><div><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
            @endif

            <form method="POST" action="{{ route('designer.support.store') }}" enctype="multipart/form-data" class="support-form" id="supportTicketForm">
                @csrf
                <div class="support-form-grid">
                    <label class="profile-field"><span>نوع المشكلة</span><span class="profile-input-shell"><i class="bi bi-list-check"></i>
                        <select name="category" required>
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </span></label>
                    <label class="profile-field"><span>رقم الطلب أو المرجع</span><span class="profile-input-shell"><i class="bi bi-hash"></i><input name="reference" type="text" value="{{ old('reference') }}" placeholder="اختياري" dir="ltr"></span></label>
                    <label class="profile-field is-wide"><span>عنوان المشكلة</span><span class="profile-input-shell"><i class="bi bi-type"></i><input name="subject" type="text" value="{{ old('subject') }}" minlength="5" maxlength="255" required></span></label>
                    <label class="profile-field is-wide"><span>تفاصيل المشكلة</span><span class="profile-input-shell is-textarea"><i class="bi bi-card-text"></i><textarea name="message" rows="4" minlength="15" maxlength="2000" required>{{ old('message') }}</textarea></span></label>
                </div>
                <footer class="support-form-actions"><button type="reset" class="profile-button is-ghost">مسح</button><button type="submit" class="profile-button is-primary"><i class="bi bi-send"></i>إرسال الطلب</button></footer>
            </form>
        </section>

        <aside class="support-side">
            <section class="support-panel support-tickets">
                <header class="support-panel-head"><span><i class="bi bi-clock-history"></i></span><div><h2>طلباتك الأخيرة</h2><p>تابع حالة تذاكر الدعم.</p></div></header>
                @forelse ($tickets as $ticket)
                    @php $ticketStatus = \App\Support\SupportTickets::statusKey($ticket); @endphp
                    <a href="{{ route('designer.support.show', $ticket) }}" class="support-ticket" data-no-press>
                        <div><strong>#{{ $ticket->ticket_number }}</strong><small>{{ $ticket->subject }}</small></div>
                        <span @class(['is-open' => $ticketStatus !== 'resolved'])>{{ ['new' => 'قيد المتابعة', 'open' => 'قيد المعالجة', 'resolved' => 'تم الحل'][$ticketStatus] }}</span>
                    </a>
                @empty
                    <p class="support-empty">ما في طلبات دعم أرسلتها بعد.</p>
                @endforelse
            </section>

            <section class="support-panel support-faq">
                <header class="support-panel-head"><span><i class="bi bi-question-circle"></i></span><div><h2>الأسئلة الشائعة</h2><p>إجابات سريعة قبل فتح تذكرة.</p></div></header>
                <div>
                    @foreach ($faqs as [$question, $answer])
                        <details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('front/shared/supportContact.js') }}?v={{ filemtime(public_path('front/shared/supportContact.js')) }}"></script>
    <script src="{{ asset('front/shared/supportNewTicket.js') }}?v={{ filemtime(public_path('front/shared/supportNewTicket.js')) }}"></script>
@endpush
