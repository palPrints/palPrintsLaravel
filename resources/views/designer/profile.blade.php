@extends('designer.layouts.app')

@section('title', 'الملف الشخصي')

@push('styles')
    <link rel='stylesheet' href='{{ asset('front/designer/css/profile.css') }}?v={{ filemtime(public_path('front/designer/css/profile.css')) }}'>
@endpush

@section('content')
    <div class='designer-profile-page' id='designerProfilePage'>
        <header class='profile-page-heading'>
            <div>
                <p class='profile-page-eyebrow' data-copy-ar='مساحتك الإبداعية' data-copy-en='Your creative space'>مساحتك الإبداعية</p>
                <h1 data-copy-ar='الملف الشخصي' data-copy-en='Profile'>الملف الشخصي</h1>
            </div>

            <ol class='profile-breadcrumb' aria-label='مسار التنقل' data-profile-aria-ar='مسار التنقل' data-profile-aria-en='Breadcrumb'>
                <li>
                    <a href='{{ route('designer.dashboard') }}' data-copy-ar='لوحة التحكم' data-copy-en='Dashboard'>لوحة التحكم</a>
                </li>
                <li aria-current='page'>
                    <span data-copy-ar='الملف الشخصي' data-copy-en='Profile'>الملف الشخصي</span>
                </li>
            </ol>
        </header>

        @if($approval['status'] !== 'approved')
            <section @class(['designer-approval-alert', 'is-rejected' => $approval['status'] === 'rejected']) role='status'>
                <span class='designer-approval-alert-icon' aria-hidden='true'>
                    <i class='bi {{ $approval['status'] === 'rejected' ? 'bi-exclamation-triangle' : 'bi-info-circle' }}'></i>
                </span>

                <div>
                    @if($approval['status'] === 'rejected')
                        <h2 data-copy-ar='ملفك يحتاج إلى بعض التعديلات' data-copy-en='Your profile needs a few changes'>ملفك يحتاج إلى بعض التعديلات</h2>
                        <p data-copy-ar='راجع بياناتك المهنية وحدّثها قبل إرسالها للمراجعة مجددًا.' data-copy-en='Review and update your professional information before submitting it again.'>راجع بياناتك المهنية وحدّثها قبل إرسالها للمراجعة مجددًا.</p>
                    @elseif(in_array($approval['status'], ['submitted', 'under_review'], true))
                        <h2 data-copy-ar='ملفك قيد المراجعة' data-copy-en='Your profile is under review'>ملفك قيد المراجعة</h2>
                        <p data-copy-ar='استلمنا بياناتك وسنحدّث حالة الحساب بعد انتهاء المراجعة.' data-copy-en='We received your details and will update the account status after review.'>استلمنا بياناتك وسنحدّث حالة الحساب بعد انتهاء المراجعة.</p>
                    @else
                        <h2 data-copy-ar='أكمل ملفك ليظهر بصورة احترافية' data-copy-en='Complete your profile for a professional presence'>أكمل ملفك ليظهر بصورة احترافية</h2>
                        <p data-copy-ar='أضف نبذتك ومهاراتك ورابط معرض الأعمال من زر تعديل الملف.' data-copy-en='Add your bio, skills, and portfolio link from the edit profile button.'>أضف نبذتك ومهاراتك ورابط معرض الأعمال من زر تعديل الملف.</p>
                    @endif
                </div>

                <a href='{{ route('account.status') }}' class='profile-button is-outline is-small'>
                    <span data-copy-ar='عرض حالة الحساب' data-copy-en='View account status'>عرض حالة الحساب</span>
                </a>
            </section>
        @endif
        <section class='profile-card profile-hero' aria-labelledby='designerName'>
            <div class='profile-hero-cover' aria-hidden='true'></div>

            <div class='profile-hero-content'>
                <div class='profile-avatar-area'>
                    <div class='profile-avatar'>
                        <img
                            src='{{ $avatarUrl ?: '' }}'
                            alt='صورة المصمم'
                            data-profile-avatar-image
                            data-profile-alt-ar='صورة المصمم'
                            data-profile-alt-en='Designer photo'
                            @if(! $avatarUrl) hidden @endif
                        >
                        <span data-profile-avatar-initials @if($avatarUrl) hidden @endif>
                            {{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}
                        </span>
                    </div>

                    <button
                        type='button'
                        class='profile-avatar-upload'
                        data-dialog-open='designerEditDialog'
                        data-dialog-focus='profilePhotoButton'
                        aria-label='تغيير الصورة الشخصية'
                        data-profile-aria-ar='تغيير الصورة الشخصية'
                        data-profile-aria-en='Change profile photo'
                    >
                        <i class='bi bi-camera' aria-hidden='true'></i>
                    </button>
                </div>

                <div class='profile-identity'>
                    <div class='profile-name-line'>
                        <h2 id='designerName'>{{ $displayName }}</h2>
                        <span class='profile-badge {{ $approval['class'] }}'>
                            <i class='bi {{ $approval['icon'] }}' aria-hidden='true'></i>
                            <span data-copy-ar='{{ $approval['label_ar'] }}' data-copy-en='{{ $approval['label_en'] }}'>{{ $approval['label_ar'] }}</span>
                        </span>
                    </div>

                    <p class='profile-role' data-copy-ar='مصمم جرافيك' data-copy-en='Graphic designer'>مصمم جرافيك</p>

                    <div class='profile-contact-list'>
                        <a href='mailto:{{ $user->email }}' class='profile-contact-item'>
                            <i class='bi bi-envelope' aria-hidden='true'></i>
                            <span dir='ltr'>{{ $user->email }}</span>
                        </a>

                        @if($user->phone)
                            <a href='tel:{{ str_replace([' ', '-', '(', ')'], '', $user->phone) }}' class='profile-contact-item'>
                                <i class='bi bi-telephone' aria-hidden='true'></i>
                                <span dir='ltr'>{{ $user->phone }}</span>
                            </a>
                        @else
                            <span class='profile-contact-item is-muted'>
                                <i class='bi bi-telephone' aria-hidden='true'></i>
                                <span data-copy-ar='لم يُضف رقم هاتف' data-copy-en='No phone number added'>لم يُضف رقم هاتف</span>
                            </span>
                        @endif

                        <span class='profile-contact-item'>
                            <i class='bi bi-calendar3' aria-hidden='true'></i>
                            <span>
                                <span data-copy-ar='عضو منذ' data-copy-en='Member since'>عضو منذ</span>
                                {{ $user->created_at->format('Y-m') }}
                            </span>
                        </span>
                    </div>
                </div>

                <div class='profile-hero-actions'>
                    <a href='{{ route('designer.designs.create') }}' class='profile-button is-primary'>
                        <i class='bi bi-cloud-arrow-up' aria-hidden='true'></i>
                        <span data-copy-ar='رفع تصميم جديد' data-copy-en='Upload new design'>رفع تصميم جديد</span>
                    </a>

                    @if($portfolioUrl)
                        <a href='{{ $portfolioUrl }}' class='profile-button is-outline' target='_blank' rel='noopener noreferrer'>
                            <i class='bi bi-briefcase' aria-hidden='true'></i>
                            <span data-copy-ar='عرض معرض الأعمال' data-copy-en='View portfolio'>عرض معرض الأعمال</span>
                        </a>
                    @endif

                    <button type='button' class='profile-button is-outline' data-dialog-open='designerEditDialog'>
                        <i class='bi bi-person-gear' aria-hidden='true'></i>
                        <span data-copy-ar='تعديل الملف الشخصي' data-copy-en='Edit profile'>تعديل الملف الشخصي</span>
                    </button>
                </div>
            </div>
        </section>
        <div class='profile-row profile-row-2'>
            <section class='profile-card' aria-labelledby='designsStatusTitle'>
                <div class='profile-card-heading'>
                    <div class='profile-card-title'>
                        <span class='profile-card-icon'><i class='bi bi-grid-1x2' aria-hidden='true'></i></span>
                        <h2 id='designsStatusTitle' data-copy-ar='حالة تصاميمك' data-copy-en='Your design status'>حالة تصاميمك</h2>
                    </div>

                    <a href='{{ route('designer.designs.index') }}' class='profile-card-link'>
                        <span data-copy-ar='عرض الكل' data-copy-en='View all'>عرض الكل</span>
                        <i class='bi bi-arrow-left' data-back-icon aria-hidden='true'></i>
                    </a>
                </div>

                <div class='profile-stat-grid'>
                    <article class='profile-stat tone-grey'>
                        <strong>0</strong>
                        <span data-copy-ar='مسودة' data-copy-en='Draft'>مسودة</span>
                    </article>
                    <article class='profile-stat tone-green'>
                        <strong>0</strong>
                        <span data-copy-ar='منشور' data-copy-en='Published'>منشور</span>
                    </article>
                    <article class='profile-stat tone-orange'>
                        <strong>0</strong>
                        <span data-copy-ar='قيد المراجعة' data-copy-en='Under review'>قيد المراجعة</span>
                    </article>
                    <article class='profile-stat tone-red'>
                        <strong>0</strong>
                        <span data-copy-ar='مرفوض' data-copy-en='Rejected'>مرفوض</span>
                    </article>
                </div>
            </section>

            <section class='profile-card' aria-labelledby='performanceTitle'>
                <div class='profile-card-heading'>
                    <div class='profile-card-title'>
                        <span class='profile-card-icon icon-blue'><i class='bi bi-graph-up-arrow' aria-hidden='true'></i></span>
                        <h2 id='performanceTitle' data-copy-ar='ملخص الأداء' data-copy-en='Performance summary'>ملخص الأداء</h2>
                    </div>
                </div>

                <div class='profile-stat-grid'>
                    <article class='profile-stat has-icon'>
                        <span class='profile-stat-icon'><i class='bi bi-images' aria-hidden='true'></i></span>
                        <strong>0</strong>
                        <span data-copy-ar='تصميم منشور' data-copy-en='Published designs'>تصميم منشور</span>
                    </article>
                    <article class='profile-stat has-icon'>
                        <span class='profile-stat-icon'><i class='bi bi-bag-check' aria-hidden='true'></i></span>
                        <strong>{{ number_format((int) ($profile->total_sales ?? 0)) }}</strong>
                        <span data-copy-ar='عملية بيع' data-copy-en='Sales'>عملية بيع</span>
                    </article>
                    <article class='profile-stat has-icon'>
                        <span class='profile-stat-icon'><i class='bi bi-star' aria-hidden='true'></i></span>
                        <strong>0.0</strong>
                        <span data-copy-ar='التقييم العام' data-copy-en='Overall rating'>التقييم العام</span>
                    </article>
                    <article class='profile-stat has-icon'>
                        <span class='profile-stat-icon'><i class='bi bi-eye' aria-hidden='true'></i></span>
                        <strong>0</strong>
                        <span data-copy-ar='مشاهدة' data-copy-en='Views'>مشاهدة</span>
                    </article>
                </div>
            </section>
        </div>
        <div class='profile-row profile-row-3'>
            <section class='profile-card designer-portfolio' aria-labelledby='portfolioTitle'>
                <div class='profile-card-heading'>
                    <div class='profile-card-title'>
                        <span class='profile-card-icon'><i class='bi bi-briefcase' aria-hidden='true'></i></span>
                        <h2 id='portfolioTitle' data-copy-ar='معرض الأعمال' data-copy-en='Portfolio'>معرض الأعمال</h2>
                    </div>

                    @if($portfolioUrl)
                        <button
                            type='button'
                            class='profile-card-link profile-card-button'
                            data-dialog-open='designerEditDialog'
                            data-dialog-focus='portfolioInput'
                            data-copy-ar='تعديل'
                            data-copy-en='Edit'
                        >تعديل</button>
                    @endif
                </div>

                @if($portfolioUrl)
                    <a class='designer-portfolio-link' href='{{ $portfolioUrl }}' target='_blank' rel='noopener noreferrer' dir='ltr'>
                        <span>{{ str($portfolioUrl)->replace(['https://', 'http://'], '')->limit(34) }}</span>
                        <i class='bi bi-box-arrow-up-right' aria-hidden='true'></i>
                    </a>

                    <ul class='designer-portfolio-grid' aria-label='معاينة معرض الأعمال' data-profile-aria-ar='معاينة معرض الأعمال' data-profile-aria-en='Portfolio preview'>
                        <li class='designer-portfolio-thumb thumb-one'><i class='bi bi-card-heading' aria-hidden='true'></i></li>
                        <li class='designer-portfolio-thumb thumb-two'><i class='bi bi-bag' aria-hidden='true'></i></li>
                        <li class='designer-portfolio-thumb thumb-three'><i class='bi bi-image' aria-hidden='true'></i></li>
                    </ul>
                @else
                    <div class='profile-empty-state'>
                        <span><i class='bi bi-link-45deg' aria-hidden='true'></i></span>
                        <h3 data-copy-ar='لم تُضف معرض أعمال بعد' data-copy-en='No portfolio added yet'>لم تُضف معرض أعمال بعد</h3>
                        <p data-copy-ar='أضف رابط معرضك ليتمكن العملاء من مشاهدة أعمالك.' data-copy-en='Add your portfolio link so customers can discover your work.'>أضف رابط معرضك ليتمكن العملاء من مشاهدة أعمالك.</p>
                        <button
                            type='button'
                            class='profile-button is-primary is-small'
                            data-dialog-open='designerEditDialog'
                            data-dialog-focus='portfolioInput'
                        >
                            <span data-copy-ar='إضافة الرابط' data-copy-en='Add link'>إضافة الرابط</span>
                        </button>
                    </div>
                @endif
            </section>

            <section class='profile-card designer-skills' aria-labelledby='skillsTitle'>
                <div class='profile-card-heading'>
                    <div class='profile-card-title'>
                        <span class='profile-card-icon icon-orange'><i class='bi bi-stars' aria-hidden='true'></i></span>
                        <h2 id='skillsTitle' data-copy-ar='مهاراتي' data-copy-en='My skills'>مهاراتي</h2>
                    </div>

                    <button
                        type='button'
                        class='profile-card-link profile-card-button'
                        data-dialog-open='designerEditDialog'
                        data-dialog-focus='skillsInput'
                        data-copy-ar='تعديل'
                        data-copy-en='Edit'
                    >تعديل</button>
                </div>

                @if($profileSkills->isNotEmpty())
                    <ul class='designer-skill-list'>
                        @foreach($profileSkills as $skill)
                            <li class='designer-skill'>{{ $skill }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class='profile-empty-state is-compact'>
                        <span><i class='bi bi-stars' aria-hidden='true'></i></span>
                        <p data-copy-ar='أضف مهاراتك من نافذة تعديل الملف الشخصي.' data-copy-en='Add your skills from the Edit profile dialog.'>أضف مهاراتك من نافذة تعديل الملف الشخصي.</p>
                    </div>
                @endif
            </section>

            <section class='profile-card designer-about' aria-labelledby='aboutTitle'>
                <div class='profile-card-heading'>
                    <div class='profile-card-title'>
                        <span class='profile-card-icon icon-green'><i class='bi bi-person-lines-fill' aria-hidden='true'></i></span>
                        <h2 id='aboutTitle' data-copy-ar='نبذة عني' data-copy-en='About me'>نبذة عني</h2>
                    </div>

                    <button
                        type='button'
                        class='profile-card-link profile-card-button'
                        data-dialog-open='designerEditDialog'
                        data-dialog-focus='aboutInput'
                        data-copy-ar='تعديل'
                        data-copy-en='Edit'
                    >تعديل</button>
                </div>

                @if($profile->bio)
                    <p>{{ $profile->bio }}</p>
                @else
                    <p data-copy-ar='لم تُضف نبذة مهنية بعد. عرّف العملاء بخبرتك وأسلوبك.' data-copy-en='No professional bio added yet. Introduce customers to your experience and style.'>لم تُضف نبذة مهنية بعد. عرّف العملاء بخبرتك وأسلوبك.</p>
                @endif
            </section>
        </div>
        <div class='profile-row profile-row-2'>
            <section class='profile-card designer-account' aria-labelledby='accountTitle'>
                <div class='profile-card-heading'>
                    <div class='profile-card-title'>
                        <span class='profile-card-icon'><i class='bi bi-person-badge' aria-hidden='true'></i></span>
                        <h2 id='accountTitle' data-copy-ar='معلومات الحساب' data-copy-en='Account information'>معلومات الحساب</h2>
                    </div>
                </div>

                <dl class='profile-info-list'>
                    <div class='profile-info-row'>
                        <dt data-copy-ar='البريد الإلكتروني' data-copy-en='Email address'>البريد الإلكتروني</dt>
                        <dd dir='ltr'>{{ $user->email }}</dd>
                    </div>
                    <div class='profile-info-row'>
                        <dt data-copy-ar='رقم الهاتف' data-copy-en='Phone number'>رقم الهاتف</dt>
                        <dd dir='ltr'>{{ $user->phone ?: '—' }}</dd>
                    </div>
                    <div class='profile-info-row'>
                        <dt data-copy-ar='نوع الحساب' data-copy-en='Account type'>نوع الحساب</dt>
                        <dd data-copy-ar='مصمم' data-copy-en='Designer'>مصمم</dd>
                    </div>
                    <div class='profile-info-row'>
                        <dt data-copy-ar='تاريخ الانضمام' data-copy-en='Join date'>تاريخ الانضمام</dt>
                        <dd>{{ $user->created_at->format('Y-m-d') }}</dd>
                    </div>
                </dl>

                <p class='profile-card-note'>
                    <i class='bi bi-shield-lock' aria-hidden='true'></i>
                    <span data-copy-ar='كلمة المرور وإعدادات الأمان تُدار من صفحة الإعدادات.' data-copy-en='Password and security options are managed from Settings.'>كلمة المرور وإعدادات الأمان تُدار من صفحة الإعدادات.</span>
                </p>

                <a href='{{ route('designer.settings') }}' class='profile-button is-ghost is-small'>
                    <i class='bi bi-gear' aria-hidden='true'></i>
                    <span data-copy-ar='إدارة الإعدادات' data-copy-en='Manage settings'>إدارة الإعدادات</span>
                </a>
            </section>

            <section class='profile-card designer-earnings-summary' aria-labelledby='earningsTitle'>
                <div class='profile-card-heading'>
                    <div class='profile-card-title'>
                        <span class='profile-card-icon icon-green'><i class='bi bi-coin' aria-hidden='true'></i></span>
                        <h2 id='earningsTitle' data-copy-ar='ملخص الأرباح' data-copy-en='Earnings summary'>ملخص الأرباح</h2>
                    </div>

                    <a href='{{ route('designer.earnings') }}' class='profile-card-link'>
                        <span data-copy-ar='التفاصيل' data-copy-en='Details'>التفاصيل</span>
                        <i class='bi bi-arrow-left' data-back-icon aria-hidden='true'></i>
                    </a>
                </div>

                <div class='profile-earnings-total'>
                    <span data-copy-ar='إجمالي الأرباح المسجلة' data-copy-en='Recorded total earnings'>إجمالي الأرباح المسجلة</span>
                    <strong dir='ltr'>{{ number_format((float) ($profile->total_earnings ?? 0), 2) }} <small>₪</small></strong>
                </div>

                <p class='profile-card-note'>
                    <i class='bi bi-info-circle' aria-hidden='true'></i>
                    <span data-copy-ar='ستظهر تفاصيل الحركات المالية بعد ربط قاعدة بيانات الأرباح الجديدة.' data-copy-en='Transaction details will appear after the new earnings database is connected.'>ستظهر تفاصيل الحركات المالية بعد ربط قاعدة بيانات الأرباح الجديدة.</span>
                </p>
            </section>
        </div>
    </div>

    <dialog class='profile-dialog' id='designerEditDialog' aria-labelledby='designerEditTitle'>
        <form method='POST' action='{{ route('designer.profile.update') }}' id='designerEditForm' enctype='multipart/form-data'>
            @csrf
            @method('PATCH')
            <input type='hidden' name='locale' id='designerProfileLocale' value='{{ old('locale', 'ar') }}'>

            <header class='profile-dialog-header'>
                <div>
                    <h2 id='designerEditTitle' data-copy-ar='تعديل الملف الشخصي' data-copy-en='Edit profile'>تعديل الملف الشخصي</h2>
                    <p data-copy-ar='حدّث معلوماتك الأساسية والمهنية ثم احفظ التغييرات.' data-copy-en='Update your basic and professional information, then save.'>حدّث معلوماتك الأساسية والمهنية ثم احفظ التغييرات.</p>
                </div>

                <button type='button' class='profile-dialog-close' data-dialog-close aria-label='إغلاق' data-profile-aria-ar='إغلاق' data-profile-aria-en='Close'>
                    <i class='bi bi-x-lg' aria-hidden='true'></i>
                </button>
            </header>

            <div class='profile-dialog-body'>
                <div class='profile-photo-editor'>
                    <div class='profile-photo-preview'>
                        <img
                            src='{{ $avatarUrl ?: '' }}'
                            alt='معاينة الصورة الشخصية'
                            data-profile-avatar-image
                            data-profile-alt-ar='معاينة الصورة الشخصية'
                            data-profile-alt-en='Profile photo preview'
                            @if(! $avatarUrl) hidden @endif
                        >
                        <span data-profile-avatar-initials @if($avatarUrl) hidden @endif>
                            {{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}
                        </span>
                    </div>

                    <div class='profile-photo-copy'>
                        <strong data-copy-ar='الصورة الشخصية' data-copy-en='Profile photo'>الصورة الشخصية</strong>
                        <p data-copy-ar='PNG أو JPG أو WEBP، وبحجم أقصى 2 ميجابايت.' data-copy-en='PNG, JPG, or WEBP, up to 2 MB.'>PNG أو JPG أو WEBP، وبحجم أقصى 2 ميجابايت.</p>

                        <button
                            type='button'
                            class='profile-button is-outline is-small profile-photo-button'
                            id='profilePhotoButton'
                            data-avatar-picker
                        >
                            <i class='bi bi-camera' aria-hidden='true'></i>
                            <span data-copy-ar='اختيار صورة' data-copy-en='Choose photo'>اختيار صورة</span>
                        </button>
                        <input
                            id='profileAvatarInput'
                            class='profile-file-input'
                            name='profile_image'
                            type='file'
                            accept='image/png,image/jpeg,image/webp'
                            @error('profile_image') aria-invalid='true' aria-describedby='profileImageError' @enderror
                        >

                        @error('profile_image')
                            <small class='profile-field-error' id='profileImageError'>{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class='profile-field-grid'>
                    <div class='profile-field'>
                        <label for='editFullName' data-copy-ar='الاسم الكامل' data-copy-en='Full name'>الاسم الكامل</label>
                        <div @class(['profile-input-shell', 'has-error' => $errors->has('name')])>
                            <i class='bi bi-person' aria-hidden='true'></i>
                            <input
                                id='editFullName'
                                name='name'
                                type='text'
                                value='{{ old('name', $user->name) }}'
                                autocomplete='name'
                                required
                                minlength='3'
                                maxlength='255'
                                @error('name') aria-invalid='true' aria-describedby='nameError' @enderror
                            >
                        </div>
                        @error('name')<small class='profile-field-error' id='nameError'>{{ $message }}</small>@enderror
                    </div>

                    <div class='profile-field'>
                        <label for='editEmail' data-copy-ar='البريد الإلكتروني' data-copy-en='Email address'>البريد الإلكتروني</label>
                        <div @class(['profile-input-shell', 'has-error' => $errors->has('email')])>
                            <i class='bi bi-envelope' aria-hidden='true'></i>
                            <input
                                id='editEmail'
                                name='email'
                                type='email'
                                dir='ltr'
                                value='{{ old('email', $user->email) }}'
                                autocomplete='email'
                                required
                                maxlength='255'
                                @error('email') aria-invalid='true' aria-describedby='emailError' @enderror
                            >
                        </div>
                        @error('email')<small class='profile-field-error' id='emailError'>{{ $message }}</small>@enderror
                    </div>

                    <div class='profile-field is-wide'>
                        <label for='editPhone' data-copy-ar='رقم الهاتف' data-copy-en='Phone number'>رقم الهاتف</label>
                        <div @class(['profile-input-shell', 'has-error' => $errors->has('phone')])>
                            <i class='bi bi-telephone' aria-hidden='true'></i>
                            <input
                                id='editPhone'
                                name='phone'
                                type='tel'
                                dir='ltr'
                                value='{{ old('phone', $user->phone) }}'
                                autocomplete='tel'
                                maxlength='30'
                                @error('phone') aria-invalid='true' aria-describedby='phoneError' @enderror
                            >
                        </div>
                        @error('phone')<small class='profile-field-error' id='phoneError'>{{ $message }}</small>@enderror
                    </div>

                    <div class='profile-field is-wide'>
                        <label for='portfolioInput' data-copy-ar='رابط معرض الأعمال' data-copy-en='Portfolio link'>رابط معرض الأعمال</label>
                        <div @class(['profile-input-shell', 'has-error' => $errors->has('portfolio_url')])>
                            <i class='bi bi-link-45deg' aria-hidden='true'></i>
                            <input
                                id='portfolioInput'
                                name='portfolio_url'
                                type='url'
                                dir='ltr'
                                value='{{ old('portfolio_url', $profile->portfolio_url) }}'
                                placeholder='https://behance.net/username'
                                autocomplete='url'
                                maxlength='255'
                                @error('portfolio_url') aria-invalid='true' aria-describedby='portfolioError' @enderror
                            >
                        </div>
                        @error('portfolio_url')<small class='profile-field-error' id='portfolioError'>{{ $message }}</small>@enderror
                    </div>

                    <div class='profile-field is-wide'>
                        <label for='skillsInput' data-copy-ar='المهارات — افصل بينها بفاصلة' data-copy-en='Skills — separate them with commas'>المهارات — افصل بينها بفاصلة</label>
                        <div @class(['profile-input-shell', 'has-error' => $errors->has('skills')])>
                            <i class='bi bi-stars' aria-hidden='true'></i>
                            <input
                                id='skillsInput'
                                name='skills'
                                type='text'
                                value='{{ old('skills', $profileSkills->implode('، ')) }}'
                                maxlength='500'
                                @error('skills') aria-invalid='true' aria-describedby='skillsError' @enderror
                            >
                        </div>
                        @error('skills')<small class='profile-field-error' id='skillsError'>{{ $message }}</small>@enderror
                    </div>

                    <div class='profile-field is-wide'>
                        <label for='aboutInput' data-copy-ar='نبذة عني' data-copy-en='About me'>نبذة عني</label>
                        <div @class(['profile-input-shell', 'is-textarea', 'has-error' => $errors->has('bio')])>
                            <i class='bi bi-card-text' aria-hidden='true'></i>
                            <textarea
                                id='aboutInput'
                                name='bio'
                                rows='4'
                                maxlength='1000'
                                @error('bio') aria-invalid='true' aria-describedby='bioError' @enderror
                            >{{ old('bio', $profile->bio) }}</textarea>
                        </div>
                        @error('bio')<small class='profile-field-error' id='bioError'>{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>

            <footer class='profile-dialog-footer'>
                <button type='button' class='profile-button is-ghost' data-dialog-close data-copy-ar='إلغاء' data-copy-en='Cancel'>إلغاء</button>
                <button type='submit' class='profile-button is-primary' id='designerProfileSave'>
                    <i class='bi bi-check2' aria-hidden='true'></i>
                    <span data-copy-ar='حفظ التغييرات' data-copy-en='Save changes'>حفظ التغييرات</span>
                </button>
            </footer>
        </form>
    </dialog>

    <div
        class='profile-toast @if(session('profile_status') === 'updated') is-visible @endif'
        id='profileToast'
        role='status'
        aria-live='polite'
        data-copy-ar='تم تحديث الملف الشخصي بنجاح.'
        data-copy-en='Your profile was updated successfully.'
    >تم تحديث الملف الشخصي بنجاح.</div>
@endsection

@push('scripts')
    <script>
        window.designerProfileHasErrors = @json($errors->any());
    </script>
    <script src='{{ asset('front/designer/js/profile.js') }}?v={{ filemtime(public_path('front/designer/js/profile.js')) }}'></script>
@endpush
