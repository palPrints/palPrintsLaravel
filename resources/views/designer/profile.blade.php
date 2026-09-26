@extends('designer.layouts.app')

@section('title', 'الملف الشخصي')

@php
    $status = $approval['status'];
    $needsChanges = in_array($status, ['rejected', 'changes_requested'], true);
    $awaitingReview = in_array($status, ['submitted', 'under_review'], true);
    $profileComplete = $profile->profile_completed_at !== null;
    $adminNote = $profile->admin_notes ?: $profile->rejection_reason;
    $skillsText = old('skills', $profileSkills->implode('، '));
    $skillsForView = collect(preg_split('/[,،]/u', (string) $skillsText))->map(fn ($s) => trim($s))->filter()->values();
    $portfolio = old('portfolio_url', $profile->portfolio_url);
    $startEditing = $errors->any();
    $dash = 'غير مضاف';
@endphp

@section('content')
    <main class="designer-form-main" id="designerMain">
        @include('designer.partials.flash')

        <form id="designerProfileForm" method="POST" action="{{ route('designer.profile.update') }}" novalidate>
            @csrf
            @method('PATCH')
            <input type="hidden" name="locale" value="ar">

            <div class="designer-breadcrumb">
                <a href="{{ route('designer.dashboard') }}">الرئيسية</a>
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                <span>الملف الشخصي</span>
            </div>

            <header class="designer-page-heading">
                <span class="designer-heading-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
                <div>
                    <h1>الملف الشخصي للمصمم</h1>
                    <p id="designerPageDescription" data-view-text="بياناتك الشخصية والمهنية كما تظهر في حسابك." data-edit-text="عدّل بياناتك الشخصية والمهنية، ثم احفظ التغييرات.">بياناتك الشخصية والمهنية كما تظهر في حسابك.</p>
                </div>
                <button class="designer-primary-btn designer-heading-action" id="editDesignerProfile" type="button" aria-expanded="false">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                    تعديل البيانات الشخصية
                </button>
            </header>

            @unless ($profileComplete)
                <section class="designer-completion-alert">
                    <span><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                    <div>
                        <h2>أكمل ملفك الشخصي</h2>
                        <p>تأكد من إدخال الاسم الكامل والتخصص والمهارات ورابط معرض الأعمال قبل إرسال طلب اعتماد الحساب.</p>
                    </div>
                </section>
            @endunless

            @if ($errors->any())
                <div class="designer-form-errors" role="alert">
                    <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
                    <div>
                        <strong>تعذر حفظ التغييرات، راجع الحقول التالية:</strong>
                        <ul>
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="designer-profile-view" id="designerProfileView" @if ($startEditing) hidden @endif>
                <div class="designer-form-grid designer-two-column">
                    <section class="designer-form-card">
                        <header class="designer-card-title">
                            <span><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                            <h2>البيانات الشخصية</h2>
                        </header>
                        <dl class="designer-details-grid">
                            <div><dt>الاسم الكامل</dt><dd>{{ $displayName }}</dd></div>
                            <div><dt>المسمى المهني</dt><dd>{{ $profileExtra['job_title'] ?: $dash }}</dd></div>
                            <div><dt>سنوات الخبرة</dt><dd>{{ $profileExtra['experience'] ?: $dash }}</dd></div>
                            <div><dt>البريد الإلكتروني</dt><dd class="designer-detail-with-icon" dir="ltr">{{ $user->email }}</dd><i class="bi bi-envelope" aria-hidden="true"></i></div>
                            <div><dt>رقم الهاتف</dt><dd class="designer-detail-with-icon" dir="ltr">{{ $user->phone ?: $dash }}</dd><i class="bi bi-telephone" aria-hidden="true"></i></div>
                            <div><dt>موقع العمل</dt><dd>{{ $profileExtra['location'] ?: $dash }}</dd></div>
                        </dl>
                    </section>

                    <section class="designer-form-card">
                        <header class="designer-card-title">
                            <span><i class="bi bi-stars" aria-hidden="true"></i></span>
                            <h2>المهارات والتخصص</h2>
                        </header>
                        <dl class="designer-details-grid designer-details-single">
                            <div><dt>نبذة تعريفية</dt><dd>{{ $profileExtra['specialization'] ?: $dash }}</dd></div>
                            <div>
                                <dt>المهارات</dt>
                                <dd class="designer-skill-list">
                                    @forelse ($profileSkills as $skill)
                                        <span class="designer-skill-chip">{{ $skill }}</span>
                                    @empty
                                        <span class="designer-skill-chip">{{ $dash }}</span>
                                    @endforelse
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <div class="designer-form-grid designer-two-column">
                    <section class="designer-form-card">
                        <header class="designer-card-title">
                            <span><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                            <h2>معرض الأعمال</h2>
                        </header>
                        <div class="designer-portfolio-view">
                            <div><span>رابط معرض الأعمال</span><strong dir="ltr">{{ $profile->portfolio_url ?: $dash }}</strong></div>
                            @if ($portfolioUrl)
                                <a class="designer-outline-btn" href="{{ $portfolioUrl }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i> عرض المعرض</a>
                            @endif
                        </div>
                    </section>

                    <section class="designer-form-card">
                        <header class="designer-card-title">
                            <span><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                            <h2>كلمة المرور والأمان</h2>
                        </header>
                        <div class="designer-security-content">
                            <p>يمكنك تغيير كلمة المرور وإدارة إعدادات أمان الحساب بعد تسجيل الدخول.</p>
                            <a class="designer-outline-btn" href="{{ route('designer.settings') }}"><i class="bi bi-key"></i> تغيير كلمة المرور</a>
                        </div>
                    </section>
                </div>
            </div>

            <div class="designer-profile-edit" id="designerProfileEdit" @unless ($startEditing) hidden @endunless>
                <div class="designer-form-grid designer-two-column">
                    <section class="designer-form-card">
                        <header class="designer-card-title">
                            <span><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                            <h2>البيانات الشخصية</h2>
                        </header>
                        <div class="designer-fields">
                            <label class="designer-full-field">الاسم الكامل <em>*</em><input id="profileFullName" name="name" type="text" required minlength="3" maxlength="255" value="{{ old('name', $displayName) }}" @error('name') aria-invalid="true" @enderror>@error('name')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                            <label>المسمى المهني<input id="profileRole" name="job_title" type="text" maxlength="120" value="{{ old('job_title', $profileExtra['job_title']) }}">@error('job_title')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                            <label>سنوات الخبرة<input id="profileExperience" name="experience" type="text" maxlength="60" value="{{ old('experience', $profileExtra['experience']) }}">@error('experience')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                            <label>البريد الإلكتروني <em>*</em><span class="designer-input-icon"><i class="bi bi-envelope"></i><input id="profileEmail" name="email" type="email" required maxlength="255" value="{{ old('email', $user->email) }}" dir="ltr" @error('email') aria-invalid="true" @enderror></span>@error('email')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                            <label>رقم الهاتف<span class="designer-input-icon"><i class="bi bi-telephone"></i><input id="profilePhone" name="phone" type="tel" maxlength="30" value="{{ old('phone', $user->phone) }}" dir="ltr"></span>@error('phone')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                            <label class="designer-full-field">موقع العمل<input id="profileLocation" name="location" type="text" maxlength="120" value="{{ old('location', $profileExtra['location']) }}">@error('location')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                        </div>
                    </section>

                    <section class="designer-form-card">
                        <header class="designer-card-title">
                            <span><i class="bi bi-stars" aria-hidden="true"></i></span>
                            <h2>المهارات والتخصص</h2>
                        </header>
                        <div class="designer-fields designer-single-column">
                            <label>نبذة تعريفية <em>*</em><input id="profileSpecialization" name="specialization" type="text" required maxlength="1000" value="{{ old('specialization', $profileExtra['specialization']) }}">@error('specialization')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                            <label>المهارات <em>*</em><textarea id="profileSkills" name="skills" rows="3" required maxlength="500">{{ $skillsText }}</textarea>@error('skills')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                            <small class="designer-field-note"><i class="bi bi-info-circle"></i> افصل بين المهارات بفاصلة.</small>
                        </div>
                    </section>
                </div>

                <div class="designer-form-grid designer-two-column">
                    <section class="designer-form-card">
                        <header class="designer-card-title">
                            <span><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                            <h2>معرض الأعمال</h2>
                        </header>
                        <div class="designer-fields designer-single-column">
                            <label>رابط معرض الأعمال <em>*</em><span class="designer-input-icon"><i class="bi bi-link-45deg"></i><input id="profilePortfolio" name="portfolio_url" type="url" required maxlength="255" value="{{ $portfolio }}" dir="ltr"></span>@error('portfolio_url')<small class="designer-field-error">{{ $message }}</small>@enderror</label>
                            <div class="designer-inline-actions">
                                @if ($portfolioUrl)
                                    <a class="designer-outline-btn" href="{{ $portfolioUrl }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i> عرض المعرض</a>
                                @endif
                                <button class="designer-danger-btn" id="portfolioClearButton" type="button"><i class="bi bi-trash3"></i> حذف الرابط</button>
                            </div>
                        </div>
                    </section>

                    <section class="designer-form-card">
                        <header class="designer-card-title">
                            <span><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                            <h2>كلمة المرور والأمان</h2>
                        </header>
                        <div class="designer-security-content">
                            <p>يمكنك تغيير كلمة المرور وإدارة إعدادات أمان الحساب بعد تسجيل الدخول.</p>
                            <a class="designer-outline-btn" href="{{ route('designer.settings') }}"><i class="bi bi-key"></i> تغيير كلمة المرور</a>
                        </div>
                    </section>
                </div>
            </div>

            <section class="designer-form-card designer-review-card">
                <header class="designer-card-title">
                    <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                    <h2>حالة اعتماد الحساب</h2>
                </header>
                <div class="designer-review-content">
                    <div @class(['designer-review-notice', 'is-approved' => $status === 'approved', 'is-rejected' => $needsChanges])>
                        <i class="bi {{ $status === 'approved' ? 'bi-patch-check-fill' : ($needsChanges ? 'bi-x-circle-fill' : ($awaitingReview ? 'bi-hourglass-split' : 'bi-info-circle-fill')) }}" aria-hidden="true"></i>
                        <div>
                            @if ($status === 'approved')
                                <h3>حسابك معتمد</h3>
                                <p>يمكنك رفع التصاميم ونشرها وطلب استلام أرباحك.</p>
                            @elseif ($status === 'rejected')
                                <h3>تم رفض الحساب بسبب خلل في البيانات</h3>
                                <p>عدّل بياناتك ثم أرسل الطلب للمراجعة مرة أخرى.</p>
                            @elseif ($status === 'changes_requested')
                                <h3>حسابك بحاجة إلى تعديل البيانات</h3>
                                <p>عدّل البيانات المطلوبة ثم أرسل الطلب للمراجعة مرة أخرى.</p>
                            @elseif ($awaitingReview)
                                <h3>طلب الاعتماد قيد المراجعة</h3>
                                <p>سيصلك إشعار عند قبول أو رفض طلب اعتماد حسابك.</p>
                            @elseif ($profileComplete)
                                <h3>ملفك مكتمل، أرسله للمراجعة</h3>
                                <p>أرسل طلب اعتماد الحساب لتراجع الإدارة بياناتك وتفعّل صلاحيات المصمم.</p>
                            @else
                                <h3>أكمل ملفك الشخصي</h3>
                                <p>أضف التخصص والمهارات ورابط معرض الأعمال ثم أرسل الطلب للمراجعة.</p>
                            @endif
                        </div>
                    </div>
                    <div class="designer-admin-notes">
                        <h3><i class="bi bi-check2-square"></i> ملاحظات الإدارة</h3>
                        <p>{{ $adminNote ?: 'لا توجد ملاحظات حاليًا. يرجى متابعة حالة الحساب لاحقًا.' }}</p>
                    </div>
                </div>
            </section>

            <footer class="designer-form-actions" id="designerFormActions" @unless ($startEditing) hidden @endunless>
                <button class="designer-secondary-btn" id="cancelDesignerEdit" type="button">إلغاء</button>
                <div>
                    <button class="designer-primary-btn" type="submit"><i class="bi bi-floppy"></i> حفظ التغييرات</button>
                </div>
            </footer>
        </form>

        @if ($profileComplete && in_array($status, ['draft', 'rejected', 'changes_requested'], true))
            <form method="POST" action="{{ route('onboarding.submit') }}" class="designer-submit-review">
                @csrf
                <button class="designer-outline-primary" type="submit"><i class="bi bi-send"></i> إرسال للاعتماد</button>
            </form>
        @endif
    </main>
@endsection

@push('scripts')
    <script>
        window.designerProfileFlash = @json(session('profile_status'));
    </script>
    <script src="{{ asset('front/designer/js/designerProfile.js') }}?v={{ filemtime(public_path('front/designer/js/designerProfile.js')) }}"></script>
@endpush
