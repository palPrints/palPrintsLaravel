{{--
    Ported from palPrintFront/settings.html to match its exact settings.css/
    profile-core.css styling (the segmented underline tab strip, icon-in-shell
    inputs, compact spacing) — reproduced with this site's own --pp-* tokens
    (same hex values as the reference's --settings-* vars) instead of
    literally importing the reference's separate design system, since that
    system also carries the standalone "profile-app" shell and a lot of
    designer/print-provider-only CSS this page doesn't need.

    The reference's "role status" tab is customer-specific content there too
    (an address/delivery completion checklist, not an approval flow — that's
    only for designer/print-provider accounts) — kept it, backed by the real
    email-verified state, since there's no address column yet (see profile.blade.php).

    Real & working: security tab (Laravel's own password.update), privacy tab's
    delete-account (Laravel's own profile.destroy). Notifications toggles and
    "download my data" have no backing table/feature yet, so they're left as
    clearly-inert demo controls rather than faking persistence.
--}}
@extends('customer.layouts.app')

@section('title', 'الإعدادات')
@section('meta-description', 'إعدادات حساب عميل PalPrints')
@section('body-class', 'storefront-page settings-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/settings.css') }}?v={{ filemtime(public_path('front/css/customer/settings.css')) }}">
@endpush

@php
    $hasPasswordErrors = $errors->updatePassword->any();
    $initialTab = $hasPasswordErrors ? 'security' : 'account';
@endphp

@section('content')
    <section class="profile-content" aria-labelledby="pageTitle">
        <div class="pp-breadcrumb">
            <a href="{{ route('customer.store') }}">الرئيسية</a>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span>الإعدادات</span>
        </div>
        <header class="pp-page-heading">
            <span class="pp-heading-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
            <div><h1 id="pageTitle">إعدادات الحساب</h1><p>إدارة معلومات الحساب والأمان والإشعارات والتفضيلات.</p></div>
            <span class="pp-role-badge"><i class="bi bi-person-badge"></i><span>حساب عميل</span></span>
        </header>

        @if(session('status') === 'password-updated')
            <div class="alert-success page-alert">تم تحديث كلمة المرور بنجاح.</div>
        @endif

        <div class="settings-layout">
            <nav class="settings-tabs" aria-label="أقسام الإعدادات">
                @foreach([
                    'account' => ['bi-person', 'الحساب'],
                    'security' => ['bi-shield-lock', 'الأمان'],
                    'notifications' => ['bi-bell', 'الإشعارات'],
                    'address' => ['bi-geo-alt', 'العناوين والتوصيل'],
                    'preferences' => ['bi-sliders', 'التفضيلات'],
                    'privacy' => ['bi-exclamation-triangle', 'الخصوصية'],
                ] as $tabKey => [$icon, $label])
                    <button type="button" class="settings-tab{{ $tabKey === 'privacy' ? ' is-danger' : '' }}{{ $initialTab === $tabKey ? ' active' : '' }}" data-settings-tab="{{ $tabKey }}" data-no-press aria-selected="{{ $initialTab === $tabKey ? 'true' : 'false' }}"><i class="bi {{ $icon }}"></i><span>{{ $label }}</span></button>
                @endforeach
            </nav>

            <div class="app-card settings-content" style="padding: var(--sp-4) var(--sp-5) var(--sp-5);">

            {{-- الحساب --}}
            <section class="settings-panel{{ $initialTab === 'account' ? ' active' : '' }}" data-settings-panel="account" @if($initialTab !== 'account') hidden @endif>
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-person-vcard"></i></span>
                    <div><h2>معلومات الحساب</h2><p>البيانات الأساسية المستخدمة في حسابك.</p></div>
                </div>
                <div class="pp-field-grid">
                    <label class="pp-field"><span>الاسم الكامل</span><span class="pp-input-shell"><i class="bi bi-person"></i><input type="text" value="{{ $customer->name }}" dir="rtl" readonly></span></label>
                    <label class="pp-field"><span>البريد الإلكتروني</span><span class="pp-input-shell"><i class="bi bi-envelope"></i><input type="text" value="{{ $customer->email }}" dir="ltr" readonly></span></label>
                    <label class="pp-field"><span>رقم الهاتف</span><span class="pp-input-shell"><i class="bi bi-telephone"></i><input type="text" value="{{ $customer->phone ?: 'لم يتم إدخاله بعد' }}" dir="ltr" readonly></span></label>
                </div>
                <div class="settings-form-actions">
                    <a href="{{ route('customer.profile') }}" class="btn-brand-outline"><i class="bi bi-pencil"></i> تعديل من الملف الشخصي</a>
                </div>
            </section>

            {{-- الأمان --}}
            <section class="settings-panel{{ $initialTab === 'security' ? ' active' : '' }}" data-settings-panel="security" @if($initialTab !== 'security') hidden @endif>
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-shield-lock"></i></span>
                    <div><h2>الأمان وكلمة المرور</h2><p>حافظ على حسابك آمنًا بكلمة مرور قوية.</p></div>
                </div>
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="pp-field-grid is-single">
                        <label class="pp-field">
                            <span>كلمة المرور الحالية</span>
                            <span class="pp-input-shell"><i class="bi bi-lock"></i><input id="current_password" type="password" name="current_password" autocomplete="current-password" required></span>
                            @error('current_password', 'updatePassword') <small class="pp-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="pp-field">
                            <span>كلمة المرور الجديدة</span>
                            <span class="pp-input-shell"><i class="bi bi-key"></i><input id="password" type="password" name="password" autocomplete="new-password" required></span>
                            @error('password', 'updatePassword') <small class="pp-field-error">{{ $message }}</small> @else <small>8 أحرف على الأقل.</small> @enderror
                        </label>
                        <label class="pp-field">
                            <span>تأكيد كلمة المرور</span>
                            <span class="pp-input-shell"><i class="bi bi-key-fill"></i><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></span>
                        </label>
                    </div>
                    <div class="settings-form-actions">
                        <button type="submit" class="btn-brand"><i class="bi bi-shield-check"></i> تحديث كلمة المرور</button>
                    </div>
                </form>
            </section>

            {{-- الإشعارات --}}
            <section class="settings-panel" data-settings-panel="notifications" hidden>
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-bell"></i></span>
                    <div><h2>تفضيلات الإشعارات</h2><p>اختر التنبيهات التي ترغب في استلامها.</p></div>
                </div>
                <div class="settings-option">
                    <div><strong>إشعارات الطلبات</strong><small>تنبيهات حالة الطلبات والتحديثات الجديدة.</small></div>
                    <label class="pp-switch"><input type="checkbox" checked><span class="pp-switch-track"></span></label>
                </div>
                <div class="settings-option">
                    <div><strong>عروض وخصومات</strong><small>عروض PalPrints ومنتجاتها الجديدة.</small></div>
                    <label class="pp-switch"><input type="checkbox"><span class="pp-switch-track"></span></label>
                </div>
                <div class="settings-form-actions">
                    <button type="button" class="btn-brand" id="saveNotificationSettings"><i class="bi bi-check2"></i> حفظ التفضيلات</button>
                </div>
            </section>

            {{-- العناوين والتوصيل --}}
            <section class="settings-panel" data-settings-panel="address" hidden>
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-geo-alt"></i></span>
                    <div><h2>العناوين والتوصيل</h2><p>إدارة بيانات الاستلام والعناوين المحفوظة قبل الدفع.</p></div>
                </div>
                <div class="settings-requirement">
                    <i class="bi bi-envelope-check"></i>
                    <div><strong>تفعيل البريد الإلكتروني</strong><p>تم تأكيد البريد ويمكن استخدام جميع خصائص الحساب.</p></div>
                    <span class="settings-requirement-status {{ $customer->email_verified_at ? '' : 'is-warning' }}">{{ $customer->email_verified_at ? 'مكتمل' : 'يحتاج تأكيد' }}</span>
                </div>
                <div class="settings-requirement">
                    <i class="bi bi-telephone"></i>
                    <div><strong>رقم الهاتف</strong><p>يُستخدم للتواصل بخصوص الطلبات والتوصيل.</p></div>
                    <span class="settings-requirement-status {{ $customer->phone ? '' : 'is-warning' }}">{{ $customer->phone ? 'مكتمل' : 'يحتاج استكمال' }}</span>
                </div>
                <div class="settings-requirement">
                    <i class="bi bi-geo-alt"></i>
                    <div><strong>عنوان التوصيل الافتراضي</strong><p>أضف عنوانًا صالحًا لاستخدامه مباشرة عند إتمام الطلب.</p></div>
                    <span class="settings-requirement-status is-warning">يحتاج استكمال</span>
                </div>
                <div class="settings-role-note"><i class="bi bi-info-circle"></i><span>وفق متطلبات النظام، يجب اختيار عنوان توصيل صالح قبل الانتقال إلى خطوة الدفع.</span></div>
                <div class="settings-form-actions" style="justify-content: flex-start;">
                    <a href="{{ route('customer.profile') }}" class="btn-brand-outline"><i class="bi bi-pencil"></i> استكمال البيانات من الملف الشخصي</a>
                </div>
            </section>

            {{-- التفضيلات --}}
            <section class="settings-panel" data-settings-panel="preferences" hidden>
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-sliders"></i></span>
                    <div><h2>تفضيلات العرض</h2><p>خصص طريقة ظهور المنصة على جهازك.</p></div>
                </div>
                <div class="settings-option">
                    <div><strong>الوضع الليلي</strong><small>مظهر داكن لصفحات الحساب (الحساب، الأمان، الطلبات...).</small></div>
                    <label class="pp-switch"><input type="checkbox" id="darkModeToggle"><span class="pp-switch-track"></span></label>
                </div>
                <div class="settings-option">
                    <div><strong>تقليل الحركات</strong><small>تقليل مؤثرات الانتقال والحركة أثناء التصفح.</small></div>
                    <label class="pp-switch"><input type="checkbox" id="reduceMotionToggle"><span class="pp-switch-track"></span></label>
                </div>
            </section>

            {{-- الخصوصية --}}
            <section class="settings-panel" data-settings-panel="privacy" hidden>
                <div class="section-title">
                    <span class="section-icon"><i class="bi bi-exclamation-triangle"></i></span>
                    <div><h2>الخصوصية وإدارة الحساب</h2><p>تنزيل بياناتك أو حذف الحساب نهائيًا.</p></div>
                </div>
                <div class="settings-danger-zone">
                    <div><strong>تنزيل نسخة من بياناتي</strong><p>احصل على نسخة من معلومات حسابك ونشاطك.</p></div>
                    <button type="button" class="btn-brand-outline" id="downloadDataButton"><i class="bi bi-download"></i> طلب نسخة</button>
                </div>
                <div class="settings-danger-zone is-danger">
                    <div><strong>حذف الحساب</strong><p>سيتم حذف بيانات الحساب نهائيًا ولا يمكن التراجع.</p></div>
                    <button type="button" class="btn-brand-outline" id="deleteAccountButton" style="color: var(--pp-danger); border-color: #fca5a5;"><i class="bi bi-trash3"></i> حذف الحساب</button>
                </div>
            </section>
            </div>
        </div>
    </section>

    <dialog class="pp-dialog" id="deleteAccountDialog">
        <form method="POST" action="{{ route('profile.destroy') }}" class="pp-dialog-card" id="deleteAccountForm">
            @csrf
            @method('DELETE')
            <div class="pp-dialog-header">
                <div><h2>تأكيد حذف الحساب</h2><p>هذا الإجراء نهائي ولا يمكن التراجع عنه.</p></div>
                <button type="button" class="pp-dialog-close" data-delete-close aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
            </div>
            <label class="pp-field">
                <span>أدخلي كلمة المرور للتأكيد</span>
                <span class="pp-input-shell"><i class="bi bi-lock"></i><input id="delete_password" type="password" name="password" autocomplete="current-password" required></span>
                @error('password', 'userDeletion') <small class="pp-field-error">{{ $message }}</small> @enderror
            </label>
            <div class="pp-dialog-footer">
                <button type="button" class="btn-brand-outline" data-delete-close>إلغاء</button>
                <button type="submit" class="btn-brand" style="background: var(--pp-danger); box-shadow: none;">حذف الحساب نهائيًا</button>
            </div>
        </form>
    </dialog>

    <div class="toast" id="toast" role="status" aria-live="polite" hidden><i class="bi bi-check-circle-fill"></i><span></span></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/settings.js') }}?v={{ filemtime(public_path('front/js/customer/settings.js')) }}"></script>
@endpush
