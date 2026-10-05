@extends('printProvider.layouts.app')

@section('title', 'الأرباح والمحفظة')

@php
    $available = (float) ($wallet?->available_balance ?? 0);
    $money = fn ($value) => number_format((float) $value, 2).' ₪';
    $txStatus = [
        'pending' => ['pending', 'معلق'],
        'available' => ['available', 'متاح للسحب'],
        'requested' => ['withdrawn', 'تم سحبه'],
        'complete' => ['withdrawn', 'تم سحبه'],
    ];
    $wdStatus = [
        'pending' => ['review', 'قيد المراجعة'],
        'approved' => ['accepted', 'مقبول'],
        'completed' => ['transferred', 'تم التحويل'],
        'rejected' => ['rejected', 'مرفوض'],
    ];
    $methodLabels = ['bank-of-palestine' => 'بنك فلسطين', 'palpay' => 'PalPay', 'jawwal-pay' => 'جوال بي'];
@endphp

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
                <span class="card-icon"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <h2>إجمالي الأرباح</h2>
                    <strong dir="ltr" data-dashboard-counter="{{ (float) ($wallet?->total_balance ?? 0) }}" data-counter-currency="true">{{ $money($wallet?->total_balance ?? 0) }}</strong>
                    <small>منذ بداية الحساب</small>
                </div>
            </article>

            <article class="summary-card available">
                <span class="card-icon"><i class="bi bi-wallet2"></i></span>
                <div>
                    <h2>الأرباح القابلة للسحب</h2>
                    <strong dir="ltr" data-dashboard-counter="{{ $available }}" data-counter-currency="true">{{ $money($available) }}</strong>
                    <small><i class="bi bi-graph-up-arrow"></i> متاح للسحب الآن</small>
                </div>
            </article>

            <article class="summary-card pending">
                <span class="card-icon"><i class="bi bi-clock"></i></span>
                <div>
                    <h2>الأرباح المعلقة</h2>
                    <strong dir="ltr" data-dashboard-counter="{{ (float) ($wallet?->pending_balance ?? 0) }}" data-counter-currency="true">{{ $money($wallet?->pending_balance ?? 0) }}</strong>
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
            <small>الحد الأدنى للسحب: <b dir="ltr">{{ $minimumWithdrawal }} ₪</b></small>
        </div>
    </section>

    <section class="data-card">
        <header><i class="bi bi-cash-coin"></i><h2>سجل الأرباح</h2></header>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الطلب</th><th>المنتج / الخدمة</th><th>العميل</th><th>التاريخ</th><th>المبلغ</th><th>الحالة</th>
                    </tr>
                </thead>
                <tbody id="earningsRows">
                    @forelse ($transactions as $transaction)
                        @php [$statusClass, $statusLabel] = $txStatus[$transaction->status] ?? ['available', $transaction->status]; @endphp
                        <tr data-days="{{ (int) $transaction->created_at?->diffInDays(now()) }}">
                            <td dir="ltr">{{ $transaction->reference_id ?: 'TRX-'.$transaction->id }}</td>
                            <td><b>{{ $transaction->description ?: 'أرباح طباعة' }}</b></td>
                            <td>—</td>
                            <td>{{ $transaction->created_at?->locale('ar')->translatedFormat('j F Y') }}</td>
                            <td class="money">{{ $money($transaction->amount) }}</td>
                            <td><span class="status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row"><i class="bi bi-inbox"></i>لا توجد أرباح مسجلة حتى الآن.</td></tr>
                    @endforelse
                    <tr class="filter-empty" hidden><td colspan="6" class="empty-row"><i class="bi bi-inbox"></i>لا توجد أرباح ضمن هذه الفترة.</td></tr>
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
                    @forelse ($withdrawals as $withdrawal)
                        @php [$statusClass, $statusLabel] = $wdStatus[$withdrawal->status] ?? ['review', $withdrawal->status]; @endphp
                        <tr>
                            <td>{{ $withdrawal->created_at?->locale('ar')->translatedFormat('j F Y') }}</td>
                            <td class="money dark">{{ $money($withdrawal->amount) }}</td>
                            <td>{{ $methodLabels[$withdrawal->method] ?? $withdrawal->method }}</td>
                            <td dir="ltr">{{ $withdrawal->reference_id }}</td>
                            <td><span class="status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            @if ($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                                <td class="withdrawal-detail is-rejected">{{ $withdrawal->rejection_reason }}</td>
                            @else
                                <td class="withdrawal-detail is-empty">—</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row"><i class="bi bi-inbox"></i>لا توجد طلبات سحب سابقة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <dialog id="withdrawDialog" class="withdraw-dialog">
        <form id="withdrawForm" data-withdraw-url="{{ route('print-provider.earnings.withdraw') }}">
            <header>
                <div>
                    <span class="dialog-icon"><i class="bi bi-wallet2"></i></span>
                    <div><h2>طلب سحب الأرباح</h2><p>الرصيد المتاح: <b dir="ltr">{{ $money($available) }}</b></p></div>
                </div>
                <button type="button" data-close aria-label="إغلاق"><i class="bi bi-x-lg"></i></button>
            </header>

            <div class="dialog-body">
                <label>المبلغ المراد سحبه
                    <span class="amount-field"><b>$</b><input id="withdrawAmount" type="number" min="{{ $minimumWithdrawal }}" max="{{ $available }}" step="0.01" placeholder="{{ $minimumWithdrawal }}" required dir="ltr"></span>
                    <small>الحد الأدنى {{ $minimumWithdrawal }} ₪، والحد الأعلى هو رصيدك المتاح.</small>
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
    <script>window.printProviderAvailableBalance = @json($available);</script>
    <script src="{{ asset('front/js/printProvider/earnings.js') }}"></script>
@endpush
