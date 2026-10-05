@extends('designer.layouts.app')

@section('title', 'الإعدادات')
@section('body-class', 'settings-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/designer/css/settings.css') }}?v={{ filemtime(public_path('front/designer/css/settings.css')) }}">
@endpush

@php
    $status = $approvalStatus;
    $awaitingReview = in_array($status, ['submitted', 'under_review'], true);
    $needsChanges = in_array($status, ['rejected', 'changes_requested'], true);
    $statusLabel = match (true) {
        $status === 'approved' => 'معتمد',
        $awaitingReview => 'قيد المراجعة',
        $needsChanges => 'يحتاج تعديل',
        default => 'غير مكتمل',
    };
    $requirements = [
        [
            'icon' => 'bi-envelope-check',
            'title' => 'تفعيل البريد الإلكتروني',
            'text' => $user->email_verified_at ? 'تم تفعيل البريد الإلكتروني بنجاح.' : 'لم يتم تفعيل البريد الإلكتروني بعد.',
            'label' => $user->email_verified_at ? 'مكتمل' : 'يحتاج استكمال',
            'warning' => $user->email_verified_at === null,
        ],
        [
            'icon' => 'bi-briefcase',
            'title' => 'الملف الشخصي والمعرض',
            'text' => $profileComplete ? 'بياناتك المهنية مكتملة.' : 'أكمل التخصص والمهارات ورابط معرض الأعمال.',
            'label' => $profileComplete ? 'مكتمل' : 'يحتاج استكمال',
            'warning' => ! $profileComplete,
        ],
        [
            'icon' => 'bi-person-check',
            'title' => 'اعتماد حساب المصمم',
            'text' => match (true) {
                $status === 'approved' => 'تمت مراجعة بياناتك واعتماد الحساب.',
                $awaitingReview => 'طلبك قيد المراجعة من الإدارة.',
                $needsChanges => 'الإدارة طلبت تعديل بعض البيانات.',
                default => 'أرسل طلب الاعتماد بعد إكمال ملفك الشخصي.',
            },
            'label' => $statusLabel,
            'warning' => $status !== 'approved',
        ],
    ];
    $settingsFlash = session('settings_status');
    $passwordFlash = session('status') === 'password-updated';
    $hasAccountErrors = $errors->updateAccount->any();
    $hasPasswordErrors = $errors->updatePassword->any();
    $hasDeleteErrors = $errors->userDeletion->any();
    $initialTab = $hasPasswordErrors || $passwordFlash ? 'security' : ($hasDeleteErrors ? 'privacy' : 'account');
@endphp

@section('content')
    <main class="settings-main" id="designerMain" data-initial-tab="{{ $initialTab }}">
        @include('designer.partials.flash')

        <div class="designer-breadcrumb"><a href="{{ route('designer.dashboard') }}">الرئيسية</a><i class="bi bi-chevron-left" aria-hidden="true"></i><span>الإعدادات</span></div>

        <header class="designer-page-heading settings-heading">
            <span class="designer-heading-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
            <div><h1>إعدادات الحساب</h1><p>إدارة معلومات الحساب والأمان والإشعارات والتفضيلات.</p></div>
            <span class="settings-role-badge"><i class="bi bi-palette"></i><span>حساب مصمم</span></span>
        </header>

        <div class="settings-layout">
            <nav class="settings-tabs" aria-label="أقسام الإعدادات">
                <button type="button" class="settings-tab active" data-settings-tab="account" data-no-press aria-selected="true"><i class="bi bi-person"></i><span>الحساب</span></button>
                <button type="button" class="settings-tab" data-settings-tab="security" data-no-press aria-selected="false"><i class="bi bi-shield-lock"></i><span>الأمان</span></button>
                <button type="button" class="settings-tab" data-settings-tab="notifications" data-no-press aria-selected="false"><i class="bi bi-bell"></i><span>الإشعارات</span></button>
                <button type="button" class="settings-tab" data-settings-tab="role" data-no-press aria-selected="false"><i class="bi bi-patch-check"></i><span>حالة الاعتماد</span></button>
                <button type="button" class="settings-tab" data-settings-tab="preferences" data-no-press aria-selected="false"><i class="bi bi-sliders"></i><span>التفضيلات</span></button>
                <button type="button" class="settings-tab is-danger" data-settings-tab="privacy" data-no-press aria-selected="false"><i class="bi bi-exclamation-triangle"></i><span>الخصوصية</span></button>
            </nav>

            <div class="settings-content">
                <section class="settings-panel active" data-settings-panel="account">
                    <header class="settings-section-head"><span><i class="bi bi-person-vcard"></i></span><div><h2>معلومات الحساب</h2><p>البيانات الأساسية المستخدمة في حسابك.</p></div></header>
                    <form class="settings-form" id="accountSettingsForm" method="POST" action="{{ route('designer.settings.account') }}">
                        @csrf
                        @method('PATCH')
                        @if ($hasAccountErrors)
                            <div class="designer-form-errors" role="alert"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i><div><ul>@foreach ($errors->updateAccount->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
                        @endif
                        <div class="settings-field-grid">
                            <label class="profile-field"><span>الاسم الكامل</span><span class="profile-input-shell"><i class="bi bi-person"></i><input name="name" type="text" value="{{ old('name', $profile?->full_name ?: $user->name) }}" minlength="3" maxlength="255" required></span></label>
                            <label class="profile-field"><span>البريد الإلكتروني</span><span class="profile-input-shell"><i class="bi bi-envelope"></i><input name="email" type="email" value="{{ old('email', $user->email) }}" dir="ltr" maxlength="255" required></span></label>
                            <label class="profile-field"><span>رقم الهاتف</span><span class="profile-input-shell"><i class="bi bi-telephone"></i><input name="phone" type="tel" inputmode="numeric" value="{{ old('phone', $user->phone) }}" dir="ltr" maxlength="10" pattern="05[69][0-9]{7}" title="يجب أن يبدأ رقم الهاتف بـ 059 أو 056 ويتكوّن من 10 أرقام." placeholder="059xxxxxxx"></span></label>
                        </div>
                        <footer class="settings-form-actions"><button type="reset" class="profile-button is-ghost">تراجع</button><button type="submit" class="profile-button is-primary"><i class="bi bi-check2"></i>حفظ التغييرات</button></footer>
                    </form>
                </section>

                <section class="settings-panel" data-settings-panel="security" hidden>
                    <header class="settings-section-head"><span><i class="bi bi-shield-lock"></i></span><div><h2>الأمان وكلمة المرور</h2><p>حافظ على حسابك آمنًا بكلمة مرور قوية.</p></div></header>
                    <form class="settings-form" id="securitySettingsForm" method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')
                        @if ($hasPasswordErrors)
                            <div class="designer-form-errors" role="alert"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i><div><ul>@foreach ($errors->updatePassword->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
                        @endif
                        <div class="settings-field-grid is-single">
                            <label class="profile-field"><span>كلمة المرور الحالية</span><span class="profile-input-shell"><i class="bi bi-lock"></i><input name="current_password" type="password" autocomplete="current-password" required></span></label>
                            <label class="profile-field"><span>كلمة المرور الجديدة</span><span class="profile-input-shell"><i class="bi bi-key"></i><input name="password" type="password" minlength="8" autocomplete="new-password" required></span><small>8 أحرف على الأقل.</small></label>
                            <label class="profile-field"><span>تأكيد كلمة المرور</span><span class="profile-input-shell"><i class="bi bi-key-fill"></i><input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></span><small class="profile-field-error" id="passwordMatchError">كلمتا المرور غير متطابقتين.</small></label>
                        </div>
                        <footer class="settings-form-actions"><button type="submit" class="profile-button is-primary"><i class="bi bi-shield-check"></i>تحديث الأمان</button></footer>
                    </form>
                </section>

                <section class="settings-panel" data-settings-panel="notifications" hidden>
                    <header class="settings-section-head"><span><i class="bi bi-bell"></i></span><div><h2>تفضيلات الإشعارات</h2><p>اختر التنبيهات التي ترغب في استلامها.</p></div></header>
                    <div class="settings-options-list">
                        <div class="settings-option"><div><strong>إشعارات التصاميم</strong><small>تنبيهات مراجعة ونشر تصاميمك.</small></div><label class="profile-switch"><input type="checkbox" data-preference="design-notifications" data-preference-label="إشعارات التصاميم" checked><span class="profile-switch-track"></span></label></div>
                        <div class="settings-option"><div><strong>إشعارات الأرباح والمدفوعات</strong><small>تنبيهات التحويلات والأرصدة الجديدة.</small></div><label class="profile-switch"><input type="checkbox" data-preference="earnings-notifications" data-preference-label="إشعارات الأرباح" checked><span class="profile-switch-track"></span></label></div>
                        <div class="settings-option"><div><strong>النشرات والعروض</strong><small>العروض الجديدة وأخبار PalPrints.</small></div><label class="profile-switch"><input type="checkbox" data-preference="newsletter" data-preference-label="النشرات والعروض"><span class="profile-switch-track"></span></label></div>
                    </div>
                </section>

                <section class="settings-panel" data-settings-panel="role" hidden>
                    <header class="settings-section-head"><span><i class="bi bi-patch-check"></i></span><div><h2>اعتماد حساب المصمم</h2><p>تابع تفعيل البريد وحالة مراجعة حساب المصمم.</p></div></header>
                    <div class="settings-role-content">
                        @foreach ($requirements as $item)
                            <div class="settings-requirement"><i class="bi {{ $item['icon'] }}"></i><div><strong>{{ $item['title'] }}</strong><p>{{ $item['text'] }}</p></div><span @class(['settings-requirement-status', 'is-warning' => $item['warning']])>{{ $item['label'] }}</span></div>
                        @endforeach
                        @if ($adminNote && $needsChanges)
                            <div class="settings-role-note"><i class="bi bi-chat-left-text"></i><span>ملاحظات الإدارة: {{ $adminNote }}</span></div>
                        @endif
                        <div class="settings-role-note"><i class="bi bi-info-circle"></i><span>ستصلك إشعارات عند أي تغيير على حالة اعتماد الحساب أو مراجعة التصاميم.</span></div>
                        <div class="settings-form-actions"><a class="profile-button is-outline" href="{{ route('designer.profile') }}"><i class="bi bi-person"></i>الملف الشخصي</a></div>
                    </div>
                </section>

                <section class="settings-panel" data-settings-panel="preferences" hidden>
                    <header class="settings-section-head"><span><i class="bi bi-sliders"></i></span><div><h2>تفضيلات العرض</h2><p>خصص طريقة ظهور واستخدام المنصة.</p></div></header>
                    <div class="settings-options-list">
                        <div class="settings-option"><div><strong>الوضع الداكن</strong><small>استخدام الألوان الداكنة في واجهة الحساب.</small></div><label class="profile-switch"><input type="checkbox" id="settingsThemeToggle"><span class="profile-switch-track"></span></label></div>
                        <div class="settings-option"><div><strong>تقليل الحركات</strong><small>تقليل مؤثرات الانتقال والحركة.</small></div><label class="profile-switch"><input type="checkbox" id="reduceMotionToggle"><span class="profile-switch-track"></span></label></div>
                    </div>
                </section>

                <section class="settings-panel" data-settings-panel="privacy" hidden>
                    <header class="settings-section-head"><span class="is-danger"><i class="bi bi-exclamation-triangle"></i></span><div><h2>الخصوصية وإدارة الحساب</h2><p>حذف الحساب نهائيًا.</p></div></header>
                    <div class="settings-danger-zone is-danger">
                        <div><strong>حذف الحساب</strong><p>سيتم حذف بيانات الحساب نهائيًا ولا يمكن التراجع.</p></div><button type="button" class="profile-button is-danger" id="deleteAccountButton"><i class="bi bi-trash3"></i>حذف الحساب</button>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <dialog class="profile-dialog settings-delete-dialog" id="deleteAccountDialog" aria-labelledby="deleteAccountTitle" @if ($hasDeleteErrors) data-open-on-load @endif>
        <form method="POST" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
            @csrf
            @method('DELETE')
            <header class="profile-dialog-header"><div><h2 id="deleteAccountTitle">تأكيد حذف الحساب</h2><p>هذا الإجراء نهائي ولا يمكن التراجع عنه.</p></div><button type="button" class="profile-dialog-close" data-delete-close data-no-press aria-label="إغلاق"><i class="bi bi-x-lg"></i></button></header>
            <div class="profile-dialog-body">
                @if ($hasDeleteErrors)
                    <div class="designer-form-errors" role="alert"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i><div><ul>@foreach ($errors->userDeletion->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
                @endif
                <label class="profile-field"><span>اكتب «حذف حسابي» للتأكيد</span><span class="profile-input-shell"><input id="deleteConfirmation" type="text" autocomplete="off" required></span></label>
                <label class="profile-field"><span>كلمة المرور الحالية</span><span class="profile-input-shell"><i class="bi bi-lock"></i><input name="password" type="password" autocomplete="current-password" required></span></label>
            </div>
            <footer class="profile-dialog-footer"><button type="button" class="profile-button is-ghost" data-delete-close>إلغاء</button><button type="submit" class="profile-button is-danger">حذف الحساب نهائيًا</button></footer>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script>
        window.designerSettingsFlash = @json($passwordFlash ? 'password-updated' : $settingsFlash);
    </script>
    <script src="{{ asset('front/designer/js/settings.js') }}?v={{ filemtime(public_path('front/designer/js/settings.js')) }}"></script>
@endpush
