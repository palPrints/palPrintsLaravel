@extends('admin.layouts.app')

@section('title', 'الملف الشخصي')
@section('body-class', 'admin-tools-page admin-settings-user-style')
@section('main-id', 'adminProfileMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminDashboard.css').'?v='.filemtime(public_path('front/css/admin/adminDashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminTools.css').'?v='.filemtime(public_path('front/css/admin/adminTools.css')) }}">
@endpush

@section('content')
<main class="admin-main admin-tools-main" id="adminProfileMain">
    @include('admin.partials.breadcrumb', ['label' => 'الملف الشخصي'])
    <header class="admin-tools-heading admin-settings-heading">
        <span class="admin-settings-heading-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
        <div><h1>الملف الشخصي</h1><p>بياناتك الشخصية وكلمة المرور.</p></div>
        <span class="admin-settings-role-badge"><i class="bi bi-shield-check" aria-hidden="true"></i><span>حساب مدير النظام</span></span>
    </header>

    @if (session('profile_status'))
        <div class="admin-info-note" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i><span>{{ session('profile_status') }}</span></div>
    @endif

    <section class="admin-tool-card">
        <header class="admin-tool-card__header"><div><h2>البيانات الشخصية</h2><p>الاسم والبريد ورقم الهاتف.</p></div><span class="admin-section-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span></header>
        <form class="admin-settings-form" method="POST" action="{{ route('admin.profile.update') }}">
            @csrf
            @method('PATCH')
            <div class="admin-field-grid">
                <label><span>الاسم الكامل</span><input type="text" name="name" value="{{ old('name', $admin->name) }}" required></label>
                <label><span>البريد الإلكتروني</span><input type="email" name="email" value="{{ old('email', $admin->email) }}" dir="ltr" required></label>
                <label><span>رقم الهاتف</span><input type="tel" name="phone" value="{{ old('phone', $admin->phone) }}" dir="ltr" placeholder="059XXXXXXX"></label>
            </div>
            @if ($errors->any() && ! $errors->hasBag('password'))
                <ul class="admin-form-errors">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
            <footer class="admin-form-actions"><button class="admin-primary-button" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>حفظ البيانات</button></footer>
        </form>
    </section>

    <section class="admin-tool-card">
        <header class="admin-tool-card__header"><div><h2>تغيير كلمة المرور</h2><p>استخدمي كلمة مرور قوية لا تستخدمينها في مكان آخر.</p></div><span class="admin-section-icon"><i class="bi bi-key" aria-hidden="true"></i></span></header>
        <form class="admin-settings-form" method="POST" action="{{ route('admin.profile.password') }}">
            @csrf
            @method('PUT')
            <div class="admin-field-grid">
                <label><span>كلمة المرور الحالية</span><input type="password" name="current_password" autocomplete="current-password" required></label>
                <label><span>كلمة المرور الجديدة</span><input type="password" name="password" autocomplete="new-password" required></label>
                <label><span>تأكيد كلمة المرور</span><input type="password" name="password_confirmation" autocomplete="new-password" required></label>
            </div>
            @if ($errors->hasBag('password'))
                <ul class="admin-form-errors">@foreach ($errors->getBag('password')->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
            <footer class="admin-form-actions"><button class="admin-primary-button" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>تغيير كلمة المرور</button></footer>
        </form>
    </section>
</main>
@endsection
