<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  @include('auth.partials.head', [
      'title' => 'إنشاء حساب',
      'description' => 'إنشاء حساب جديد في منصة PalPrints كعميل أو مصمم أو مطبعة',
      'css' => 'register',
  ])
</head>
<body>
  @php
    $requestedRole = request('account_type');
    $role = old('account_type', in_array($requestedRole, ['customer', 'designer', 'print_provider'], true) ? $requestedRole : 'customer');
    $err = fn (string $key) => $errors->first($key);
  @endphp

  <main class="register-layout">
    <section class="register-visual" aria-label="منتجات مطبوعة من PalPrints">
      <img src="{{ asset('front/auth/images/pal-print-login-products.png') }}" alt="مجموعة منتجات مطبوعة ومخصصة" class="register-visual__image">
      <div class="register-visual__overlay" aria-hidden="true"></div>
      <div class="register-visual__content">
        <h2>مكان واحد يجمع <span>الفكرة والطباعة</span></h2>
        <p>ابدأ كعميل، شارك إبداعك كمصمم، أو وسّع أعمال مطبعتك.</p>
      </div>
    </section>

    <section class="register-panel" aria-labelledby="registerTitle">
      <a href="{{ route('home') }}" class="register-wordmark" aria-label="العودة إلى الصفحة الرئيسية">
        <img src="{{ asset('front/auth/images/palprints-wordmark-approved.png') }}" alt="PalPrints">
      </a>

      <div class="register-card">
        <header class="register-heading">
          <span class="eyebrow">انضم إلى مجتمعنا</span>
          <h1 id="registerTitle">أنشئ حسابك <span>الجديد</span></h1>
          <p>بياناتك الأساسية فقط، وتكمل باقي التفاصيل من ملفك الشخصي بعد التسجيل.</p>
        </header>

        <form id="registerForm" method="POST" action="{{ route('register') }}" novalidate>
          @csrf
          @if (! $registrationOpen)
            <p class="terms-error" role="alert">التسجيل مغلق مؤقتًا من إدارة المنصة، حاول لاحقًا.</p>
          @elseif ($err('account_type'))
            <p class="terms-error" role="alert">{{ $err('account_type') }}</p>
          @endif
          <div class="form-step is-active">
            <fieldset class="role-selector" aria-label="نوع الحساب">
              <div class="role-grid">
                <label class="role-option">
                  <input type="radio" name="account_type" value="customer" @checked($role === 'customer')>
                  <span class="role-card">
                    <i class="bi bi-person" aria-hidden="true"></i>
                    <strong>عميل</strong>
                    <small>اطلب وخصص منتجاتك</small>
                    <span class="role-check"><i class="bi bi-check-lg"></i></span>
                  </span>
                </label>
                <label class="role-option">
                  <input type="radio" name="account_type" value="designer" @checked($role === 'designer')>
                  <span class="role-card">
                    <i class="bi bi-pen" aria-hidden="true"></i>
                    <strong>مصمم</strong>
                    <small>اعرض تصاميمك وبِعها</small>
                    <span class="role-check"><i class="bi bi-check-lg"></i></span>
                  </span>
                </label>
                <label class="role-option">
                  <input type="radio" name="account_type" value="print_provider" @checked($role === 'print_provider')>
                  <span class="role-card">
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    <strong>مطبعة</strong>
                    <small>استقبل طلبات الطباعة</small>
                    <span class="role-check"><i class="bi bi-check-lg"></i></span>
                  </span>
                </label>
              </div>
              @error('account_type')<p class="terms-error">{{ $message }}</p>@enderror
            </fieldset>

            <div class="form-section">
              <div class="section-title">
                <span>1</span>
                <div><h2>المعلومات الأساسية</h2><p>بيانات الدخول والتواصل الخاصة بك</p></div>
              </div>
              <div class="fields-grid">
                <div @class(['form-field', 'is-invalid' => $errors->has('name')])>
                  <label for="fullName">الاسم الكامل <em>*</em></label>
                  <div class="input-control"><i class="bi bi-person"></i><input id="fullName" name="name" type="text" value="{{ old('name') }}" autocomplete="name" placeholder="أدخل اسمك الكامل" minlength="3" maxlength="255" required></div>
                  <span class="field-error">{{ $err('name') }}</span>
                </div>
                <div @class(['form-field', 'is-invalid' => $errors->has('email')])>
                  <label for="email">البريد الإلكتروني <em>*</em></label>
                  <div class="input-control"><i class="bi bi-envelope"></i><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" placeholder="name@example.com" maxlength="255" required></div>
                  <span class="field-error">{{ $err('email') }}</span>
                </div>
                <div @class(['form-field', 'is-invalid' => $errors->has('password')])>
                  <label for="password">كلمة المرور <em>*</em></label>
                  <div class="input-control"><i class="bi bi-lock"></i><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" placeholder="8 أحرف على الأقل" required><button class="password-toggle" type="button" data-target="password" aria-label="إظهار كلمة المرور"><i class="bi bi-eye"></i></button></div>
                  <span class="field-error">{{ $err('password') }}</span>
                </div>
                <div @class(['form-field', 'is-invalid' => $errors->has('password_confirmation')])>
                  <label for="passwordConfirm">تأكيد كلمة المرور <em>*</em></label>
                  <div class="input-control"><i class="bi bi-shield-lock"></i><input id="passwordConfirm" name="password_confirmation" type="password" autocomplete="new-password" placeholder="أعد كتابة كلمة المرور" required><button class="password-toggle" type="button" data-target="passwordConfirm" aria-label="إظهار كلمة المرور"><i class="bi bi-eye"></i></button></div>
                  <span class="field-error">{{ $err('password_confirmation') }}</span>
                </div>
              </div>
            </div>

            <label class="terms-option"><input type="checkbox" id="terms" name="terms" value="1" @checked(old('terms')) required><span>أوافق على <a href="{{ route('terms') }}" target="_blank" rel="noopener">الشروط والأحكام</a> و<a href="{{ route('privacy') }}" target="_blank" rel="noopener">سياسة الخصوصية</a></span></label>
            <p class="terms-error" id="termsError" aria-live="polite">{{ $err('terms') }}</p>
            <button class="submit-button" type="submit"><span>إنشاء حساب عميل</span><i class="bi bi-arrow-left"></i></button>
            <p class="form-status is-error" id="formStatus" role="status" aria-live="polite"></p>
            <p class="login-prompt">لديك حساب بالفعل؟ <a href="{{ route('login') }}">تسجيل الدخول</a></p>
          </div>
        </form>
      </div>
    </section>
  </main>

  <script src="{{ asset('front/auth/js/register.js') }}?v={{ filemtime(public_path('front/auth/js/register.js')) }}"></script>
@include('partials.page-loader')
</body>
</html>
