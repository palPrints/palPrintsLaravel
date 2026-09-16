@extends('printProvider.layouts.app')

@section('title', 'الملف الشخصي للمطبعة')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/profile.css') }}?v={{ filemtime(public_path('front/css/printProvider/profile.css')) }}">
@endpush

@section('content')
    <form id="printerProfileForm">
        <div class="breadcrumb">
            <a href="{{ route('print-provider.dashboard') }}">لوحة التحكم</a>
            <i class="bi bi-chevron-left"></i>
            <span>الملف الشخصي</span>
        </div>

        <header class="page-heading">
            <span class="heading-icon"><i class="bi bi-person"></i></span>
            <div>
                <h1>الملف الشخصي للمطبعة</h1>
                <p>يمكنك إدارة بيانات مطبعتك الخاصة والمعلومات والوصول إلى المزيد من فرص الطلبات.</p>
            </div>
        </header>

        <section class="completion-alert">
            <span><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <h2>أكمل ملفك الشخصي</h2>
                <p>هذا بعض الحقول المهمة لإكمال بيانات ملفك الشخصي. يمكنك الوصول إلى هذه الصفحة من خلال زر "إكمال البيانات" في لوحة التحكم.</p>
            </div>
        </section>

        <div class="two-column profile-section-grid">
            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-building"></i></span><h2>بيانات المطبعة</h2></header>
                <div class="printer-data-layout">
                    <div class="fields">
                        <label class="full">اسم المطبعة <em>*</em><input required value="مطبعة الألوان الحديثة"></label>
                        <label>سنة التأسيس<input type="number" value="2020"></label>
                        <label>رقم الهاتف <em>*</em><span class="input-icon"><i class="bi bi-telephone"></i><input required value="970 56 123 4567" dir="ltr"></span></label>
                        <label class="full">البريد الإلكتروني <em>*</em><span class="input-icon"><i class="bi bi-envelope"></i><input required type="email" value="info@alwanprint.ps" dir="ltr"></span></label>
                        <label class="full">وصف مختصر عن المطبعة<textarea rows="3">مطبعة متخصصة في جميع خدمات الطباعة الرقمية والتجارية.
نقدم جودة عالية وخدمة سريعة تلبي احتياجات عملائنا.</textarea></label>
                        <label class="full">وسائل التواصل الاجتماعي<span class="input-icon whatsapp"><i class="bi bi-whatsapp"></i><input value="970 56 123 4567" dir="ltr"></span></label>
                    </div>
                </div>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-geo-alt"></i></span><h2>العنوان ومعلومات الموقع</h2></header>
                <div class="fields address-fields">
                    <label>المحافظة <em>*</em>
                        <select required><option>غزة</option><option>رام الله</option><option>نابلس</option></select>
                    </label>
                    <label>المدينة / المنطقة <em>*</em>
                        <select required><option>الرمال</option><option>الشجاعية</option></select>
                    </label>
                    <label class="full">المنطقة
                        <select><option>المنطقة الأولى</option><option>المنطقة الثانية</option></select>
                    </label>
                    <label class="full">العنوان التفصيلي <em>*</em>
                        <textarea required rows="4">غزة، الرمال، شارع عمر المختار</textarea>
                    </label>
                </div>
            </section>
        </div>

        <div class="three-column profile-section-grid">
            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-file-earmark-text"></i></span><h2>الوثائق</h2></header>
                <div class="documents-table">
                    <div class="table-head"><b>اسم الوثيقة</b><b>حالة الوثيقة</b><b>الإجراء</b></div>
                    <div class="document-row"><span>السجل التجاري</span><strong class="status uploaded">مرفوع</strong><button class="outline-btn" type="button">استبدال الملف</button></div>
                    <div class="document-row"><span>الهوية / إثبات الشخصية</span><strong class="status review">قيد المراجعة</strong><button class="outline-btn" type="button">عرض ملف</button></div>
                    <div class="document-row"><span>رخصة مزاولة المهنة</span><strong class="status uploaded">مرفوع</strong><button class="outline-btn" type="button">استبدال الملف</button></div>
                    <div class="document-row"><span>شهادة التسجيل الضريبي</span><strong class="status missing">غير مرفوع</strong><button class="outline-btn" type="button">رفع ملف</button></div>
                </div>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-person"></i></span><h2>بيانات المسؤول</h2></header>
                <div class="fields single">
                    <label>اسم المسؤول <em>*</em><input required value="أحمد محمد علي"></label>
                    <label>رقم الهاتف <em>*</em><span class="input-icon"><i class="bi bi-telephone"></i><input required value="970 59 987 6543" dir="ltr"></span></label>
                    <label>البريد الإلكتروني <em>*</em><span class="input-icon"><i class="bi bi-envelope"></i><input required type="email" value="ahmed@alwanprint.ps" dir="ltr"></span></label>
                    <label>الصفة في المطبعة <em>*</em>
                        <select required><option>المدير العام</option><option>المالك</option><option>موظف إداري</option></select>
                    </label>
                </div>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-clock"></i></span><h2>معلومات العمل</h2></header>
                <div class="availability">
                    <div>
                        <b>متاح حالياً لاستقبال الطلبات</b>
                        <span id="availabilityText">متاح <i></i></span>
                    </div>
                    <label class="switch">
                        <input id="availabilityToggle" type="checkbox" checked>
                        <span></span>
                    </label>
                </div>

                <h3>أيام العمل</h3>
                <div class="work-days" id="workDays">
                    <button class="selected" type="button">الأحد</button>
                    <button class="selected" type="button">الإثنين</button>
                    <button class="selected" type="button">الثلاثاء</button>
                    <button class="selected" type="button">الأربعاء</button>
                    <button class="selected" type="button">الخميس</button>
                    <button type="button">الجمعة</button>
                    <button type="button">السبت</button>
                </div>

                <h3>ساعات العمل</h3>
                <div class="hours">
                    <label>من الساعة<span class="input-icon"><i class="bi bi-clock"></i><input value="٨:٠٠ ص"></span></label>
                    <label>إلى الساعة<span class="input-icon"><i class="bi bi-clock"></i><input value="٨:٠٠ م"></span></label>
                </div>
            </section>
        </div>

        <section class="form-card review-card">
            <header class="card-title"><span><i class="bi bi-shield"></i></span><h2>حالة مراجعة الملف</h2></header>
            <div class="review-content">
                <div class="review-notice">
                    <i class="bi bi-hourglass-split"></i>
                    <div>
                        <h3>جاري تجهيز بياناتك للمراجعة</h3>
                        <p>سيتم إشعارك عبر البريد الإلكتروني حالما يتم مراجعة ملفك من قبل فريق الإدارة.</p>
                    </div>
                </div>
                <div class="admin-notes">
                    <h3><i class="bi bi-check2-square"></i> ملاحظات الإدارة</h3>
                    <p>لا يوجد تحديثات حالياً. يرجى متابعة حالة الملف لاحقاً.</p>
                    <b><i class="bi bi-clock"></i> آخر تحديث: 12 سبتمبر 2026 — 10:30 صباحاً</b>
                </div>
            </div>
        </section>

        <footer class="form-actions">
            <button class="secondary-btn" type="reset">إلغاء</button>
            <div>
                <button class="outline-primary" id="submitReview" type="button"><i class="bi bi-send"></i> إرسال للمراجعة</button>
                <button class="primary-btn" type="submit"><i class="bi bi-floppy"></i> حفظ التغييرات</button>
            </div>
        </footer>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/printProvider/profile.js') }}"></script>
@endpush
