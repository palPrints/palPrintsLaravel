@extends('admin.layouts.app')

@section('title', 'إعدادات النظام')
@section('body-class', 'admin-tools-page admin-settings-user-style')
@section('main-id', 'adminSettingsMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/printingEarnings.css').'?v='.filemtime(public_path('front/css/admin/printingEarnings.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminDashboard.css').'?v='.filemtime(public_path('front/css/admin/adminDashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminTools.css').'?v='.filemtime(public_path('front/css/admin/adminTools.css')) }}">
@endpush

@php
    $general = $settings['general'];
    $notifications = $settings['notifications'];
    $fees = $settings['fees'];
    $payments = $settings['payments'];
@endphp

@section('content')
<main class="admin-main admin-tools-main" id="adminSettingsMain">
    <nav class="admin-settings-breadcrumb" aria-label="مسار التنقل"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a><i class="bi bi-chevron-left"></i><span>الإعدادات</span></nav>
    <header class="admin-tools-heading admin-settings-heading"><span class="admin-settings-heading-icon"><i class="bi bi-gear"></i></span><div><h1>إعدادات النظام</h1><p>إدارة إعدادات المنصة ووسائل الدفع والإشعارات والرسوم.</p></div><span class="admin-settings-role-badge"><i class="bi bi-shield-check"></i><span>حساب مدير النظام</span></span></header>

    <div class="admin-settings-layout">
        <nav class="admin-settings-tabs" aria-label="أقسام الإعدادات">
            <button class="active" type="button" data-settings-tab="general" aria-selected="true"><i class="bi bi-sliders"></i><span>الإعدادات العامة</span></button>
            <button type="button" data-settings-tab="payments"><i class="bi bi-credit-card"></i><span>وسائل الدفع</span></button>
            <button type="button" data-settings-tab="notifications"><i class="bi bi-bell"></i><span>الإشعارات</span></button>
            <button type="button" data-settings-tab="fees"><i class="bi bi-percent"></i><span>الرسوم والعمولات</span></button>
            <button type="button" data-settings-tab="audit"><i class="bi bi-clock-history"></i><span>سجل النشاط</span></button>
        </nav>

        <div class="admin-settings-content">
            <section class="admin-tool-card admin-settings-panel active" data-settings-panel="general">
                <header class="admin-tool-card__header"><div><h2>الإعدادات العامة</h2><p>البيانات الأساسية وسياسات تشغيل المنصة.</p></div><span class="admin-section-icon"><i class="bi bi-sliders"></i></span></header>
                <form class="admin-settings-form" data-admin-form="general" data-action="{{ route('admin.settings.general') }}">
                    <div class="admin-field-grid">
                        <label><span>اسم المنصة</span><input type="text" name="platform_name" value="{{ $general['platform_name'] }}" required></label>
                        <label><span>البريد الإداري</span><input type="email" name="admin_email" value="{{ $general['admin_email'] }}" dir="ltr" required></label>
                        <label><span>رقم التواصل</span><input type="tel" name="contact_phone" value="{{ $general['contact_phone'] }}" dir="ltr"></label>
                        <label><span>العملة الافتراضية</span>
                            <select name="currency">
                                <option value="ILS" @selected($general['currency'] === 'ILS')>شيكل إسرائيلي (₪)</option>
                                <option value="USD" @selected($general['currency'] === 'USD')>دولار أمريكي ($)</option>
                                <option value="JOD" @selected($general['currency'] === 'JOD')>دينار أردني (د.أ)</option>
                            </select>
                        </label>
                    </div>
                    <label class="admin-field-wide"><span>وصف المنصة</span><textarea name="description" rows="3">{{ $general['description'] }}</textarea></label>
                    <div class="admin-toggle-row"><div><strong>السماح بالتسجيل الجديد</strong><small>السماح للعملاء والمصممين والمطابع بإنشاء حسابات.</small></div><label class="admin-switch"><input type="checkbox" name="allow_registration" value="1" @checked($general['allow_registration'])><span></span></label></div>
                    <div class="admin-toggle-row"><div><strong>وضع الصيانة</strong><small>تعطيل الواجهة العامة مؤقتًا مع إبقاء لوحة الإدارة متاحة.</small></div><label class="admin-switch"><input type="checkbox" name="maintenance_mode" value="1" @checked($general['maintenance_mode'])><span></span></label></div>
                    <footer class="admin-form-actions"><button class="admin-secondary-button" type="reset"><i class="bi bi-arrow-counterclockwise"></i>استعادة الافتراضي</button><button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i>حفظ التغييرات</button></footer>
                </form>
            </section>

            <section class="admin-settings-panel" data-settings-panel="payments" hidden>
                <div class="admin-panel-heading"><div><h2>وسائل الدفع</h2><p>تفعيل بوابات الدفع المستخدمة في الطلبات وعمليات السحب.</p></div><button class="admin-primary-button" type="button" data-toast="ميزة إضافة وسيلة دفع جديدة قيد التطوير."><i class="bi bi-plus-lg"></i>إضافة وسيلة</button></div>
                <div class="admin-payment-list">
                    @foreach ($paymentMethods as $key => [$label, $icon, $class, $description])
                        <article class="admin-payment-card" data-payment-method="{{ $key }}" data-action="{{ route('admin.settings.payments', $key) }}">
                            <span class="payment-logo {{ $class }}"><i class="bi {{ $icon }}"></i></span>
                            <div><h3>{{ $label }}</h3><p>{{ $description }} · {{ $payments[$key] ?? false ? 'مفعّلة' : 'غير مفعّلة' }}</p></div>
                            <span class="admin-status {{ ($payments[$key] ?? false) ? 'is-success' : 'is-warning' }}">{{ ($payments[$key] ?? false) ? 'نشطة' : 'متوقفة' }}</span>
                            <label class="admin-switch"><input type="checkbox" @checked($payments[$key] ?? false)><span></span></label>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="admin-tool-card admin-settings-panel" data-settings-panel="notifications" hidden>
                <header class="admin-tool-card__header"><div><h2>إدارة الإشعارات</h2><p>حدد الأحداث التي ترسل إشعارات تلقائية للمستخدمين.</p></div><span class="admin-section-icon"><i class="bi bi-bell"></i></span></header>
                <form data-admin-form="notifications" data-action="{{ route('admin.settings.notifications') }}">
                    <div class="admin-toggle-row"><div><strong>تحديثات الطلبات</strong><small>إرسال إشعار عند انتقال الطلب بين مراحل التنفيذ والشحن.</small></div><label class="admin-switch"><input type="checkbox" name="order_updates" value="1" @checked($notifications['order_updates'])><span></span></label></div>
                    <div class="admin-toggle-row"><div><strong>نتائج الاعتماد</strong><small>إبلاغ المصمم أو المطبعة بقبول أو رفض طلب الاعتماد.</small></div><label class="admin-switch"><input type="checkbox" name="approval_results" value="1" @checked($notifications['approval_results'])><span></span></label></div>
                    <div class="admin-toggle-row"><div><strong>المدفوعات والسحوبات</strong><small>إرسال إشعارات بحالة عمليات الدفع وطلبات السحب.</small></div><label class="admin-switch"><input type="checkbox" name="payments_withdrawals" value="1" @checked($notifications['payments_withdrawals'])><span></span></label></div>
                    <div class="admin-toggle-row"><div><strong>النشرات التسويقية</strong><small>إرسال العروض والأخبار إلى المستخدمين المشتركين.</small></div><label class="admin-switch"><input type="checkbox" name="marketing" value="1" @checked($notifications['marketing'])><span></span></label></div>
                    <footer class="admin-form-actions"><button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i>حفظ الإشعارات</button></footer>
                </form>
            </section>

            <section class="admin-tool-card admin-settings-panel" data-settings-panel="fees" hidden>
                <header class="admin-tool-card__header"><div><h2>الرسوم والعمولات</h2><p>تعديل النسب المستخدمة في احتساب أرباح المنصة.</p></div><span class="admin-section-icon"><i class="bi bi-percent"></i></span></header>
                <form class="admin-settings-form" data-admin-form="fees" data-action="{{ route('admin.settings.fees') }}">
                    <div class="admin-field-grid">
                        <label><span>عمولة المنصة على المبيعات</span><span class="admin-number-field"><input type="number" name="platform_commission" min="0" max="100" step="0.1" value="{{ $fees['platform_commission'] }}"><b>%</b></span></label>
                        <label><span>رسوم معالجة الدفع</span><span class="admin-number-field"><input type="number" name="payment_processing_fee" min="0" max="100" step="0.1" value="{{ $fees['payment_processing_fee'] }}"><b>%</b></span></label>
                        <label><span>الحد الأدنى للسحب</span><span class="admin-number-field"><input type="number" name="minimum_withdrawal" min="0" value="{{ $fees['minimum_withdrawal'] }}"><b>₪</b></span></label>
                        <label><span>الضريبة</span><span class="admin-number-field"><input type="number" name="tax_rate" min="0" max="100" step="0.1" value="{{ $fees['tax_rate'] }}"><b>%</b></span></label>
                    </div>
                    <div class="admin-info-note"><i class="bi bi-info-circle"></i><span>الحد الأدنى للسحب يُطبَّق فورًا على طلبات سحب المصممين والمطابع.</span></div>
                    <footer class="admin-form-actions"><button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i>تحديث الرسوم</button></footer>
                </form>
            </section>

            <section class="admin-settings-panel" data-settings-panel="audit" hidden>
                <div class="admin-panel-heading"><div><h2>سجل النشاط الإداري</h2><p>جميع التغييرات الحساسة المسجلة وفق متطلبات الـSRS.</p></div><button class="admin-secondary-button" type="button" id="exportAuditLogButton"><i class="bi bi-download"></i>تصدير السجل</button></div>
                <div class="admin-audit-list" id="adminAuditList">
                    @forelse ($auditLogs as $log)
                        <article data-audit-row data-title="{{ $log['title'] }}" data-description="{{ $log['description'] }}" data-date="{{ $log['date'] }}"><span class="audit-icon {{ $log['class'] }}"><i class="bi {{ $log['icon'] }}"></i></span><div><h3>{{ $log['title'] }}</h3><p>{{ $log['description'] }}</p></div><time>{{ $log['date'] }}</time></article>
                    @empty
                        <p class="admin-audit-empty">لا يوجد نشاط إداري مسجّل بعد.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminDashboard.js').'?v='.filemtime(public_path('front/js/admin/adminDashboard.js')) }}"></script>
    <script src="{{ asset('front/js/admin/adminTools.js').'?v='.filemtime(public_path('front/js/admin/adminTools.js')) }}"></script>
@endpush
