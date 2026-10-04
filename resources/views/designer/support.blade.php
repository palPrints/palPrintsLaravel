@extends('designer.layouts.app')

@section('title', 'مركز المساعدة')
@section('body-class', 'settings-page support-page')

@push('styles')
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
        <button type="button" class="support-channel" id="startChatButton" data-no-press>
            <i class="bi bi-chat-dots"></i><span><strong>المحادثة المباشرة</strong><small>متاحون من 9 صباحًا حتى 5 مساءً</small></span><b>بدء المحادثة</b>
        </button>
        <a class="support-channel" href="mailto:{{ $contactEmail }}" data-no-press>
            <i class="bi bi-envelope"></i><span><strong>البريد الإلكتروني</strong><small>{{ $contactEmail }}</small></span><b>إرسال رسالة</b>
        </a>
        <a class="support-channel" href="tel:{{ str_replace(' ', '', $contactPhone) }}" data-no-press>
            <i class="bi bi-telephone"></i><span><strong>اتصل بنا</strong><small dir="ltr">{{ $contactPhone }}</small></span><b>اتصال</b>
        </a>
    </section>

    <div class="support-grid">
        <section class="support-panel">
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
                    <label class="support-upload">
                        <i class="bi bi-paperclip"></i>
                        <span><strong>إرفاق ملف</strong><small>PNG أو JPG أو PDF — حتى 5MB</small></span>
                        <input type="file" name="attachment" accept=".png,.jpg,.jpeg,.pdf" id="supportAttachmentInput">
                        <b id="attachmentName">اختيار ملف</b>
                    </label>
                </div>
                <footer class="support-form-actions"><button type="reset" class="profile-button is-ghost">مسح</button><button type="submit" class="profile-button is-primary"><i class="bi bi-send"></i>إرسال الطلب</button></footer>
            </form>
        </section>

        <aside class="support-side">
            <section class="support-panel support-tickets">
                <header class="support-panel-head"><span><i class="bi bi-clock-history"></i></span><div><h2>طلباتك الأخيرة</h2><p>تابع حالة تذاكر الدعم.</p></div></header>
                @forelse ($tickets as $ticket)
                    <div class="support-ticket">
                        <div>
                            <strong>#{{ $ticket['id'] }}</strong>
                            <small>{{ $ticket['subject'] }}</small>
                        </div>
                        @if ($ticket['status'] === 'resolved')
                            <span>تم الحل</span>
                        @else
                            <span class="is-open">{{ $ticket['status'] === 'open' ? 'قيد المعالجة' : 'قيد المتابعة' }}</span>
                        @endif
                    </div>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var fileInput = document.getElementById('supportAttachmentInput');
        var fileLabel = document.getElementById('attachmentName');
        if (fileInput && fileLabel) {
            fileInput.addEventListener('change', function () {
                fileLabel.textContent = fileInput.files[0] ? fileInput.files[0].name : 'اختيار ملف';
            });
        }

        var chatButton = document.getElementById('startChatButton');
        if (chatButton) {
            chatButton.addEventListener('click', function () {
                if (window.PalProfile && window.PalProfile.toast) {
                    window.PalProfile.toast('سيتم ربطك بأحد موظفي الدعم الآن.', 'info');
                }
            });
        }
    });
</script>
@endpush
