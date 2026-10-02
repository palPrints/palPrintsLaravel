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
    $canEdit = ! $awaiting;
    $startEditing = $errors->any();
    $selectedDays = old('days', $hours['days']);
    $isAvailable = old('available', $hours['available']);
    $dash = 'غير مضاف';
    $viewDays = $hours['days'];
    $savedServices = $provider->services ?? [];
    $selectedServices = $savedServices['selected'] ?? [];
    $otherServices = $savedServices['other'] ?? [];
    $selectedProducts = array_map('intval', old('products', $selectedProductIds));
    $shownProducts = $productOptions->whereIn('id', $selectedProductIds);
    $shownServices = array_merge(
        array_values(array_intersect_key($serviceOptions, array_flip($selectedServices))),
        $otherServices,
    );
@endphp

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('print-provider.dashboard') }}">لوحة التحكم</a>
        <i class="bi bi-chevron-left"></i>
        <span>الملف الشخصي</span>
    </div>

    <header class="page-heading">
        <span class="heading-icon"><i class="bi bi-person"></i></span>
        <div>
            <h1>الملف الشخصي للمطبعة</h1>
            <p id="providerPageDescription" data-view-text="بيانات مطبعتك كما تظهر في حسابك." data-edit-text="عدّل بيانات مطبعتك ثم احفظ التغييرات.">{{ $startEditing ? 'عدّل بيانات مطبعتك ثم احفظ التغييرات.' : 'بيانات مطبعتك كما تظهر في حسابك.' }}</p>
        </div>
        @if ($canEdit)
            <button class="primary-btn heading-action" id="editProviderProfile" type="button" aria-expanded="{{ $startEditing ? 'true' : 'false' }}" @if ($startEditing) hidden @endif>
                <i class="bi bi-pencil-square"></i> تعديل البيانات
            </button>
        @endif
    </header>

    @if ($errors->any())
        <div class="review-notice form-errors" role="alert">
            <i class="bi bi-exclamation-octagon"></i>
            <div><h3>تعذر حفظ التغييرات</h3><p>{{ $errors->first() }}</p></div>
        </div>
    @endif

    @if (session('warning'))
        <div class="review-notice form-errors" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <div><p>{{ session('warning') }}</p></div>
        </div>
    @endif

    @unless ($complete)
        <section class="completion-alert">
            <span><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <h2>أكمل ملفك الشخصي</h2>
                <p>اضغط "تعديل البيانات" وأدخل بيانات المطبعة وصاحبها، وارفع وثيقة التحقق وصورة الهوية، ثم احفظ التغييرات لتتمكن من إرسال الملف للمراجعة.</p>
            </div>
        </section>
    @endunless

    {{-- ========== عرض البيانات ========== --}}
    <div id="providerProfileView" @if ($startEditing) hidden @endif>
        <div class="two-column profile-section-grid">
            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-building"></i></span><h2>بيانات المطبعة</h2></header>
                <dl class="details-grid">
                    <div><dt>اسم المطبعة</dt><dd>{{ $provider->company_name ?: $dash }}</dd></div>
                    <div><dt>البريد الإلكتروني</dt><dd dir="ltr">{{ $user->email }}</dd></div>
                    <div><dt>هاتف المطبعة</dt><dd dir="ltr">{{ $provider->phone ?: $dash }}</dd></div>
                    <div><dt>واتساب</dt><dd dir="ltr">{{ $provider->whatsapp_number ?: $dash }}</dd></div>
                </dl>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-geo-alt"></i></span><h2>العنوان ومعلومات الموقع</h2></header>
                <dl class="details-grid single">
                    <div><dt>العنوان التفصيلي</dt><dd>{{ $provider->address ?: $dash }}</dd></div>
                </dl>
            </section>
        </div>

        <div class="three-column profile-section-grid">
            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-file-earmark-text"></i></span><h2>الوثائق</h2></header>
                <div class="documents-table">
                    <div class="table-head"><b>اسم الوثيقة</b><b>حالة الوثيقة</b></div>
                    @foreach ($documentLabels as $field => $label)
                        <div class="document-row">
                            <span>{{ $label }}</span>
                            @if (! in_array($field, $documentFields, true))
                                <strong class="status pending">غير متاحة بعد</strong>
                            @elseif (filled($provider->$field))
                                <strong class="status uploaded">مرفوع</strong>
                                <a class="doc-link" href="{{ asset('storage/'.$provider->$field) }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i> عرض</a>
                            @else
                                <strong class="status missing">غير مرفوع</strong>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-person"></i></span><h2>بيانات المسؤول</h2></header>
                <dl class="details-grid single">
                    <div><dt>اسم المسؤول</dt><dd>{{ $user->name ?: $dash }}</dd></div>
                    <div><dt>رقم هاتف صاحب المطبعة</dt><dd dir="ltr">{{ $user->phone ?: $dash }}</dd></div>
                    <div><dt>البريد الإلكتروني للمسؤول</dt><dd dir="ltr">{{ $provider->contact_email ?: $user->email ?: $dash }}</dd></div>
                </dl>
            </section>

            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-box-seam"></i></span><h2>المنتجات التي أطبعها</h2></header>
                <div class="work-days is-static service-chips">
                    @forelse ($shownProducts as $product)
                        <span class="selected">{{ $product->name }}</span>
                    @empty
                        <span>{{ $dash }}</span>
                    @endforelse
                </div>
            </section>

            @if ($hasServices)
                <section class="form-card">
                    <header class="card-title"><span><i class="bi bi-printer"></i></span><h2>الخدمات التي أقدمها</h2></header>
                    <div class="work-days is-static service-chips">
                        @forelse ($shownServices as $service)
                            <span class="selected">{{ $service }}</span>
                        @empty
                            <span>{{ $dash }}</span>
                        @endforelse
                    </div>
                </section>
            @endif
            <section class="form-card">
                <header class="card-title"><span><i class="bi bi-clock"></i></span><h2>معلومات العمل</h2></header>
                <div class="availability">
                    <div>
                        <b>متاح حالياً لاستقبال الطلبات</b>
                        <span @class(['availability-badge', 'off' => ! $hours['available']])>{!! $hours['available'] ? 'متاح <i></i>' : 'غير متاح' !!}</span>
                    </div>
                </div>
                <h3>أيام العمل</h3>
                <div class="work-days is-static">
                    @forelse ($viewDays as $day)
                        <span class="selected">{{ $day }}</span>
                    @empty
                        <span>{{ $dash }}</span>
                    @endforelse
                </div>
                <h3>ساعات العمل</h3>
                <dl class="details-grid">
                    <div><dt>من الساعة</dt><dd dir="ltr">{{ $hours['from'] }}</dd></div>
                    <div><dt>إلى الساعة</dt><dd dir="ltr">{{ $hours['to'] }}</dd></div>
                </dl>
            </section>
        </div>
    </div>

    {{-- ========== نموذج التعديل ========== --}}
    <form id="printerProfileForm" method="POST" action="{{ route('print-provider.profile.update') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PATCH')

        <div id="providerProfileEdit" @unless ($startEditing) hidden @endunless>
            <div class="two-column profile-section-grid">
                <section class="form-card">
                    <header class="card-title"><span><i class="bi bi-building"></i></span><h2>بيانات المطبعة</h2></header>
                    <div class="printer-data-layout">
                        <div class="fields">
                            <label>اسم المطبعة <em>*</em><input name="company_name" required value="{{ old('company_name', $provider->company_name) }}"></label>
                            <label>البريد الإلكتروني <em>*</em><span class="input-icon"><i class="bi bi-envelope"></i><input name="email" required type="email" value="{{ old('email', $user->email) }}" dir="ltr"></span></label>
                            <label>هاتف المطبعة <em>*</em><span class="input-icon"><i class="bi bi-telephone"></i><input name="phone" required value="{{ old('phone', $provider->phone) }}" dir="ltr"></span></label>
                            <label>واتساب<span class="input-icon whatsapp"><i class="bi bi-whatsapp"></i><input name="whatsapp_number" value="{{ old('whatsapp_number', $provider->whatsapp_number) }}" dir="ltr"></span></label>
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
                    <div class="upload-fields">
                        @foreach ($documentLabels as $field => $label)
                            @continue(! in_array($field, $documentFields, true))
                            <label class="upload-field"><span class="upload-title">{{ $label }} <em>*</em></span>
                                <input type="file" name="{{ $field }}" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf">
                                <small>
                                    @if (filled($provider->$field))
                                        <i class="bi bi-check-circle-fill"></i> تم رفع ملف سابقًا (<a href="{{ asset('storage/'.$provider->$field) }}" target="_blank" rel="noopener noreferrer">عرض</a>) — اختر ملفًا جديدًا لاستبداله.
                                    @else
                                        صورة أو ملف PDF، بحجم أقصى 5 ميجابايت.
                                    @endif
                                </small>
                            </label>
                        @endforeach
                        @unless (in_array('id_document', $documentFields, true))
                            <p class="upload-pending"><i class="bi bi-info-circle"></i> رفع صورة الهوية غير متاح بعد: بانتظار إضافة عمود <bdi>id_document</bdi> في قاعدة البيانات.</p>
                        @endunless
                    </div>
                </section>
    
                <section class="form-card">
                    <header class="card-title"><span><i class="bi bi-person"></i></span><h2>بيانات المسؤول</h2></header>
                    <div class="fields single">
                        <label>اسم المسؤول <em>*</em><input name="contact_name" required value="{{ old('contact_name', $user->name) }}"></label>
                        <label>رقم هاتف صاحب المطبعة <em>*</em><span class="input-icon"><i class="bi bi-telephone"></i><input name="owner_phone" required value="{{ old('owner_phone', $user->phone) }}" dir="ltr"></span></label>
                        @if ($hasContactEmail)
                            <label>البريد الإلكتروني للمسؤول<span class="input-icon"><i class="bi bi-envelope"></i><input name="contact_email" type="email" value="{{ old('contact_email', $provider->contact_email) }}" dir="ltr"></span></label>
                        @endif
                    </div>
                </section>

                <section class="form-card">
                    <header class="card-title"><span><i class="bi bi-box-seam"></i></span><h2>المنتجات التي أطبعها</h2></header>
                    <p class="services-hint">اختر المنتجات التي تقدمها مطبعتك. بعد موافقة الإدارة على طلبك تظهر هذه المنتجات في صفحة خدمات الطباعة لتكمل أسعارها ومدة تجهيزها.</p>
                    @foreach ($productOptions->groupBy(fn ($product) => $product->category?->name ?? 'منتجات أخرى') as $categoryName => $group)
                        <h3 class="services-group">{{ $categoryName }}</h3>
                        <div class="service-options">
                            @foreach ($group as $product)
                                <label class="service-option">
                                    <input type="checkbox" name="products[]" value="{{ $product->id }}" @checked(in_array($product->id, $selectedProducts, true))>
                                    <span>{{ $product->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </section>

                @if ($hasServices)
                    <section class="form-card">
                        <header class="card-title"><span><i class="bi bi-printer"></i></span><h2>الخدمات التي أقدمها</h2></header>
                        <p class="services-hint">اختر الخدمات التي تقدمها مطبعتك، وأضف أي خدمة غير موجودة بالقائمة.</p>
                        <div class="service-options">
                            @foreach ($serviceOptions as $code => $label)
                                <label class="service-option">
                                    <input type="checkbox" name="services[]" value="{{ $code }}" @checked(in_array($code, old('services', $selectedServices), true))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="fields single">
                            <label>خدمات أخرى<input name="other_services" maxlength="500" value="{{ old('other_services', implode('، ', $otherServices)) }}" placeholder="افصل بين الخدمات بفاصلة"></label>
                        </div>
                    </section>
                @endif
    
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
        </div>

        <footer class="form-actions" id="providerFormActions" @unless ($startEditing) hidden @endunless>
            <button class="primary-btn" type="submit"><i class="bi bi-floppy"></i> حفظ التغييرات</button>
            <a class="secondary-btn" id="cancelProviderEdit" href="{{ route('print-provider.profile') }}">إلغاء</a>
        </footer>
    </form>

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
