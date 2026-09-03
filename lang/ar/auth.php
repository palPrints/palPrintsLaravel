<?php

return [
    'failed' => 'بيانات تسجيل الدخول غير صحيحة. تحقّق من البريد الإلكتروني وكلمة المرور.',
    'password' => 'كلمة المرور غير صحيحة.',
    'throttle' => 'تم تجاوز عدد محاولات تسجيل الدخول. حاول مجددًا بعد :seconds ثانية.',

    'login' => [
        'email_required' => 'أدخل البريد الإلكتروني.',
        'email_invalid' => 'أدخل بريدًا إلكترونيًا صحيحًا، مثل name@example.com.',
        'password_required' => 'أدخل كلمة المرور.',
        'password_invalid' => 'أدخل كلمة مرور صالحة.',
        'inactive' => 'هذا الحساب موقوف حاليًا. يرجى التواصل مع إدارة PALPRINTS.',
        'session_inactive' => 'تم تسجيل خروجك لأن حسابك غير نشط حاليًا. يرجى التواصل مع إدارة PALPRINTS.',
    ],

    'social' => [
        'account_type_required' => 'اختر نوع الحساب أولًا قبل المتابعة باستخدام Google أو Apple.',
        'terms_required' => 'يجب الموافقة على الشروط وسياسة الخصوصية قبل إنشاء الحساب.',
        'provider_not_configured' => 'تسجيل الدخول بواسطة :provider غير متاح حاليًا لعدم اكتمال الإعدادات.',
        'connection_failed' => 'تعذر بدء الاتصال مع :provider. حاول مرة أخرى.',
        'cancelled' => 'تم إلغاء المتابعة بواسطة :provider.',
        'settings_incomplete' => 'إعدادات :provider غير مكتملة.',
        'unable_to_complete' => 'تعذر إكمال تسجيل الدخول. حاول مرة أخرى.',
        'provider_login_failed' => 'تعذر إكمال تسجيل الدخول بواسطة :provider. حاول مرة أخرى.',
        'provider_id_missing' => 'لم يرسل مزوّد تسجيل الدخول معرّف الحساب المطلوب.',
        'email_unverified' => 'تعذر التحقق من بريدك الإلكتروني لدى :provider.',
        'login_account_missing' => 'لا يوجد حساب PALPRINTS مرتبط بهذا البريد. انتقل إلى إنشاء حساب واختر نوع الحساب أولًا.',
        'role_session_expired' => 'انتهت جلسة اختيار نوع الحساب. اختر نوع الحساب مرة أخرى.',
        'already_linked' => 'هذا الحساب مربوط مسبقًا بحساب :provider آخر.',
        'inactive' => 'هذا الحساب موقوف حاليًا. يرجى التواصل مع إدارة PALPRINTS.',
    ],
];
