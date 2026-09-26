@extends('designer.layouts.app')

@section('title', 'الأرباح')
@section('body-class', 'earnings-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/designer/css/designerEarnings.css') }}?v={{ filemtime(public_path('front/designer/css/designerEarnings.css')) }}">
@endpush

@php
    $available = (float) ($wallet?->available_balance ?? 0);
    $statusLabels = [
        'pending' => ['معلق', 'statusPending'],
        'available' => ['متاح للسحب', 'statusAvailable'],
        'requested' => ['في طلب السحب', 'statusRequested'],
        'transferring' => ['قيد التحويل', 'statusTransferring'],
        'complete' => ['تم التحويل', 'statusComplete'],
        'rejected' => ['مرفوض', 'statusRejected'],
    ];
@endphp

@section('content')
    <main class="profile-main" id="designerMain">
        @include('designer.partials.flash')

        <div class="designer-breadcrumb">
            <a href="{{ route('designer.dashboard') }}">الرئيسية</a>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span data-i18n="earnings">الأرباح</span>
        </div>

        <header class="earnings-header">
            <div class="earnings-heading-group">
                <span class="designer-heading-icon"><i class="bi bi-coin" aria-hidden="true"></i></span>
                <div class="earnings-heading-copy"><h1 data-i18n="earnings">الأرباح</h1><p data-i18n="earningsIntro">تابع أرباحك واطلب استلامها بسهولة</p></div>
            </div>
            <button type="button" class="profile-button is-primary earnings-withdraw-cta" id="openWithdrawDialog" aria-haspopup="dialog" aria-controls="withdrawDialog" aria-expanded="false"><i class="bi bi-cash-coin" aria-hidden="true"></i><span data-i18n="requestEarnings">طلب استلام الأرباح</span></button>
        </header>

        <section class="earnings-summary" aria-label="ملخص الأرباح" data-i18n-aria="earningsSummary">
            <article class="earnings-stat is-available"><div class="earnings-stat-content is-flex"><span class="earnings-stat-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span><div><h2 data-i18n="availableBalance">الرصيد المتاح للسحب</h2><strong dir="ltr" data-earnings-counter="{{ $available }}">{{ number_format($available, 2) }} ₪</strong></div></div></article>
            <article class="earnings-stat is-total"><div class="earnings-stat-content is-flex"><span class="earnings-stat-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span><div><h2 data-i18n="totalEarnings">إجمالي الأرباح</h2><strong dir="ltr" data-earnings-counter="{{ (float) ($wallet?->total_balance ?? 0) }}">{{ number_format((float) ($wallet?->total_balance ?? 0), 2) }} ₪</strong></div></div></article>
            <article class="earnings-stat is-pending"><div class="earnings-stat-content is-flex"><span class="earnings-stat-icon"><i class="bi bi-clock" aria-hidden="true"></i></span><div><h2 data-i18n="pendingBalance">قيد الاستحقاق</h2><strong dir="ltr" data-earnings-counter="{{ (float) ($wallet?->pending_balance ?? 0) }}">{{ number_format((float) ($wallet?->pending_balance ?? 0), 2) }} ₪</strong></div></div></article>
        </section>

        <section class="earnings-history" aria-labelledby="historyTitle">
            <div class="earnings-history-head">
                <h2 id="historyTitle"><i class="bi bi-calendar3" aria-hidden="true"></i><span data-i18n="recentActivity">حدث مؤخراً</span></h2>
                <button type="button" class="earnings-filter-toggle" id="filterToggle" aria-haspopup="dialog" aria-controls="filterDialog" aria-expanded="false"><i class="bi bi-funnel" aria-hidden="true"></i><span data-i18n="filter">الفلترة</span></button>
            </div>
            <div class="earnings-table-wrap" tabindex="0" aria-label="جدول معاملات الأرباح، قابل للتمرير أفقياً" data-i18n-aria="earningsTableLabel">
                <table class="earnings-table">
                    <thead><tr><th data-i18n="transactionCode">كود العملية</th><th data-i18n="operationType">نوع العملية</th><th data-i18n="designName">اسم التصميم</th><th data-i18n="date">التاريخ</th><th data-i18n="profit">الربح</th><th data-i18n="profitStatus">حالة الربح</th></tr></thead>
                    <tbody id="earningsRows">
                        @foreach ($transactions as $transaction)
                            @php
                                $status = array_key_exists($transaction->status, $statusLabels) ? $transaction->status : 'complete';
                                $date = $transaction->created_at?->format('Y-m-d');
                            @endphp
                            <tr data-date="{{ $date }}" data-amount="{{ (float) $transaction->amount }}" data-status="{{ $status }}" data-product="{{ $transaction->type }}">
                                <td dir="ltr">{{ $transaction->reference_id ?: 'TRX-'.$transaction->id }}</td>
                                <td>{{ $transactionTypes[$transaction->type] ?? $transaction->type }}</td>
                                <td>{{ $transaction->description ?: '—' }}</td>
                                <td dir="ltr">{{ $date }}</td>
                                <td dir="ltr">{{ number_format((float) $transaction->amount, 2) }} ₪</td>
                                <td><span class="status-pill is-{{ $status }}"><i></i><span data-i18n="{{ $statusLabels[$status][1] }}">{{ $statusLabels[$status][0] }}</span></span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="earnings-empty" id="earningsEmpty" @if ($transactions->isNotEmpty()) hidden @endif data-i18n="noMatchingTransactions">
                    {{ $transactions->isEmpty() ? 'لا توجد معاملات حتى الآن.' : 'لا توجد معاملات تطابق عوامل التصفية.' }}
                </p>
            </div>
        </section>
    </main>

    <dialog class="profile-dialog earnings-filter-dialog" id="filterDialog" aria-labelledby="filterDialogTitle">
        <form id="earningsFilters">
            <header class="profile-dialog-header">
                <div class="earnings-filter-dialog-title"><span><i class="bi bi-funnel" aria-hidden="true"></i></span><div><h2 id="filterDialogTitle" data-i18n="filterEarnings">فلترة الأرباح</h2><p data-i18n="filterDescription">حدّد الخيارات المناسبة للوصول إلى المعاملات التي تبحث عنها.</p></div></div>
                <button type="button" class="profile-dialog-close" data-filter-close data-no-press aria-label="إغلاق" data-i18n-aria="close"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </header>
            <div class="profile-dialog-body">
                <div class="earnings-filter-grid">
                    <label class="profile-field"><span data-i18n="fromDate">من تاريخ</span><span class="profile-input-shell"><input type="date" name="dateFrom"></span></label>
                    <label class="profile-field"><span data-i18n="toDate">إلى تاريخ</span><span class="profile-input-shell"><input type="date" name="dateTo"></span></label>
                    <label class="profile-field"><span data-i18n="minimumAmount">الحد الأدنى</span><span class="profile-input-shell"><input type="number" min="0" step="0.01" name="minAmount" placeholder="0.00" dir="ltr"></span></label>
                    <label class="profile-field"><span data-i18n="maximumAmount">الحد الأقصى</span><span class="profile-input-shell"><input type="number" min="0" step="0.01" name="maxAmount" placeholder="100.00" dir="ltr"></span></label>
                    <label class="profile-field"><span data-i18n="profitStatus">حالة الربح</span><span class="profile-input-shell"><select name="status"><option value="" data-i18n="allStatuses">كل الحالات</option>@foreach ($statusLabels as $value => [$label, $key])<option value="{{ $value }}" data-i18n="{{ $key }}">{{ $label }}</option>@endforeach</select></span></label>
                    <label class="profile-field"><span data-i18n="operationType">نوع العملية</span><span class="profile-input-shell"><select name="product"><option value="" data-i18n="allOperations">كل العمليات</option>@foreach ($transactionTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></span></label>
                    <label class="profile-field is-wide"><span data-i18n="designName">اسم التصميم</span><span class="profile-input-shell"><input type="search" name="design" placeholder="ابحث باسم التصميم" data-i18n-placeholder="searchDesign"></span></label>
                </div>
            </div>
            <footer class="profile-dialog-footer earnings-filter-actions"><button type="reset" class="profile-button is-ghost" data-i18n="reset">إعادة تعيين</button><button type="submit" class="profile-button is-primary"><i class="bi bi-funnel" aria-hidden="true"></i><span data-i18n="applyFilter">تطبيق الفلترة</span></button></footer>
        </form>
    </dialog>

    <dialog class="profile-dialog earnings-withdraw-dialog" id="withdrawDialog" aria-labelledby="withdrawTitle">
        <form method="dialog" id="withdrawForm" data-withdraw-url="{{ route('designer.earnings.withdraw') }}" data-available="{{ $available }}">
            <header class="profile-dialog-header"><div class="earnings-withdraw-dialog-title"><span><i class="bi bi-cash-coin" aria-hidden="true"></i></span><div><h2 id="withdrawTitle" data-i18n="requestEarnings">طلب استلام الأرباح</h2><p><span data-i18n="availableBalanceLabel">الرصيد المتاح للسحب:</span> <strong dir="ltr">{{ number_format($available, 2) }} ₪</strong></p></div></div><button type="button" class="profile-dialog-close" data-dialog-close data-no-press aria-label="إغلاق" data-i18n-aria="close"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <div class="profile-dialog-body"><div class="profile-field-grid">
                <label class="profile-field"><span data-i18n="withdrawAmount">المبلغ المراد سحبه</span><span class="profile-input-shell"><i class="bi bi-cash-coin" aria-hidden="true"></i><input id="withdrawAmount" type="number" min="1" max="{{ $available }}" step="0.01" required dir="ltr" placeholder="{{ number_format($available, 2, '.', '') }}"></span><small class="profile-field-error" id="withdrawAmountError" data-i18n="amountError">أدخل مبلغاً بين 1 ₪ و{{ number_format($available, 2) }} ₪.</small></label>
                <label class="profile-field"><span data-i18n="payoutMethod">طريقة الاستلام</span><span class="profile-input-shell"><i class="bi bi-bank" aria-hidden="true"></i><select id="payoutMethod" required><option value="" data-i18n="chooseMethod">اختر الطريقة</option><option value="bank" data-i18n="bankTransfer">تحويل بنكي</option><option value="wallet" data-i18n="electronicWallet">محفظة إلكترونية</option></select></span><small class="profile-field-error" data-i18n="methodError">اختر طريقة الاستلام.</small></label>
                <label class="profile-field is-wide"><span data-i18n="notesOptional">ملاحظات (اختياري)</span><span class="profile-input-shell is-textarea"><textarea id="withdrawNotes" rows="3" maxlength="1000" placeholder="أي تفاصيل تساعدنا في معالجة الطلب" data-i18n-placeholder="notesPlaceholder"></textarea></span></label>
            </div></div>
            <footer class="profile-dialog-footer"><button type="button" class="profile-button is-ghost" data-dialog-close data-i18n="cancel">إلغاء</button><button type="submit" class="profile-button is-primary"><i class="bi bi-check2" aria-hidden="true"></i><span data-i18n="confirmRequest">تأكيد الطلب</span></button></footer>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script src="{{ asset('front/designer/js/designerEarnings.js') }}?v={{ filemtime(public_path('front/designer/js/designerEarnings.js')) }}"></script>
@endpush
