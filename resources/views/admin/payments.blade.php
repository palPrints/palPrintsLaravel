@extends('admin.layouts.app')

@section('title', 'المدفوعات والأرباح')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminEarningsPayments.css').'?v='.filemtime(public_path('front/css/admin/adminEarningsPayments.css')) }}">
@endpush

@php
    $money = fn (float $value, int $decimals = 0): string => number_format($value, $decimals, '.', ',');
    $letter = fn (?string $name): string => mb_substr(trim((string) $name), 0, 1) ?: '؟';
@endphp

@section('content')
<main id="adminMain">
    <section class="payments-overview" aria-labelledby="paymentsTitle">
        @include('admin.partials.breadcrumb', ['label' => 'المدفوعات والأرباح'])

        <header class="page-heading">
            <span class="heading-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
            <div>
                <h1 id="paymentsTitle">المدفوعات والأرباح</h1>
                <p>تابع المدفوعات والأرباح وراقب طلبات السحب من خلال هذه الصفحة.</p>
            </div>
        </header>

        <div class="finance-summary" aria-label="ملخص المدفوعات والأرباح">
            <article class="finance-card revenue-card">
                <div class="finance-card-copy">
                    <h2>إجمالي المعاملات المالية</h2>
                    <strong><bdi data-counter="{{ $summary['total'] }}" data-counter-decimals="{{ floor($summary['total']) == $summary['total'] ? 0 : 2 }}">{{ $money($summary['total'], 2) }}</bdi> <span>₪</span></strong>
                </div>
                <span class="finance-icon"><i class="bi bi-cash-coin"></i></span>
            </article>

            <article class="finance-card commission-card">
                <div class="finance-card-copy">
                    <h2>عمولة المنصة</h2>
                    <strong><bdi data-counter="{{ $summary['commission'] }}" data-counter-decimals="{{ floor($summary['commission']) == $summary['commission'] ? 0 : 2 }}">{{ $money($summary['commission'], 2) }}</bdi> <span>₪</span></strong>
                </div>
                <span class="finance-icon"><i class="bi bi-wallet2"></i></span>
            </article>

            <article class="finance-card designers-card">
                <div class="finance-card-copy">
                    <h2>طلبات السحب المعلقة</h2>
                    <strong><bdi data-counter="{{ $summary['pendingWithdrawals'] }}">{{ $summary['pendingWithdrawals'] }}</bdi> <span>طلبًا</span></strong>
                </div>
                <span class="finance-icon"><i class="bi bi-file-earmark-text"></i></span>
            </article>

            <article class="finance-card printers-card">
                <div class="finance-card-copy">
                    <h2>إجمالي المبالغ المصروفة</h2>
                    <strong><bdi data-counter="{{ $summary['paidOut'] }}" data-counter-decimals="{{ floor($summary['paidOut']) == $summary['paidOut'] ? 0 : 2 }}">{{ $money($summary['paidOut'], 2) }}</bdi> <span>₪</span></strong>
                </div>
                <span class="finance-icon"><i class="bi bi-send-check"></i></span>
            </article>
        </div>

        <div class="finance-tabs" role="tablist" aria-label="أقسام المدفوعات والأرباح">
            <button id="transactionsTab" type="button" role="tab" aria-selected="false" aria-controls="transactionsPanel" tabindex="-1" data-finance-filter="transactions">سجل المعاملات المالية</button>
            <button id="withdrawalsTab" class="active" type="button" role="tab" aria-selected="true" aria-controls="withdrawalsPanel" tabindex="0" data-finance-filter="withdrawals">طلبات السحب</button>
        </div>

        <section class="earnings-table-card finance-panel" id="withdrawalsPanel" role="tabpanel" aria-labelledby="withdrawalsTab" data-finance-panel="withdrawals">
            <div class="earnings-table-wrap">
                <table class="earnings-table">
                    <thead>
                        <tr>
                            <th scope="col">التفاصيل</th>
                            <th scope="col">الحالة</th>
                            <th scope="col">طريقة السحب</th>
                            <th scope="col">تاريخ الطلب</th>
                            <th scope="col">المبلغ</th>
                            <th scope="col">نوع الحساب</th>
                            <th scope="col">صاحب الطلب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($withdrawals as $withdrawal)
                            <tr data-review-url="{{ $withdrawal['reviewUrl'] }}">
                                <td><button class="details-button" type="button" data-detail-kind="withdrawal" aria-label="عرض تفاصيل طلب {{ $withdrawal['name'] }}">عرض التفاصيل</button></td>
                                <td><span class="payment-status {{ $withdrawal['stateClass'] }}">{{ $withdrawal['stateLabel'] }}</span></td>
                                <td><span class="payment-method"><i class="bi {{ $withdrawal['icon'] }}"></i> {{ $withdrawal['method'] }}</span></td>
                                <td><time datetime="{{ $withdrawal['iso'] }}">{{ $withdrawal['date'] }}</time></td>
                                <td class="money {{ $withdrawal['pending'] ? 'due' : 'net' }}"><bdi>{{ $money($withdrawal['amount'], 2) }} ₪</bdi></td>
                                <td><span class="account-type {{ $withdrawal['role'] === 'print_provider' ? 'printer-type' : 'designer-type' }}">{{ $withdrawal['role'] === 'print_provider' ? 'مطبعة' : 'مصمم' }}</span></td>
                                <td><div class="designer-cell"><span class="designer-avatar">{{ $letter($withdrawal['name']) }}</span><span><b>{{ $withdrawal['name'] }}</b><small>{{ $withdrawal['email'] }}</small></span></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="finance-empty">
                                <div class="finance-empty-row">
                                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                    <p>لا توجد طلبات سحب حاليًا.</p>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="earnings-table-card finance-panel" id="transactionsPanel" role="tabpanel" aria-labelledby="transactionsTab" data-finance-panel="transactions" hidden>
            <div class="earnings-table-wrap">
                <table class="earnings-table transactions-table">
                    <thead>
                        <tr>
                            <th scope="col">الحالة</th>
                            <th scope="col">المبلغ</th>
                            <th scope="col">المستفيد</th>
                            <th scope="col">نوع المعاملة</th>
                            <th scope="col">التاريخ</th>
                            <th scope="col">رقم المعاملة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td><span class="payment-status {{ $transaction['stateClass'] }}">{{ $transaction['stateLabel'] }}</span></td>
                                <td class="money {{ $transaction['due'] ? 'due' : 'net' }}"><bdi>{{ $money($transaction['amount'], 2) }} ₪</bdi></td>
                                <td>{{ $transaction['name'] }}</td>
                                <td><span class="transaction-type {{ $transaction['typeClass'] }}">{{ $transaction['type'] }}</span></td>
                                <td><time datetime="{{ $transaction['iso'] }}">{{ $transaction['date'] }}</time></td>
                                <td class="transaction-id"><bdi>{{ $transaction['reference'] }}</bdi></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="finance-empty">
                                <div class="finance-empty-row">
                                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                    <p>لا توجد معاملات مالية بعد.</p>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>

<dialog class="details-dialog" id="detailsDialog" aria-labelledby="detailsDialogTitle">
    <div class="details-dialog-content">
        <header>
            <div>
                <span class="dialog-heading-icon"><i class="bi bi-receipt"></i></span>
                <div><h2 id="detailsDialogTitle">تفاصيل العملية</h2><p id="detailsDialogSubtitle"></p></div>
            </div>
            <button class="dialog-close" type="button" data-dialog-close aria-label="إغلاق النافذة"><i class="bi bi-x-lg"></i></button>
        </header>
        <dl class="details-list" id="detailsList"></dl>
        <footer id="detailsDialogFooter" hidden>
            <div class="dialog-review-actions" id="dialogReviewActions" hidden>
                <button class="approve-button" id="dialogApprove" type="button">اعتماد الطلب</button>
                <button class="reject-button" id="dialogReject" type="button">رفض الطلب</button>
            </div>
        </footer>
    </div>
</dialog>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/admin/adminEarningsPayments.js').'?v='.filemtime(public_path('front/js/admin/adminEarningsPayments.js')) }}"></script>
@endpush
