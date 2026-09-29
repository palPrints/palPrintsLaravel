{{--
    Ported from palPrintFront/support.html to match its exact support.css
    styling (the channel strip with vertical dividers, icon-in-shell inputs)
    — reproduced with this site's own --pp-* tokens instead of literally
    importing the reference's separate design system (profile-core.css carries
    a standalone "profile-app" shell and a lot of designer/print-provider-only
    CSS this page doesn't need). Trimmed to the "customer" role branch of the
    original (it served designer/printer/customer from one file via a role
    query param).

    There's no support-ticket table yet, so the ticket form and "طلباتك الأخيرة"
    list are demo-only (client-side toast + reset on submit), same treatment as
    the other not-yet-wired demo fields elsewhere in the customer area.
--}}
@extends('customer.layouts.app')

@section('title', 'الدعم الفني')
@section('meta-description', 'مركز الدعم الفني لعملاء PalPrints')
@section('body-class', 'storefront-page support-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/support.css') }}?v={{ filemtime(public_path('front/css/customer/support.css')) }}">
@endpush

@section('content')
    <section class="profile-content" aria-labelledby="pageTitle">
        <div class="pp-breadcrumb">
            <a href="{{ route('customer.store') }}">الرئيسية</a>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span>الدعم الفني</span>
        </div>
        <header class="pp-page-heading">
            <span class="pp-heading-icon"><i class="bi bi-headphones" aria-hidden="true"></i></span>
            <div><h1 id="pageTitle">مركز الدعم الفني</h1><p>نساعدك في حل المشكلات ومتابعة طلبات الدعم من مكان واحد.</p></div>
            <span class="pp-role-badge"><i class="bi bi-person-badge"></i><span>حساب عميل</span></span>
        </header>

        <section class="support-channels" aria-label="قنوات الدعم">
            <button type="button" class="support-channel" id="startChatButton">
                <i class="bi bi-chat-dots"></i>
                <span><strong>المحادثة المباشرة</strong><small>متاحون من 9 صباحًا حتى 5 مساءً</small></span>
                <b>بدء المحادثة</b>
            </button>
            <a class="support-channel" href="mailto:support@palprints.com">
                <i class="bi bi-envelope"></i>
                <span><strong>البريد الإلكتروني</strong><small dir="ltr">support@palprints.com</small></span>
                <b>إرسال رسالة</b>
            </a>
            <a class="support-channel" href="tel:+970599000000">
                <i class="bi bi-telephone"></i>
                <span><strong>اتصل بنا</strong><small dir="ltr">+970 59 900 0000</small></span>
                <b>اتصال</b>
            </a>
        </section>

        <div class="support-grid">
            <section class="app-card" style="padding: var(--sp-4) var(--sp-5) var(--sp-5);">
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-ticket-perforated"></i></span>
                    <div><h2>فتح تذكرة دعم</h2><p>أرسل تفاصيل المشكلة وسنتابعها معك.</p></div>
                </div>
                <form id="supportTicketForm">
                    <div class="pp-field-grid">
                        <label class="pp-field">
                            <span>نوع المشكلة</span>
                            <span class="pp-input-shell"><i class="bi bi-list-check"></i><select id="ticketCategory" name="category" required>
                                <option value="">اختر نوع المشكلة</option>
                                <option>طلب أو توصيل</option>
                                <option>الدفع والاسترداد</option>
                                <option>الحساب والعنوان</option>
                                <option>مشكلة تقنية</option>
                            </select></span>
                        </label>
                        <label class="pp-field">
                            <span>رقم الطلب أو المرجع</span>
                            <span class="pp-input-shell"><i class="bi bi-hash"></i><input id="ticketReference" name="reference" type="text" placeholder="اختياري" dir="ltr"></span>
                        </label>
                        <label class="pp-field is-wide">
                            <span>عنوان المشكلة</span>
                            <span class="pp-input-shell"><i class="bi bi-type"></i><input id="ticketSubject" name="subject" type="text" minlength="5" required></span>
                        </label>
                        <label class="pp-field is-wide">
                            <span>تفاصيل المشكلة</span>
                            <span class="pp-input-shell is-textarea"><i class="bi bi-card-text"></i><textarea id="ticketMessage" name="message" rows="4" minlength="15" required></textarea></span>
                        </label>
                        <label class="support-upload">
                            <i class="bi bi-paperclip"></i>
                            <span><strong>إرفاق ملف</strong><small>PNG أو JPG أو PDF — حتى 5MB</small></span>
                            <input type="file" accept=".png,.jpg,.jpeg,.pdf">
                            <b id="attachmentName">اختيار ملف</b>
                        </label>
                    </div>
                    <div class="support-form-actions">
                        <button type="reset" class="btn-brand-outline">مسح</button>
                        <button type="submit" class="btn-brand"><i class="bi bi-send"></i> إرسال الطلب</button>
                    </div>
                </form>
            </section>

            <aside style="display: flex; flex-direction: column; gap: var(--sp-4);">
                <section class="app-card" style="padding: var(--sp-4) var(--sp-5);">
                    <div class="section-title">
                        <span class="section-icon"><i class="bi bi-clock-history"></i></span>
                        <div><h2>طلباتك الأخيرة</h2><p>تابع حالة تذاكر الدعم.</p></div>
                    </div>
                    <div class="support-ticket"><div><strong>#SUP-1042</strong><small>مشكلة في تحديث البيانات</small></div><span class="is-open">قيد المتابعة</span></div>
                    <div class="support-ticket"><div><strong>#SUP-1018</strong><small>استفسار عن الحساب</small></div><span>تم الحل</span></div>
                </section>

                <section class="app-card support-faq" style="padding: var(--sp-4) var(--sp-5);">
                    <div class="section-title">
                        <span class="section-icon"><i class="bi bi-question-circle"></i></span>
                        <div><h2>الأسئلة الشائعة</h2><p>إجابات سريعة قبل فتح تذكرة.</p></div>
                    </div>
                    <details>
                        <summary>كيف أغيّر كلمة المرور؟</summary>
                        <p>من <a href="{{ route('customer.settings') }}">الإعدادات</a>، افتح قسم الأمان وأدخل كلمة المرور الحالية والجديدة.</p>
                    </details>
                    <details>
                        <summary>كيف أتابع حالة طلبي؟</summary>
                        <p>يمكنك متابعة الحالة من قسم <a href="{{ route('customer.orders') }}">طلباتي</a> في القائمة الجانبية.</p>
                    </details>
                    <details>
                        <summary>متى يصل الرد على تذكرتي؟</summary>
                        <p>عادةً يرد فريق الدعم خلال يوم عمل واحد.</p>
                    </details>
                </section>
            </aside>
        </div>
    </section>

    <div class="toast" id="toast" role="status" aria-live="polite" hidden><i class="bi bi-check-circle-fill"></i><span></span></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/support.js') }}?v={{ filemtime(public_path('front/js/customer/support.js')) }}"></script>
@endpush
