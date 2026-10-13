@extends('printProvider.layouts.app')

@section('title', 'الإعدادات')

@section('bodyClass', 'print-account-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/account.css') }}?v={{ filemtime(public_path('front/css/printProvider/account.css')) }}">
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
            'icon' => 'bi-shop',
            'title' => 'بيانات المطبعة',
            'text' => $profileComplete ? 'بيانات مطبعتك ووثائقك مكتملة.' : 'أكمل بيانات المطبعة والوثائق المطلوبة من الملف الشخصي.',
            'label' => $profileComplete ? 'مكتمل' : 'يحتاج استكمال',
            'warning' => ! $profileComplete,
        ],
        [
            'icon' => 'bi-patch-check',
            'title' => 'اعتماد حساب المطبعة',
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
    <div class="breadcrumb">
        <a href="{{ route('print-provider.dashboard') }}">الرئيسية</a>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span>الإعدادات</span>
    </div>

    <header class="page-heading">
        <span class="heading-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
        <div><h1>إعدادات الحساب</h1><p>إدارة معلومات الحساب والأمان وحالة الاعتماد.</p></div>
        <span class="acct-role-badge"><i class="bi bi-printer" aria-hidden="true"></i><span>حساب مطبعة</span></span>
    </header>

    @if ($settingsFlash === 'account-updated' || $passwordFlash)
        <div class="acct-flash" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i>{{ $passwordFlash ? 'تم تحديث كلمة المرور بنجاح.' : 'تم حفظ معلومات الحساب.' }}</div>
    @endif

    <div class="acct-layout" id="accountSettings" data-initial-tab="{{ $initialTab }}">
        <nav class="acct-tabs" aria-label="أقسام الإعدادات">
            <button type="button" class="acct-tab active" data-acct-tab="account" aria-selected="true"><i class="bi bi-person" aria-hidden="true"></i><span>الحساب</span></button>
            <button type="button" class="acct-tab" data-acct-tab="security" aria-selected="false"><i class="bi bi-shield-lock" aria-hidden="true"></i><span>الأمان</span></button>
            <button type="button" class="acct-tab" data-acct-tab="notifications" aria-selected="false"><i class="bi bi-bell" aria-hidden="true"></i><span>الإشعارات</span></button>
            <button type="button" class="acct-tab" data-acct-tab="role" aria-selected="false"><i class="bi bi-patch-check" aria-hidden="true"></i><span>حالة الاعتماد</span></button>
            <button type="button" class="acct-tab" data-acct-tab="preferences" aria-selected="false"><i class="bi bi-sliders" aria-hidden="true"></i><span>التفضيلات</span></button>
            <button type="button" class="acct-tab is-danger" data-acct-tab="privacy" aria-selected="false"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>الخصوصية</span></button>
        </nav>

        <section class="acct-panel active" data-acct-panel="account">
            <header class="acct-section-head"><span><i class="bi bi-person-vcard" aria-hidden="true"></i></span><div><h2>معلومات الحساب</h2><p>البيانات الأساسية المستخدمة في حسابك.</p></div></header>
            <form class="acct-form" method="POST" action="{{ route('print-provider.settings.account') }}">
                @csrf
                @method('PATCH')
                @if ($hasAccountErrors)
                    <div class="acct-errors" role="alert"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i><ul>@foreach ($errors->updateAccount->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
                @endif
                <div class="acct-grid">
                    <label class="acct-field"><span>الاسم الكامل</span><span class="acct-input"><i class="bi bi-person" aria-hidden="true"></i><input name="name" type="text" value="{{ old('name', $user->name) }}" minlength="3" maxlength="255" required></span></label>
                    <label class="acct-field"><span>البريد الإلكتروني</span><span class="acct-input"><i class="bi bi-envelope" aria-hidden="true"></i><input name="email" type="email" value="{{ old('email', $user->email) }}" dir="ltr" maxlength="255" required></span></label>
                    <label class="acct-field"><span>رقم الهاتف</span><span class="acct-input"><i class="bi bi-telephone" aria-hidden="true"></i><input name="phone" type="tel" value="{{ old('phone', $user->phone) }}" dir="ltr" maxlength="30"></span></label>
                </div>
                <footer class="acct-actions"><button type="reset" class="acct-button is-ghost">تراجع</button><button type="submit" class="acct-button is-primary"><i class="bi bi-check2" aria-hidden="true"></i>حفظ التغييرات</button></footer>
            </form>
        </section>

        <section class="acct-panel" data-acct-panel="security" hidden>
            <header class="acct-section-head"><span><i class="bi bi-shield-lock" aria-hidden="true"></i></span><div><h2>الأمان وكلمة المرور</h2><p>حافظ على حسابك آمنًا بكلمة مرور قوية.</p></div></header>
            <form class="acct-form" id="securityForm" method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')
                @if ($hasPasswordErrors)
                    <div class="acct-errors" role="alert"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i><ul>@foreach ($errors->updatePassword->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
                @endif
                <div class="acct-grid is-triple">
                    <label class="acct-field"><span>كلمة المرور الحالية</span><span class="acct-input"><i class="bi bi-lock" aria-hidden="true"></i><input name="current_password" type="password" autocomplete="current-password" required></span></label>
                    <label class="acct-field"><span>كلمة المرور الجديدة</span><span class="acct-input"><i class="bi bi-key" aria-hidden="true"></i><input name="password" type="password" minlength="8" autocomplete="new-password" required></span><small>8 أحرف على الأقل.</small></label>
                    <label class="acct-field"><span>تأكيد كلمة المرور</span><span class="acct-input"><i class="bi bi-key-fill" aria-hidden="true"></i><input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></span><small class="acct-field-error" id="passwordMatchError">كلمتا المرور غير متطابقتين.</small></label>
                </div>
                <div class="acct-grid is-single">
                    @include('partials.password-code-field', ['field' => 'acct-field', 'shell' => 'acct-input'])
                </div>
                <footer class="acct-actions"><button type="submit" class="acct-button is-primary"><i class="bi bi-shield-check" aria-hidden="true"></i>تحديث الأمان</button></footer>
            </form>
        </section>

        <section class="acct-panel" data-acct-panel="notifications" hidden>
            <header class="acct-section-head"><span><i class="bi bi-bell" aria-hidden="true"></i></span><div><h2>تفضيلات الإشعارات</h2><p>اختر التنبيهات التي ترغب في استلامها.</p></div></header>
            <div class="acct-options">
                <div class="acct-option"><div><strong>إشعارات الطلبات</strong><small>تنبيهات الطلبات الجديدة وتغيّر حالتها.</small></div><label class="acct-switch"><input type="checkbox" data-preference="order-notifications" data-preference-label="إشعارات الطلبات" checked><span class="acct-switch-track"></span></label></div>
                <div class="acct-option"><div><strong>إشعارات الأرباح والمدفوعات</strong><small>تنبيهات التحويلات والأرصدة الجديدة.</small></div><label class="acct-switch"><input type="checkbox" data-preference="earnings-notifications" data-preference-label="إشعارات الأرباح" checked><span class="acct-switch-track"></span></label></div>
                <div class="acct-option"><div><strong>النشرات والعروض</strong><small>العروض الجديدة وأخبار PalPrints.</small></div><label class="acct-switch"><input type="checkbox" data-preference="newsletter" data-preference-label="النشرات والعروض"><span class="acct-switch-track"></span></label></div>
            </div>
        </section>

        <section class="acct-panel" data-acct-panel="role" hidden>
            <header class="acct-section-head"><span><i class="bi bi-patch-check" aria-hidden="true"></i></span><div><h2>اعتماد حساب المطبعة</h2><p>تابع تفعيل البريد وحالة مراجعة حساب المطبعة.</p></div></header>
            <div class="acct-requirements">
                @foreach ($requirements as $item)
                    <div class="acct-requirement"><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i><div><strong>{{ $item['title'] }}</strong><p>{{ $item['text'] }}</p></div><span @class(['acct-status', 'is-warning' => $item['warning']])>{{ $item['label'] }}</span>@if ($item['icon'] === 'bi-envelope-check' && $item['warning'])<form method="POST" action="{{ route('verification.send') }}" class="email-verify-form">@csrf<button type="submit" class="email-verify-button"><i class="bi bi-send" aria-hidden="true"></i> أرسل رمز التوثيق</button></form>@endif</div>
                @endforeach
                @if ($adminNote && $needsChanges)
                    <div class="acct-note"><i class="bi bi-chat-left-text" aria-hidden="true"></i><span>ملاحظات الإدارة: {{ $adminNote }}</span></div>
                @endif
                <div class="acct-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>ستصلك إشعارات عند أي تغيير على حالة اعتماد الحساب.</span></div>
                <div class="acct-actions"><a class="acct-button is-outline" href="{{ route('print-provider.profile') }}"><i class="bi bi-person" aria-hidden="true"></i>الملف الشخصي</a></div>
            </div>
        </section>

        <section class="acct-panel" data-acct-panel="preferences" hidden>
            <header class="acct-section-head"><span><i class="bi bi-sliders" aria-hidden="true"></i></span><div><h2>تفضيلات العرض</h2><p>خصص طريقة ظهور واستخدام المنصة.</p></div></header>
            <div class="acct-options">
                <div class="acct-option"><div><strong>الوضع الليلي</strong><small>استخدام الألوان الداكنة في واجهة الحساب.</small></div><label class="acct-switch"><input type="checkbox" id="settingsThemeToggle"><span class="acct-switch-track"></span></label></div>
            </div>
        </section>

        <section class="acct-panel" data-acct-panel="privacy" hidden>
            <header class="acct-section-head"><span class="is-danger"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span><div><h2>الخصوصية وإدارة الحساب</h2><p>حذف الحساب نهائيًا.</p></div></header>
            <div class="acct-danger">
                <div><strong>حذف الحساب</strong><p>سيتم حذف بيانات الحساب نهائيًا ولا يمكن التراجع.</p></div>
                <button type="button" class="acct-button is-danger" id="deleteAccountButton"><i class="bi bi-trash3" aria-hidden="true"></i>حذف الحساب</button>
            </div>
        </section>
    </div>

    <dialog class="acct-dialog" id="deleteAccountDialog" aria-labelledby="deleteAccountTitle" @if ($hasDeleteErrors) data-open-on-load @endif>
        <form method="POST" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
            @csrf
            @method('DELETE')
            <header class="acct-dialog-head"><div><h2 id="deleteAccountTitle">تأكيد حذف الحساب</h2><p>هذا الإجراء نهائي ولا يمكن التراجع عنه.</p></div><button type="button" class="acct-close" data-delete-close aria-label="إغلاق"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <div class="acct-dialog-body">
                @if ($hasDeleteErrors)
                    <div class="acct-errors" role="alert"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i><ul>@foreach ($errors->userDeletion->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
                @endif
                <label class="acct-field"><span>اكتب «حذف حسابي» للتأكيد</span><span class="acct-input"><input id="deleteConfirmation" type="text" autocomplete="off" required></span></label>
                <label class="acct-field"><span>كلمة المرور الحالية</span><span class="acct-input"><i class="bi bi-lock" aria-hidden="true"></i><input name="password" type="password" autocomplete="current-password" required></span></label>
            </div>
            <footer class="acct-dialog-foot"><button type="button" class="acct-button is-ghost" data-delete-close>إلغاء</button><button type="submit" class="acct-button is-danger">حذف الحساب نهائيًا</button></footer>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/printProvider/account.js') }}?v={{ filemtime(public_path('front/js/printProvider/account.js')) }}"></script>
@endpush
