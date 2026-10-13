@extends('customer.layouts.app')

@section('title', 'إتمام الطلب')
@section('meta-description', 'إتمام طلب طباعة الورق في PalPrints')
@section('body-class', 'storefront-page checkout-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/checkout.css') }}?v={{ filemtime(public_path('front/css/customer/checkout.css')) }}">
@endpush

@section('content')
    <div class="checkout-top">
        <div class="title-block">
            <div class="breadcrumbs">
                <a href="{{ route('home') }}">الرئيسية</a><span>/</span>
                <a href="{{ route('customer.basket') }}">السلة</a><span>/</span>
                <span>إتمام الطلب</span>
            </div>
            <h1 id="pageTitle">إتمام الطلب <i class="bi bi-bag-lock"></i></h1>
            <p id="pageSubtitle">راجع بيانات طباعة الورق واختر عنوان التوصيل ثم أكمل الدفع.</p>
        </div>
        <ol class="steps" aria-label="مراحل إتمام الطلب">
            <li data-step="1" class="active"><button type="button" data-no-press><span>1</span><strong>المستلم</strong></button></li>
            <li data-step="2"><button type="button" data-no-press><span>2</span><strong>العنوان</strong></button></li>
            <li data-step="3"><button type="button" data-no-press><span>3</span><strong>الدفع</strong></button></li>
            <li data-step="4"><button type="button" data-no-press><span>4</span><strong>المراجعة</strong></button></li>
        </ol>
    </div>

    @if ($errors->any())
        <div class="alert-success page-alert" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="checkout-layout">
        <section class="checkout-card form-card" aria-live="polite">
            <form id="checkoutForm" method="POST" action="{{ route('customer.checkout.store') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="step-panel active" data-panel="1">
                    <div class="card-heading"><i class="bi bi-person"></i><div><h2>بيانات المستلم</h2><p>هذه البيانات تحفظ داخل لقطة الطلب وقت الشراء.</p></div></div>
                    <div class="form-grid">
                        <label class="field">
                            <span>الاسم الكامل <b>*</b></span>
                            <span class="input-wrap"><i class="bi bi-person"></i><input id="recipientName" name="recipient_name" type="text" value="{{ old('recipient_name', $customer->name) }}" autocomplete="name" required></span>
                            <small class="error"></small>
                        </label>
                        <label class="field">
                            <span>رقم الهاتف <b>*</b></span>
                            <span class="input-wrap"><i class="bi bi-telephone"></i><input id="phone" name="phone" type="tel" inputmode="numeric" maxlength="10" pattern="05[69][0-9]{7}" value="{{ old('phone', $customer->phone) }}" autocomplete="tel" required></span>
                            <small class="error"></small>
                        </label>
                    </div>
                    <button class="primary-btn next-btn" type="button" data-next="2">متابعة إلى عنوان التوصيل <i class="bi bi-arrow-left"></i></button>
                </div>

                <div class="step-panel" data-panel="2">
                    <div class="card-heading"><i class="bi bi-geo-alt"></i><div><h2>عنوان التوصيل</h2><p>اختر عنواناً محفوظاً أو أدخل عنواناً جديداً.</p></div></div>
                    @if($addresses->isNotEmpty())
                        <h3 class="section-label">العناوين المحفوظة</h3>
                        <div class="address-list">
                            @foreach($addresses as $address)
                                <label class="choice-card @if(old('address_id', $addresses->first()->id) == $address->id) selected @endif">
                                    <input type="radio" name="address_id" value="{{ $address->id }}" @checked(old('address_id', $addresses->first()->id) == $address->id)>
                                    <span class="radio"></span>
                                    <i class="bi bi-house"></i>
                                    <span>
                                        <strong>{{ $address->city }}{{ $address->region ? ' - '.$address->region : '' }}</strong>
                                        <small>{{ $address->street }}{{ $address->building ? '، '.$address->building : '' }}{{ $address->apartment ? '، شقة '.$address->apartment : '' }}<br>{{ $address->phone }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <button class="add-address" type="button" data-toggle-new-address><i class="bi bi-plus-lg"></i> استخدام عنوان جديد</button>
                    @else
                        <p class="info-line"><i class="bi bi-info-circle-fill"></i> لا يوجد عنوان محفوظ بعد. أدخل عنوان التوصيل لهذا الطلب.</p>
                        <button class="add-address" type="button" data-toggle-new-address><i class="bi bi-plus-lg"></i> استخدام عنوان جديد</button>
                    @endif

                    <div class="new-address" hidden>
                        <div class="form-grid">
                            <label class="field"><span>المدينة <b>*</b></span><span class="input-wrap"><i class="bi bi-buildings"></i><input name="city" type="text" value="{{ old('city') }}"></span><small class="error"></small></label>
                            <label class="field"><span>المنطقة</span><span class="input-wrap"><i class="bi bi-map"></i><input name="region" type="text" value="{{ old('region') }}"></span></label>
                            <label class="field"><span>الشارع <b>*</b></span><span class="input-wrap"><i class="bi bi-signpost"></i><input name="street" type="text" value="{{ old('street') }}"></span><small class="error"></small></label>
                            <label class="field"><span>المبنى</span><span class="input-wrap"><i class="bi bi-building"></i><input name="building" type="text" value="{{ old('building') }}"></span></label>
                            <label class="field"><span>الشقة</span><span class="input-wrap"><i class="bi bi-door-open"></i><input name="apartment" type="text" value="{{ old('apartment') }}"></span></label>
                        </div>
                    </div>

                    <div class="button-row"><button class="secondary-btn" type="button" data-back="1"><i class="bi bi-arrow-right"></i> السابق</button><button class="primary-btn" type="button" data-next="3">متابعة إلى الدفع <i class="bi bi-arrow-left"></i></button></div>
                </div>
                <div class="step-panel" data-panel="3">
                    <div class="card-heading"><i class="bi bi-credit-card"></i><div><h2>طريقة الدفع</h2><p>اختر طريقة الدفع المناسبة لإتمام الطلب.</p></div></div>
                    <div class="payment-options">
                        @if(in_array('palpay', $paymentMethods, true))<label class="payment-card {{ $paymentMethods[0] === 'palpay' ? 'selected' : '' }}"><input type="radio" name="payment_method" value="palpay" @checked($paymentMethods[0] === 'palpay')><span class="radio"></span><span class="payment-logo-frame payment-logo-frame--palpay"><img class="payment-logo" src="{{ asset('front/assets/images/customer/payment-methods/palpay.png') }}" alt="PalPay"></span><span><strong>PalPay</strong><small>الدفع عبر محفظة PalPay</small></span></label>@endif
                        @if(in_array('jawwal', $paymentMethods, true))<label class="payment-card {{ $paymentMethods[0] === 'jawwal' ? 'selected' : '' }}"><input type="radio" name="payment_method" value="jawwal" @checked($paymentMethods[0] === 'jawwal')><span class="radio"></span><span class="payment-logo-frame payment-logo-frame--jawwal"><img class="payment-logo" src="{{ asset('front/assets/images/customer/payment-methods/jawwal-pay.png') }}" alt="Jawwal Pay"></span><span><strong>جوال Pay</strong><small>الدفع عبر محفظة جوال Pay</small></span></label>@endif
                        @if(in_array('bank', $paymentMethods, true))<label class="payment-card {{ $paymentMethods[0] === 'bank' ? 'selected' : '' }}"><input type="radio" name="payment_method" value="bank" @checked($paymentMethods[0] === 'bank')><span class="radio"></span><span class="payment-logo-frame payment-logo-frame--bank"><img class="payment-logo" src="{{ asset('front/assets/images/customer/payment-methods/bank-of-palestine.png') }}" alt="Bank of Palestine"></span><span><strong>بنك فلسطين</strong><small>الدفع عبر تطبيق بنك فلسطين</small></span></label>@endif
                        @if($paymentMethods === [])
                            <p class="manual-payment-instructions">لا توجد طريقة دفع متاحة حاليًا. يرجى المحاولة لاحقًا أو التواصل مع الدعم.</p>
                        @endif
                    </div>
                    <div class="payment-fields manual-payment-instructions">
                        <p>لإتمام طلبك، يُرجى تحويل المبلغ المطلوب إلى الرقم الموضح أدناه.<br>بعد إتمام التحويل، يُرجى إرفاق صورة إشعار الدفع لتأكيد العملية.</p>
                    </div>
                    <label class="field payment-receipt-field" data-payment-receipt>
                        <span>&#1589;&#1608;&#1585;&#1577; &#1573;&#1588;&#1593;&#1575;&#1585; &#1575;&#1604;&#1583;&#1601;&#1593; <b>*</b></span>
                        <span class="input-wrap receipt-drop"><img class="receipt-thumb" alt="" hidden><i class="bi bi-cloud-arrow-up"></i><span class="receipt-text"><strong data-receipt-name>اضغطي لاختيار صورة الإشعار</strong><small>PNG أو JPG أو WEBP</small></span><input id="paymentReceipt" name="payment_receipt" type="file" accept="image/png,image/jpeg,image/webp"></span>
                        <small>&#1576;&#1593;&#1583; &#1573;&#1578;&#1605;&#1575;&#1605; &#1575;&#1604;&#1578;&#1581;&#1608;&#1610;&#1604; &#1593;&#1576;&#1585; PalPay &#1571;&#1608; &#1580;&#1608;&#1575;&#1604; Pay &#1571;&#1608; &#1576;&#1606;&#1603; &#1601;&#1604;&#1587;&#1591;&#1610;&#1606;&#1548; &#1575;&#1585;&#1601;&#1593;&#1610; &#1589;&#1608;&#1585;&#1577; &#1608;&#1575;&#1590;&#1581;&#1577; &#1605;&#1606; &#1573;&#1588;&#1593;&#1575;&#1585; &#1575;&#1604;&#1583;&#1601;&#1593; &#1581;&#1578;&#1609; &#1610;&#1578;&#1605; &#1578;&#1571;&#1603;&#1610;&#1583; &#1575;&#1604;&#1591;&#1604;&#1576;.</small>
                        <small class="error"></small>
                    </label>
                    <p class="secure-note"><i class="bi bi-lock"></i> جميع معاملات الدفع آمنة ومشفرة.</p>
                    <div class="button-row"><button class="secondary-btn" type="button" data-back="2"><i class="bi bi-arrow-right"></i> العودة إلى العنوان</button><button class="primary-btn" type="button" data-next="4">متابعة إلى مراجعة الطلب <i class="bi bi-arrow-left"></i></button></div>
                </div>
                <div class="step-panel" data-panel="4">
                    <div class="card-heading review-heading"><i class="bi bi-clipboard-check"></i><div><h2>مراجعة الطلب</h2><p>راجع بياناتك قبل التأكيد. يمكنك تعديل أي خطوة قبل إتمام الطلب.</p></div></div>
                    <div class="review-list">
                        <div><i class="bi bi-person"></i><span><strong>بيانات المستلم</strong><small id="reviewCustomer">{{ $customer->name }}<br>{{ $customer->phone }}</small></span><button type="button" data-back="1">تعديل</button></div>
                        <div><i class="bi bi-geo-alt"></i><span><strong>عنوان التوصيل</strong><small id="reviewAddress">سيتم استخدام العنوان المختار</small></span><button type="button" data-back="2">تعديل</button></div>
                        <div><i class="bi bi-credit-card"></i><span><strong>طريقة الدفع</strong><small id="reviewPayment">PalPay</small></span><button type="button" data-back="3">تعديل</button></div>
                    </div>
                    <textarea name="notes" rows="3" placeholder="ملاحظات إضافية للمطبعة أو التوصيل">{{ old('notes') }}</textarea>
                    <button class="primary-btn confirm-btn" type="submit">تأكيد الطلب والدفع <i class="bi bi-check-lg"></i></button>
                    <p class="terms">بالضغط على التأكيد، سيتم حفظ الطلب كمدفوع وتحويل السلة إلى طلب.</p>
                </div>
            </form>
        </section>

        <aside class="checkout-card summary-card" aria-label="ملخص الطلب">
            <div class="card-heading"><i class="bi bi-receipt"></i><div><h2>ملخص الطلب</h2><p>{{ $items->count() }} عنصر في السلة</p></div></div>
            <div class="summary-products">
                @foreach($items as $item)
                    <article>
                        @if(!empty($item['mockup']))
                            <div style="width:72px;height:76px;display:flex;justify-content:center">
                                @include('customer.partials.cart-mockup', ['mockup' => $item['mockup'], 'alt' => $item['product_name']])
                            </div>
                        @else
                            <img src="{{ $item['product_image'] }}" alt="{{ $item['product_name'] }}">
                        @endif
                        <div>
                            <h3>{{ $item['product_name'] }}</h3>
                            <strong>{{ number_format($item['total_price'], 2) }} ₪</strong>
                            @if(!empty($item['option_tags']))
                                <ul class="summary-tags">
                                    @foreach($item['option_tags'] as $tag)
                                        <li>{{ $tag }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if(!empty($item['options']['files']))
                                <details class="summary-files">
                                    <summary>عرض الملفات ({{ count($item['options']['files']) }})</summary>
                                    <ul>
                                        @foreach($item['options']['files'] as $file)
                                            <li><span>{{ $file['name'] }}</span>@if(!empty($file['page_count']))<small>{{ $file['page_count'] }} صفحة</small>@endif</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                        <b>×{{ $item['quantity'] }}</b>
                    </article>
                @endforeach
            </div>
            <div class="summary-totals">
                <p><span>المجموع الفرعي</span><strong>{{ number_format($subtotal, 2) }} ₪</strong></p>
                <p class="shipping-row"><span>تكلفة التوصيل</span><strong>{{ number_format($shippingCost, 2) }} ₪</strong></p>
                <p class="grand-total"><span>الإجمالي</span><strong>{{ number_format($totalAmount, 2) }} ₪</strong></p>
                <small><i class="bi bi-shield-check"></i> الأسعار النهائية ستُحفظ داخل الطلب كلقطة ثابتة.</small>
            </div>
        </aside>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/customer/checkout.js') }}?v={{ filemtime(public_path('front/js/customer/checkout.js')) }}"></script>
@endpush
