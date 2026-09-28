{{--
    Ported from palPrintFront/support.html. Same situation as settings.blade.php:
    the reference used a separate "profile-app" shell and its own design system
    (profile-core.css, support.css), unrelated to the --pp-* tokens the rest of
    this site is built on. Rebuilt with the existing app-card/field-grid/btn-brand
    components, fitted into the shared storefront layout, and trimmed to the
    "customer" role branch of the original (it served designer/printer/customer
    from one file via a role query param).

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
        <div class="page-heading">
            <h1 id="pageTitle">مركز الدعم الفني</h1>
            <p>نساعدك في حل المشكلات ومتابعة طلبات الدعم من مكان واحد.</p>
        </div>

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
                    <div class="field-grid">
                        <div class="field">
                            <label class="pp-label" for="ticketCategory">نوع المشكلة</label>
                            <select id="ticketCategory" class="pp-input" name="category" required>
                                <option value="">اختر نوع المشكلة</option>
                                <option>طلب أو توصيل</option>
                                <option>الدفع والاسترداد</option>
                                <option>الحساب والعنوان</option>
                                <option>مشكلة تقنية</option>
                            </select>
                        </div>
                        <div class="field">
                            <label class="pp-label" for="ticketReference">رقم الطلب أو المرجع</label>
                            <input id="ticketReference" class="pp-input" name="reference" type="text" placeholder="اختياري" dir="ltr">
                        </div>
                        <div class="field" style="grid-column: 1 / -1;">
                            <label class="pp-label" for="ticketSubject">عنوان المشكلة</label>
                            <input id="ticketSubject" class="pp-input" name="subject" type="text" minlength="5" required>
                        </div>
                        <div class="field" style="grid-column: 1 / -1;">
                            <label class="pp-label" for="ticketMessage">تفاصيل المشكلة</label>
                            <textarea id="ticketMessage" class="pp-textarea" name="message" rows="4" minlength="15" required></textarea>
                        </div>
                        <label class="support-upload">
                            <i class="bi bi-paperclip"></i>
                            <span><strong>إرفاق ملف</strong><small>PNG أو JPG أو PDF — حتى 5MB</small></span>
                            <input type="file" accept=".png,.jpg,.jpeg,.pdf">
                            <b id="attachmentName">اختيار ملف</b>
                        </label>
                    </div>
                    <div class="form-actions" style="justify-content: flex-start;">
                        <button type="submit" class="btn-brand"><i class="bi bi-send"></i> إرسال الطلب</button>
                        <button type="reset" class="btn-brand-outline">مسح</button>
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
