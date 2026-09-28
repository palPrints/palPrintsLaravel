@extends('printProvider.layouts.app')

@section('title', 'الملف الشخصي للمطبعة')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/printProvider/profile.css') }}?v={{ filemtime(public_path('front/css/printProvider/profile.css')) }}">
@endpush

@php
    $status = $approvalStatus;
    $complete = $provider->profile_completed_at !== null;
    $awaiting = in_array($status, ['submitted', 'under_review'], true);
    $needsChanges = in_array($status, ['rejected', 'changes_requested'], true);
    $canSubmit = $complete && in_array($status, ['draft', 'rejected', 'changes_requested'], true);
    $selectedDays = old('days', $hours['days']);
    $isAvailable = old('available', $hours['available']);
@endphp

@section('content')
    <form id="printerProfileForm" method="POST" action="{{ route('print-provider.profile.update') }}">
        @csrf
        @method('PATCH')

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

        @if ($errors->any())
            <div class="review-notice" role="alert">
                <i class="bi bi-exclamation-octagon"></i>
                <div><h3>تعذر حفظ التغييرات</h3><p>{{ $errors->first() }}</p></div>
            </div>
        @endif

        @unless ($complete)
            <section class="completion-alert">
                <span><i class="bi bi-exclamation-triangle"></i></span>
                <div>
                    <h2>أكمل ملفك الشخصي</h2>
                    <p>أدخل اسم المطبعة ورقم الهاتف والعنوان ثم احفظ التغييرات لتتمكن من إرسال الملف للمراجعة.</p>
                </div>
            </section>
        @endunless

        <div class="two-column profile-section-grid">
            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-building"></i></span><h2>بيانات المطبعة</h2></header>
                <div class="printer-data-layout">
                    <div class="fields">
                        <label class="full">اسم المطبعة <em>*</em><input name="company_name" required value="{{ old('company_name', $provider->company_name) }}"></label>
                        <label>رقم الهاتف <em>*</em><span class="input-icon"><i class="bi bi-telephone"></i><input name="phone" required value="{{ old('phone', $provider->phone ?: $user->phone) }}" dir="ltr"></span></label>
                        <label>واتساب<span class="input-icon whatsapp"><i class="bi bi-whatsapp"></i><input name="whatsapp_number" value="{{ old('whatsapp_number', $provider->whatsapp_number) }}" dir="ltr"></span></label>
                        <label class="full">البريد الإلكتروني <em>*</em><span class="input-icon"><i class="bi bi-envelope"></i><input name="email" required type="email" value="{{ old('email', $user->email) }}" dir="ltr"></span></label>
                    </div>
                </div>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-geo-alt"></i></span><h2>العنوان ومعلومات الموقع</h2></header>
                <div class="fields address-fields">
                    <label class="full">العنوان التفصيلي <em>*</em>
                        <textarea name="address" required rows="4">{{ old('address', $provider->address) }}</textarea>
                    </label>
                </div>
            </section>
        </div>

        <div class="three-column profile-section-grid">
            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-file-earmark-text"></i></span><h2>الوثائق</h2></header>
                <div class="documents-table">
                    <div class="table-head"><b>اسم الوثيقة</b><b>حالة الوثيقة</b></div>
                    <div class="document-row"><span>رخصة المطبعة</span><strong class="status {{ filled($provider->license_document) ? 'uploaded' : 'missing' }}">{{ filled($provider->license_document) ? 'مرفوع' : 'غير مرفوع' }}</strong></div>
                    <div class="document-row"><span>وثيقة التحقق</span><strong class="status {{ filled($provider->verification_document) ? 'uploaded' : 'missing' }}">{{ filled($provider->verification_document) ? 'مرفوع' : 'غير مرفوع' }}</strong></div>
                </div>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-person"></i></span><h2>بيانات المسؤول</h2></header>
                <div class="fields single">
                    <label>اسم المسؤول <em>*</em><input name="contact_name" required value="{{ old('contact_name', $user->name) }}"></label>
                </div>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-clock"></i></span><h2>معلومات العمل</h2></header>
                <div class="availability">
                    <div>
                        <b>متاح حالياً لاستقبال الطلبات</b>
                        <span id="availabilityText" @class(['off' => ! $isAvailable])>{!! $isAvailable ? 'متاح <i></i>' : 'غير متاح' !!}</span>
                    </div>
                    <label class="switch">
                        <input id="availabilityToggle" name="available" value="1" type="checkbox" @checked($isAvailable)>
                        <span></span>
                    </label>
                </div>

                <h3>أيام العمل</h3>
                <div class="work-days" id="workDays">
                    @foreach ($allDays as $day)
                        <button @class(['selected' => in_array($day, $selectedDays, true)]) type="button" data-day="{{ $day }}">{{ $day }}</button>
                    @endforeach
                </div>
                <div id="workDaysInputs">
                    @foreach ($selectedDays as $day)
                        <input type="hidden" name="days[]" value="{{ $day }}">
                    @endforeach
                </div>

                <h3>ساعات العمل</h3>
                <div class="hours">
                    <label>من الساعة<span class="input-icon"><i class="bi bi-clock"></i><input type="time" name="from" value="{{ old('from', $hours['from']) }}"></span></label>
                    <label>إلى الساعة<span class="input-icon"><i class="bi bi-clock"></i><input type="time" name="to" value="{{ old('to', $hours['to']) }}"></span></label>
                </div>
            </section>
        </div>

        <section class="form-card review-card">
            <header class="card-title"><span><i class="bi bi-shield"></i></span><h2>حالة مراجعة الملف</h2></header>
            <div class="review-content">
                <div class="review-notice">
                    <i class="bi {{ $status === 'approved' ? 'bi-patch-check-fill' : ($needsChanges ? 'bi-x-circle-fill' : 'bi-hourglass-split') }}"></i>
                    <div>
                        @if ($status === 'approved')
                            <h3>تم اعتماد حسابك</h3>
                            <p>يمكنك استقبال طلبات الطباعة وإدارة خدماتك وأرباحك.</p>
                        @elseif ($awaiting)
                            <h3>ملفك قيد المراجعة</h3>
                            <p>سيتم إشعارك عند قبول أو رفض طلب اعتماد حسابك.</p>
                        @elseif ($needsChanges)
                            <h3>الملف بحاجة إلى تعديل</h3>
                            <p>راجع ملاحظات الإدارة وعدّل البيانات ثم أعد إرسال الملف للمراجعة.</p>
                        @elseif ($complete)
                            <h3>ملفك مكتمل، أرسله للمراجعة</h3>
                            <p>أرسل الملف ليراجعه فريق الإدارة ويفعّل حسابك.</p>
                        @else
                            <h3>جاري تجهيز بياناتك للمراجعة</h3>
                            <p>أكمل البيانات المطلوبة واحفظها ثم أرسل الملف للمراجعة.</p>
                        @endif
                    </div>
                </div>
                <div class="admin-notes">
                    <h3><i class="bi bi-check2-square"></i> ملاحظات الإدارة</h3>
                    <p>{{ $adminNote ?: 'لا يوجد تحديثات حالياً. يرجى متابعة حالة الملف لاحقاً.' }}</p>
                    @if ($provider->updated_at)
                        <b><i class="bi bi-clock"></i> آخر تحديث: {{ $provider->updated_at->locale('ar')->translatedFormat('j F Y — h:i A') }}</b>
                    @endif
                </div>
            </div>
        </section>

        <footer class="form-actions">
            <a class="secondary-btn" href="{{ route('print-provider.profile') }}">إلغاء</a>
            <div>
                <button class="primary-btn" type="submit"><i class="bi bi-floppy"></i> حفظ التغييرات</button>
            </div>
        </footer>
    </form>

    @if ($canSubmit)
        <form method="POST" action="{{ route('onboarding.submit') }}" class="submit-review-form">
            @csrf
            <button class="outline-primary" type="submit"><i class="bi bi-send"></i> إرسال للمراجعة</button>
        </form>
    @endif

    <div class="printshop-toast" id="toast" role="status" aria-live="polite" aria-atomic="true"><span></span></div>
@endsection

@push('scripts')
    <script>window.printProviderProfileFlash = @json(session('profile_status'));</script>
    <script src="{{ asset('front/js/printProvider/profile.js') }}?v={{ filemtime(public_path('front/js/printProvider/profile.js')) }}"></script>
@endpush
