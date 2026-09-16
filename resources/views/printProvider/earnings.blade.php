@extends('printProvider.layouts.app')

@section('title', 'الأرباح والمحفظة')

@section('content')
    <section class="wallet-overview">
        <div class="breadcrumb">
            <a href="{{ route('print-provider.dashboard') }}">لوحة التحكم</a>
            <i class="bi bi-chevron-left"></i>
            <span>الأرباح والمحفظة</span>
        </div>

        <header class="page-heading">
            <span class="heading-icon"><i class="bi bi-wallet2"></i></span>
            <div>
                <h1>الأرباح والمحفظة</h1>
                <p>تابع أرباحك وراقب طلبات السحب الخاصة بك من خلال هذه الصفحة.</p>
            </div>
        </header>

        <div class="summary-grid" aria-label="ملخص الأرباح">
            <article class="summary-card total">
                <span class="card-icon"><i class="bi bi-currency-dollar"></i></span>
                <div>
                    <h2>إجمالي الأرباح</h2>
                    <strong dir="ltr" data-dashboard-counter="12850" data-counter-currency="true">$12,850.00</strong>
                    <small>منذ بداية الحساب</small>
                </div>
            </article>

            <article class="summary-card available">
                <span class="card-icon"><i class="bi bi-wallet2"></i></span>
                <div>
                    <h2>الأرباح القابلة للسحب</h2>
                    <strong dir="ltr" data-dashboard-counter="2430" data-counter-currency="true">$2,430.00</strong>
                    <small><i class="bi bi-graph-up-arrow"></i> متاح للسحب الآن</small>
                </div>
            </article>

            <article class="summary-card pending">
                <span class="card-icon"><i class="bi bi-clock"></i></span>
                <div>
                    <h2>الأرباح المعلقة</h2>
                    <strong dir="ltr" data-dashboard-counter="680" data-counter-currency="true">$680.00</strong>
                    <small>بانتظار إتمام الطلبات</small>
                </div>
            </article>
        </div>
    </section>

    <section class="wallet-controls">
        <div class="period-filter">
            <h2><i class="bi bi-calendar3"></i> الفترة الزمنية</h2>
            <div class="period-options" role="group" aria-label="اختيار الفترة">
                <button type="button" data-period="today">اليوم</button>
                <button type="button" data-period="7">آخر 7 أيام</button>
                <button type="button" class="active" data-period="30">آخر 30 يوم</button>
                <button type="button" data-period="90">آخر 3 أشهر</button>
                <button type="button" data-period="custom">فترة مخصصة</button>
            </div>
            <p><i class="bi bi-info-circle"></i> تؤثر هذه الفترة على سجل الأرباح والبيانات المعروضة في الجدول أدناه.</p>
        </div>

        <div class="withdraw-action">
            <button id="openWithdraw" type="button"><i class="bi bi-plus-lg"></i> طلب سحب</button>
            <small>الحد الأدنى للسحب: <b dir="ltr">$100</b></small>
        </div>
    </section>

    <section class="data-card">
        <header><i class="bi bi-currency-dollar"></i><h2>سجل الأرباح</h2></header>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الطلب</th><th>المنتج / الخدمة</th><th>العميل</th><th>التاريخ</th><th>المبلغ</th><th>الحالة</th>
                    </tr>
                </thead>
                <tbody id="earningsRows">
                    <tr data-days="2"><td><a href="#">#10358</a></td><td><b>تيشيرت مطبوع</b></td><td>أحمد ناصر</td><td>12 سبتمبر 2026</td><td class="money">$250.00</td><td><span class="status pending">معلق</span></td></tr>
                    <tr data-days="3"><td><a href="#">#10357</a></td><td><b>أكواب مخصصة</b></td><td>سارة خالد</td><td>11 سبتمبر 2026</td><td class="money">$320.00</td><td><span class="status available">متاح للسحب</span></td></tr>
                    <tr data-days="4"><td><a href="#">#10356</a></td><td><b>ستيكرات</b></td><td>محمد علي</td><td>10 سبتمبر 2026</td><td class="money">$180.00</td><td><span class="status withdrawn">تم سحبه</span></td></tr>
                    <tr data-days="5"><td><a href="#">#10355</a></td><td><b>طباعة ورق</b></td><td>ريم حسن</td><td>9 سبتمبر 2026</td><td class="money">$140.00</td><td><span class="status available">متاح للسحب</span></td></tr>
                    <tr data-days="6"><td><a href="#">#10354</a></td><td><b>كروت شخصية</b></td><td>خالد إبراهيم</td><td>8 سبتمبر 2026</td><td class="money">$90.00</td><td><span class="status pending">معلق</span></td></tr>
                    <tr data-days="7"><td><a href="#">#10353</a></td><td><b>هودي مطبوع</b></td><td>نورة صالح</td><td>7 سبتمبر 2026</td><td class="money">$270.00</td><td><span class="status withdrawn">تم سحبه</span></td></tr>
                    <tr data-days="8"><td><a href="#">#10352</a></td><td><b>أكواب مخصصة</b></td><td>فاطمة حسين</td><td>6 سبتمبر 2026</td><td class="money">$110.00</td><td><span class="status available">متاح للسحب</span></td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="data-card withdrawals-card">
        <header><i class="bi bi-wallet2"></i><h2>طلبات السحب السابقة</h2></header>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>التاريخ</th><th>المبلغ</th><th>طريقة السحب</th><th>رقم المعاملة</th><th>الحالة</th><th>التفاصيل</th></tr>
                </thead>
                <tbody>
                    <tr><td>10 سبتمبر 2026</td><td class="money dark">$500.00</td><td>تحويل بنكي</td><td dir="ltr">TRX789456123</td><td><span class="status review">قيد المراجعة</span></td><td class="withdrawal-detail is-empty">—</td></tr>
                    <tr><td>1 سبتمبر 2026</td><td class="money dark">$750.00</td><td>محفظة إلكترونية</td><td dir="ltr">PP456123789</td><td><span class="status accepted">مقبول</span></td><td class="withdrawal-detail is-empty">—</td></tr>
                    <tr><td>20 أغسطس 2026</td><td class="money dark">$320.00</td><td>تحويل بنكي</td><td dir="ltr">TRX321654987</td><td><span class="status transferred">تم التحويل</span></td><td class="withdrawal-detail">تم التحويل في 22 أغسطس 2026</td></tr>
                    <tr><td>10 أغسطس 2026</td><td class="money dark">$200.00</td><td>محفظة إلكترونية</td><td dir="ltr">PP987654321</td><td><span class="status rejected">مرفوض</span></td><td class="withdrawal-detail is-rejected">سبب الرفض: بيانات الحساب غير مكتملة</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <dialog id="withdrawDialog" class="withdraw-dialog">
        <form id="withdrawForm">
            <header>
                <div>
                    <span class="dialog-icon"><i class="bi bi-wallet2"></i></span>
                    <div><h2>طلب سحب الأرباح</h2><p>الرصيد المتاح: <b dir="ltr">$2,430.00</b></p></div>
                </div>
                <button type="button" data-close aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
            </header>

            <div class="dialog-body">
                <label>المبلغ المراد سحبه
                    <span class="amount-field"><b>$</b><input id="withdrawAmount" type="number" min="100" max="2430" step="1" placeholder="100" required dir="ltr"></span>
                    <small>الحد الأدنى $100، والحد الأعلى هو رصيدك المتاح.</small>
                </label>

                <fieldset class="withdraw-methods">
                    <legend>طريقة السحب</legend>
                    <label class="method">
                        <input type="radio" name="method" value="bank-of-palestine">
                        <span class="method-card">
                            <img src="{{ asset('front/assets/images/customer/payment-methods/bank-of-palestine.png') }}" alt="">
                            <span class="method-copy"><b>بنك فلسطين</b><small>الدفع عبر تطبيق بنك فلسطين</small></span>
                        </span>
                    </label>
                    <label class="method">
                        <input type="radio" name="method" value="palpay" checked>
                        <span class="method-card">
                            <img src="{{ asset('front/assets/images/customer/payment-methods/palpay.png') }}" alt="">
                            <span class="method-copy"><b>PalPay</b><small>الدفع عبر محفظة PalPay</small></span>
                        </span>
                    </label>
                    <label class="method">
                        <input type="radio" name="method" value="jawwal-pay">
                        <span class="method-card">
                            <img src="{{ asset('front/assets/images/customer/payment-methods/jawwal-pay.png') }}" alt="">
                            <span class="method-copy"><b>جوال بي</b><small>الدفع عبر محفظة جوال بي</small></span>
                        </span>
                    </label>
                </fieldset>

                <label class="payout-account-label" for="payoutAccount">
                    <span id="payoutAccountLabel">رقم حساب PalPay</span>
                    <span class="payout-account-field">
                        <i class="bi bi-credit-card-2-front"></i>
                        <input id="payoutAccount" name="payoutAccount" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9]{9,10}" placeholder="أدخل رقم حساب PalPay" required dir="rtl">
                    </span>
                    <small id="payoutAccountHint">أدخل رقم الحساب المرتبط بمحفظة PalPay.</small>
                </label>
            </div>

            <footer>
                <button type="button" class="cancel" data-close>إلغاء</button>
                <button type="submit" class="submit">تأكيد طلب السحب</button>
            </footer>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/printProvider/earnings.js') }}"></script>
@endpush
