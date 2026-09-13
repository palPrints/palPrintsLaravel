{{--
    Ported from palPrintFront/checkout.html.
    Recipient/address/payment data and the order summary are still hardcoded
    demo data — same "demo data" situation the basket page is in — until this
    is wired to a real cart/orders/payment backend.
--}}
@extends('customer.layouts.app')

@section('title', 'إتمام الطلب')
@section('meta-description', 'إتمام الطلب في متجر PalPrints')
@section('body-class', 'storefront-page checkout-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/checkout.css') }}?v={{ filemtime(public_path('front/css/customer/checkout.css')) }}">
@endpush

@section('content')
    <div class="checkout-top">
      <div class="title-block">
        <div class="breadcrumbs"><a href="{{ route('home') }}">الرئيسية</a><span>/</span><a href="{{ route('customer.basket') }}">السلة</a><span>/</span><span>إتمام الطلب</span></div>
        <h1 id="pageTitle">إتمام الطلب <i class="bi bi-bag-lock"></i></h1>
        <p id="pageSubtitle">أكمل بياناتك لإتمام الطلب بسهولة وأمان</p>
      </div>
      <ol class="steps" aria-label="مراحل إتمام الطلب">
        <li data-step="1" class="active"><button type="button" data-no-press aria-disabled="true" tabindex="-1"><span>1</span><strong>بيانات المستلم</strong></button></li>
        <li data-step="2"><button type="button" data-no-press aria-disabled="true" tabindex="-1"><span>2</span><strong>عنوان التوصيل</strong></button></li>
        <li data-step="3"><button type="button" data-no-press aria-disabled="true" tabindex="-1"><span>3</span><strong>الدفع</strong></button></li>
        <li data-step="4"><button type="button" data-no-press aria-disabled="true" tabindex="-1"><span>4</span><strong>تأكيد الطلب</strong></button></li>
      </ol>
    </div>

    <div class="checkout-layout">
      <section class="checkout-card form-card" aria-live="polite">
        <form id="checkoutForm" novalidate>
          <div class="step-panel active" data-panel="1">
            <div class="card-heading"><i class="bi bi-person"></i><div><h2>بيانات المستلم</h2><p>يرجى إدخال بيانات المستلم بشكل صحيح</p></div></div>
            <div class="form-grid">
              <label class="field"><span>الاسم الكامل <b>*</b></span><span class="input-wrap"><i class="bi bi-person"></i><input id="fullName" name="fullName" type="text" placeholder="مثال: علاء المصري" autocomplete="name" required></span><small class="error"></small></label>
              <label class="field"><span>رقم الهاتف <b>*</b></span><span class="phone-wrap"><select aria-label="مفتاح الدولة"><option>+970</option><option>+972</option></select><span class="input-wrap"><i class="bi bi-telephone"></i><input id="phone" name="phone" type="tel" inputmode="tel" placeholder="59 123 4567" autocomplete="tel" required></span></span><small>سنتواصل معك على هذا الرقم بخصوص الطلب</small><small class="error"></small></label>
            </div>
            <label class="save-check"><input id="saveInfo" type="checkbox" checked><span><i class="bi bi-check"></i></span>حفظ هذه البيانات لاستخدامها لاحقاً</label>
            <button class="primary-btn next-btn" type="button" data-next="2">متابعة إلى عنوان التوصيل <i class="bi bi-arrow-left"></i></button>
          </div>

          <div class="step-panel" data-panel="2">
            <div class="card-heading"><i class="bi bi-geo-alt"></i><div><h2>عنوان التوصيل</h2><p>اختر عنوان التوصيل أو أضف عنوان جديد</p></div></div>
            <h3 class="section-label">العناوين المحفوظة</h3>
            <div class="address-list">
              <label class="choice-card selected"><input type="radio" name="address" value="المنزل" checked><span class="radio"></span><i class="bi bi-house"></i><span><strong>المنزل</strong><small>شارع الجلاء، عمارة رقم 5، الطابق الثالث<br>غزة، فلسطين</small></span><button type="button" class="edit-link">تعديل <i class="bi bi-pencil"></i></button></label>
              <label class="choice-card"><input type="radio" name="address" value="العمل"><span class="radio"></span><i class="bi bi-briefcase"></i><span><strong>العمل</strong><small>شارع الرشيد، برج فلسطين، الطابق الأول<br>غزة، فلسطين</small></span><button type="button" class="edit-link">تعديل <i class="bi bi-pencil"></i></button></label>
            </div>
            <button class="add-address" type="button"><i class="bi bi-plus-lg"></i> إضافة عنوان جديد</button>
            <div class="new-address" hidden><input type="text" placeholder="اكتب عنوان التوصيل الجديد"><button type="button">حفظ</button></div>
            <div class="button-row"><button class="secondary-btn" type="button" data-back="1"><i class="bi bi-arrow-right"></i> السابق</button><button class="primary-btn" type="button" data-next="3">متابعة إلى الدفع <i class="bi bi-arrow-left"></i></button></div>
          </div>

          <div class="step-panel" data-panel="3">
            <div class="card-heading"><i class="bi bi-credit-card"></i><div><h2>طريقة الدفع</h2><p>اختر طريقة الدفع المناسبة لك</p></div></div>
            <div class="payment-options">
              <label class="payment-card"><input type="radio" name="payment" value="bank"><span class="radio"></span><span class="payment-logo-frame payment-logo-frame--bank"><img class="payment-logo" src="{{ asset('front/assets/images/customer/payment-methods/bank-of-palestine.png') }}" alt="شعار بنك فلسطين"></span><span><strong>بنك فلسطين</strong><small>الدفع عبر تطبيق بنك فلسطين</small></span></label>
              <label class="payment-card selected"><input type="radio" name="payment" value="palpay" checked><span class="radio"></span><span class="payment-logo-frame payment-logo-frame--palpay"><img class="payment-logo" src="{{ asset('front/assets/images/customer/payment-methods/palpay.png') }}" alt="شعار PalPay"></span><span><strong>PalPay</strong><small>الدفع عبر محفظة PalPay</small></span></label>
              <label class="payment-card"><input type="radio" name="payment" value="jawwal"><span class="radio"></span><span class="payment-logo-frame payment-logo-frame--jawwal"><img class="payment-logo" src="{{ asset('front/assets/images/customer/payment-methods/jawwal-pay.png') }}" alt="شعار جوال بي"></span><span><strong>جوال بي</strong><small>الدفع عبر محفظة جوال بي</small></span></label>
            </div>
            <div class="payment-fields bank-fields" data-payment-fields="bank" hidden>
              <h3>بيانات الدفع عبر بنك فلسطين <i class="bi bi-bank"></i></h3>
              <div class="form-grid"><label class="field"><span>رقم الحساب أو العميل <b>*</b></span><span class="input-wrap"><i class="bi bi-person-vcard"></i><input id="bankAccount" type="text" inputmode="numeric" placeholder="أدخل رقم الحساب أو العميل"></span></label><label class="field"><span>رقم الجوال المسجل <b>*</b></span><span class="input-wrap"><i class="bi bi-telephone"></i><input id="bankPhone" type="tel" inputmode="tel" placeholder="59 123 4567"></span></label></div>
              <p class="info-line"><i class="bi bi-info-circle-fill"></i> سيتم إرسال طلب تأكيد الدفع إلى تطبيق بنك فلسطين</p>
            </div>
            <div class="payment-fields palpay-fields" data-payment-fields="palpay">
              <h3>بيانات الدفع عبر PalPay <i class="bi bi-phone"></i></h3>
              <div class="form-grid"><label class="field"><span>رقم الجوال <b>*</b></span><span class="input-wrap"><i class="bi bi-telephone"></i><input id="payPhone" type="tel" placeholder="59 123 4567"></span></label><label class="field"><span>المبلغ المراد دفعه</span><span class="input-wrap"><i class="bi bi-currency-dollar"></i><input value="118.00" readonly></span></label></div>
              <p class="info-line"><i class="bi bi-info-circle-fill"></i> سيصلك إشعار على محفظة PalPay لتأكيد عملية الدفع</p>
            </div>
            <div class="payment-fields jawwal-fields" data-payment-fields="jawwal" hidden>
              <h3>بيانات الدفع عبر جوال بي <i class="bi bi-phone"></i></h3>
              <div class="form-grid"><label class="field"><span>رقم محفظة جوال بي <b>*</b></span><span class="input-wrap"><i class="bi bi-telephone"></i><input id="jawwalPhone" type="tel" inputmode="tel" placeholder="59 123 4567"></span></label><label class="field"><span>المبلغ المراد دفعه</span><span class="input-wrap"><i class="bi bi-currency-dollar"></i><input value="118.00" readonly></span></label></div>
              <p class="info-line"><i class="bi bi-info-circle-fill"></i> سيتم إرسال رمز التحقق إلى رقم جوالك المسجل في جوال بي</p>
              <div class="otp-area"><span>رمز التحقق <b>*</b></span><div class="otp-inputs" dir="ltr"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"></div><small>تم إرسال رمز التحقق إلى جوالك · <b id="otpTimer">00:45</b></small></div>
            </div>
            <div class="button-row"><button class="secondary-btn" type="button" data-back="2"><i class="bi bi-arrow-right"></i> العودة إلى عنوان التوصيل</button><button class="primary-btn" type="button" data-next="4">دفع الآن وإنهاء العملية <i class="bi bi-arrow-left"></i></button></div>
            <p class="secure-note"><i class="bi bi-lock"></i> جميع معاملات الدفع آمنة ومشفرة</p>
          </div>

          <div class="step-panel" data-panel="4">
            <div class="card-heading review-heading"><i class="bi bi-clipboard-check"></i><div><h2>مراجعة الطلب</h2><p>تأكد من جميع التفاصيل قبل تأكيد الطلب</p></div></div>
            <div class="review-list">
              <div><i class="bi bi-person"></i><span><strong>بيانات المستلم</strong><small id="reviewCustomer">علاء المصري<br>+970 59 123 4567<br>example@mail.com</small></span><button type="button" data-back="1">تعديل</button></div>
              <div><i class="bi bi-geo-alt"></i><span><strong>عنوان التوصيل</strong><small id="reviewAddress">المنزل<br>غزة، الرمال، شارع الجلاء، بناية الخير، الطابق الثالث</small></span><button type="button" data-back="2">تعديل</button></div>
              <div><i class="bi bi-credit-card"></i><span><strong>طريقة الدفع</strong><small id="reviewPayment">الدفع عبر PalPay<br>تم التحقق من المحفظة بنجاح</small></span><button type="button" data-back="3">تعديل</button></div>
              <div><i class="bi bi-file-earmark-text"></i><span><strong>ملاحظات إضافية</strong><small>—</small></span><button type="button">تعديل</button></div>
            </div>
            <button class="primary-btn confirm-btn" type="submit">تأكيد الطلب <i class="bi bi-check-lg"></i></button>
            <p class="terms">بالضغط على تأكيد الطلب، فإنك توافق على <a href="{{ route('terms') }}">الشروط والأحكام</a> الخاصة بنا</p>
          </div>

          <div class="step-panel success-panel" data-panel="5">
            <div class="success-hero"><span class="success-icon"><i class="bi bi-check-lg"></i></span><div><h2>تم استلام طلبك بنجاح!</h2><p>نحن نعمل الآن على تجهيز طلبك وشحنه إليك</p><div class="order-number"><small>رقم الطلب</small><strong>#PLP-2026-000478</strong><button type="button" id="copyOrder" data-no-press aria-label="نسخ رقم الطلب"><i class="bi bi-copy"></i></button></div></div></div>
            <div class="order-details"><p><strong><i class="bi bi-receipt"></i> حالة الطلب</strong><span class="paid">تم الدفع <i class="bi bi-check"></i></span></p><p><strong><i class="bi bi-calendar3"></i> تاريخ الطلب</strong><span id="orderDate"></span></p><p><strong><i class="bi bi-receipt-cutoff"></i> إجمالي الطلب</strong><span>$118.00</span></p><p><strong><i class="bi bi-credit-card"></i> طريقة الدفع</strong><span>الدفع الإلكتروني (PalPay)</span></p><p><strong><i class="bi bi-geo-alt"></i> عنوان التوصيل</strong><span>غزة، الرمال، شارع الجلاء، بناية الخير</span></p></div>
            <p class="shipping-note"><i class="bi bi-truck"></i> سنقوم بإعلامك عند شحن طلبك مع رقم التتبع</p>
            <a class="primary-btn" href="{{ route('customer.orders') }}">تتبع طلبك <i class="bi bi-box-seam"></i></a><a class="back-store" href="{{ route('customer.store') }}">العودة إلى المتجر <i class="bi bi-chevron-left"></i></a>
          </div>
        </form>
      </section>

      <aside class="checkout-card summary-card" aria-label="ملخص الطلب">
        <div class="card-heading"><i class="bi bi-receipt"></i><div><h2>ملخص الطلب</h2><p>3 منتجات في السلة</p></div></div>
        <div class="summary-products">
          <article><img src="{{ asset('front/assets/images/customer/products/1.png') }}" alt="تيشيرت كلاسيك"><div><h3>تيشيرت كلاسيك</h3><strong>$40.00</strong><p>المقاس: L &nbsp;|&nbsp; اللون: أسود<br>الطباعة: أمامي</p></div><b>×2</b></article>
          <article><img src="{{ asset('front/assets/images/customer/products/2.png') }}" alt="هودي"><div><h3>هودي</h3><strong>$28.00</strong><p>المقاس: M &nbsp;|&nbsp; اللون: رمادي</p></div><b>×1</b></article>
          <article><img src="{{ asset('front/assets/images/customer/products/3.png') }}" alt="طاقية"><div><h3>طاقية</h3><strong>$45.00</strong><p>المقاس: مقاس واحد &nbsp;|&nbsp; اللون: أخضر</p></div><b>×3</b></article>
        </div>
        <div class="summary-totals"><p><span>المجموع الفرعي</span><strong>$113.00</strong></p><p class="shipping-row" hidden><span>تكلفة التوصيل</span><strong>$5.00</strong></p><p class="grand-total"><span>الإجمالي المتوقع</span><strong>$118.00</strong></p><small><i class="bi bi-shield-check"></i> الأسعار تشمل ضريبة القيمة المضافة</small></div>
      </aside>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/checkout.js') }}?v={{ filemtime(public_path('front/js/customer/checkout.js')) }}"></script>
@endpush
