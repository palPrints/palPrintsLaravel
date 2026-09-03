@extends('designer.layouts.app')

@section('title', 'الأرباح')
@section('main-class', 'source-earnings-main')

@push('base-styles')
    <link rel='stylesheet' href='{{ asset('front/designer/source/earnings/css/pages/profile-core.css') }}'>
@endpush

@push('styles')
    <link rel='stylesheet' href='{{ asset('front/designer/source/earnings/css/pages/designerEarnings.css') }}'>
    <link rel='stylesheet' href='{{ asset('front/designer/source/earnings/css/interactions.css') }}'>
@endpush

@section('content')
    <div class='earnings-page'>
        <div class='profile-main' id='earningsMain'>
        <header class="earnings-header">
          <div><h1 data-i18n="earnings">الأرباح</h1><p data-i18n="earningsIntro">تابع أرباحك واطلب استلامها بسهولة</p></div>
          <button type="button" class="profile-button is-primary earnings-withdraw-cta" id="openWithdrawDialog"><i class="bi bi-cash-coin" aria-hidden="true"></i><span data-i18n="requestEarnings">طلب استلام الأرباح</span></button>
        </header>

        <section class="earnings-summary" aria-label="ملخص الأرباح" data-i18n-aria="earningsSummary">
          <article class="earnings-stat is-available" data-state="loading"><div class="earnings-stat-loader" data-state-loading><span class="profile-skeleton is-block"></span><span class="profile-skeleton is-short"></span></div><div class="earnings-stat-content is-flex" data-state-content><span class="earnings-stat-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span><div><h2 data-i18n="availableBalance">الرصيد المتاح للسحب</h2><strong dir="ltr" data-earnings-counter="245">$245.00</strong></div></div></article>
          <article class="earnings-stat is-total" data-state="loading"><div class="earnings-stat-loader" data-state-loading><span class="profile-skeleton is-block"></span><span class="profile-skeleton is-short"></span></div><div class="earnings-stat-content is-flex" data-state-content><span class="earnings-stat-icon"><i class="bi bi-currency-dollar" aria-hidden="true"></i></span><div><h2 data-i18n="totalEarnings">إجمالي الأرباح</h2><strong dir="ltr" data-earnings-counter="1420">$1,420.00</strong></div></div></article>
          <article class="earnings-stat is-pending" data-state="loading"><div class="earnings-stat-loader" data-state-loading><span class="profile-skeleton is-block"></span><span class="profile-skeleton is-short"></span></div><div class="earnings-stat-content is-flex" data-state-content><span class="earnings-stat-icon"><i class="bi bi-clock" aria-hidden="true"></i></span><div><h2 data-i18n="pendingBalance">قيد الاستحقاق</h2><strong dir="ltr" data-earnings-counter="180">$180.00</strong></div></div></article>
        </section>

        <section class="earnings-history pal-hover-lift" aria-labelledby="historyTitle" data-state="loading">
          <div class="earnings-history-head">
            <h2 id="historyTitle"><i class="bi bi-calendar3" aria-hidden="true"></i><span data-i18n="recentActivity">حدث مؤخراً</span></h2>
            <button type="button" class="earnings-filter-toggle" id="filterToggle" aria-haspopup="dialog" aria-controls="filterDialog"><i class="bi bi-funnel" aria-hidden="true"></i><span data-i18n="filter">الفلترة</span></button>
          </div>
          <div class="earnings-history-loader" data-state-loading><span class="profile-skeleton is-line"></span><span class="profile-skeleton is-block"></span><span class="profile-skeleton is-block"></span><span class="profile-skeleton is-block"></span></div>
          <div class="earnings-table-wrap" data-state-content tabindex="0" aria-label="جدول معاملات الأرباح، قابل للتمرير أفقياً" data-i18n-aria="earningsTableLabel">
            <table class="earnings-table">
              <thead><tr><th data-i18n="transactionCode">كود العملية</th><th data-i18n="product">المنتج</th><th data-i18n="designName">اسم التصميم</th><th data-i18n="date">التاريخ</th><th data-i18n="profit">الربح</th><th data-i18n="profitStatus">حالة الربح</th></tr></thead>
              <tbody id="earningsRows">
                <tr data-date="2024-05-20" data-amount="15" data-status="pending" data-product="tshirt" data-design-key="designNature"><td dir="ltr">TRX-2024-00521</td><td data-i18n="productTshirt">تيشيرت</td><td data-i18n="designNature">معاصرة الطبيعة</td><td dir="ltr">2024-05-20</td><td dir="ltr">$15.00</td><td><span class="status-pill is-pending"><i></i><span data-i18n="statusPending">معلق</span></span></td></tr>
                <tr data-date="2024-05-19" data-amount="25" data-status="available" data-product="mug" data-design-key="designCalligraphy"><td dir="ltr">TRX-2024-00520</td><td data-i18n="productMug">كوب</td><td data-i18n="designCalligraphy">خط عربي</td><td dir="ltr">2024-05-19</td><td dir="ltr">$25.00</td><td><span class="status-pill is-available"><i></i><span data-i18n="statusAvailable">متاح للسحب</span></span></td></tr>
                <tr data-date="2024-05-18" data-amount="35" data-status="requested" data-product="hoodie" data-design-key="designSimple"><td dir="ltr">TRX-2024-00519</td><td data-i18n="productHoodie">هودي</td><td data-i18n="designSimple">تصميم بسيط</td><td dir="ltr">2024-05-18</td><td dir="ltr">$35.00</td><td><span class="status-pill is-requested"><i></i><span data-i18n="statusRequested">في طلب السحب</span></span></td></tr>
                <tr data-date="2024-05-17" data-amount="40" data-status="transferring" data-product="shirt" data-design-key="designLion"><td dir="ltr">TRX-2024-00518</td><td data-i18n="productShirt">قميص</td><td data-i18n="designLion">الأسد الملك</td><td dir="ltr">2024-05-17</td><td dir="ltr">$40.00</td><td><span class="status-pill is-transferring"><i></i><span data-i18n="statusTransferring">قيد التحويل</span></span></td></tr>
                <tr data-date="2024-05-15" data-amount="50" data-status="complete" data-product="bag" data-design-key="designMinimal"><td dir="ltr">TRX-2024-00517</td><td data-i18n="productBag">حقيبة</td><td dir="ltr" data-i18n="designMinimal">minimal vibes</td><td dir="ltr">2024-05-15</td><td dir="ltr">$50.00</td><td><span class="status-pill is-complete"><i></i><span data-i18n="statusComplete">تم التحويل</span></span></td></tr>
                <tr data-date="2024-05-14" data-amount="10" data-status="rejected" data-product="tshirt" data-design-key="designBlocked"><td dir="ltr">TRX-2024-00516</td><td data-i18n="productTshirt">تيشيرت</td><td data-i18n="designBlocked">تصميم محظور</td><td dir="ltr">2024-05-14</td><td dir="ltr">$10.00</td><td><span class="status-pill is-rejected"><i></i><span data-i18n="statusRejected">مرفوض</span></span></td></tr>
              </tbody>
            </table>
            <p class="earnings-empty" id="earningsEmpty" hidden data-i18n="noMatchingTransactions">لا توجد معاملات تطابق عوامل التصفية.</p>
          </div>
        </section>

        </div>
        <dialog class="profile-dialog earnings-filter-dialog" id="filterDialog" aria-labelledby="filterDialogTitle">
    <form id="earningsFilters">
      <header class="profile-dialog-header">
        <div><h2 id="filterDialogTitle" data-i18n="filterEarnings">فلترة الأرباح</h2><p data-i18n="filterDescription">حدّد الخيارات المناسبة للوصول إلى المعاملات التي تبحث عنها.</p></div>
        <button type="button" class="profile-dialog-close" data-filter-close aria-label="إغلاق" data-i18n-aria="close"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
      </header>
      <div class="profile-dialog-body">
        <div class="earnings-filter-grid">
          <label class="profile-field"><span data-i18n="fromDate">من تاريخ</span><span class="profile-input-shell"><input type="date" name="dateFrom"></span></label>
          <label class="profile-field"><span data-i18n="toDate">إلى تاريخ</span><span class="profile-input-shell"><input type="date" name="dateTo"></span></label>
          <label class="profile-field"><span data-i18n="minimumAmount">الحد الأدنى</span><span class="profile-input-shell"><input type="number" min="0" step="0.01" name="minAmount" placeholder="0.00" dir="ltr"></span></label>
          <label class="profile-field"><span data-i18n="maximumAmount">الحد الأقصى</span><span class="profile-input-shell"><input type="number" min="0" step="0.01" name="maxAmount" placeholder="100.00" dir="ltr"></span></label>
          <label class="profile-field"><span data-i18n="profitStatus">حالة الربح</span><span class="profile-input-shell"><select name="status"><option value="" data-i18n="allStatuses">كل الحالات</option><option value="pending" data-i18n="statusPending">معلق</option><option value="available" data-i18n="statusAvailable">متاح للسحب</option><option value="requested" data-i18n="statusRequested">في طلب السحب</option><option value="transferring" data-i18n="statusTransferring">قيد التحويل</option><option value="complete" data-i18n="statusComplete">تم التحويل</option><option value="rejected" data-i18n="statusRejected">مرفوض</option></select></span></label>
          <label class="profile-field"><span data-i18n="product">المنتج</span><span class="profile-input-shell"><select name="product"><option value="" data-i18n="allProducts">كل المنتجات</option><option value="tshirt" data-i18n="productTshirt">تيشيرت</option><option value="mug" data-i18n="productMug">كوب</option><option value="hoodie" data-i18n="productHoodie">هودي</option><option value="shirt" data-i18n="productShirt">قميص</option><option value="bag" data-i18n="productBag">حقيبة</option></select></span></label>
          <label class="profile-field is-wide"><span data-i18n="designName">اسم التصميم</span><span class="profile-input-shell"><input type="search" name="design" placeholder="ابحث باسم التصميم" data-i18n-placeholder="searchDesign"></span></label>
        </div>
      </div>
      <footer class="profile-dialog-footer earnings-filter-actions"><button type="reset" class="profile-button is-ghost" data-i18n="reset">إعادة تعيين</button><button type="submit" class="profile-button is-primary"><i class="bi bi-funnel" aria-hidden="true"></i><span data-i18n="applyFilter">تطبيق الفلترة</span></button></footer>
    </form>
  </dialog>

  <dialog class="profile-dialog" id="withdrawDialog" aria-labelledby="withdrawTitle">
    <form method="dialog" id="withdrawForm">
      <header class="profile-dialog-header"><div><h2 id="withdrawTitle" data-i18n="requestEarnings">طلب استلام الأرباح</h2><p><span data-i18n="availableBalanceLabel">الرصيد المتاح للسحب:</span> <strong dir="ltr">$245.00</strong></p></div><button type="button" class="profile-dialog-close" data-dialog-close aria-label="إغلاق" data-i18n-aria="close"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
      <div class="profile-dialog-body"><div class="profile-field-grid">
        <label class="profile-field"><span data-i18n="withdrawAmount">المبلغ المراد سحبه</span><span class="profile-input-shell"><i class="bi bi-currency-dollar" aria-hidden="true"></i><input id="withdrawAmount" type="number" min="1" max="245" step="0.01" required dir="ltr" placeholder="245.00"></span><small class="profile-field-error" data-i18n="amountError">أدخل مبلغاً بين $1 و$245.</small></label>
        <label class="profile-field"><span data-i18n="payoutMethod">طريقة الاستلام</span><span class="profile-input-shell"><i class="bi bi-bank" aria-hidden="true"></i><select id="payoutMethod" required><option value="" data-i18n="chooseMethod">اختر الطريقة</option><option value="bank" data-i18n="bankTransfer">تحويل بنكي</option><option value="wallet" data-i18n="electronicWallet">محفظة إلكترونية</option></select></span><small class="profile-field-error" data-i18n="methodError">اختر طريقة الاستلام.</small></label>
        <label class="profile-field is-wide"><span data-i18n="notesOptional">ملاحظات (اختياري)</span><span class="profile-input-shell is-textarea"><textarea rows="3" placeholder="أي تفاصيل تساعدنا في معالجة الطلب" data-i18n-placeholder="notesPlaceholder"></textarea></span></label>
      </div></div>
      <footer class="profile-dialog-footer"><button type="button" class="profile-button is-ghost" data-dialog-close data-i18n="cancel">إلغاء</button><button type="submit" class="profile-button is-primary"><i class="bi bi-check2" aria-hidden="true"></i><span data-i18n="confirmRequest">تأكيد الطلب</span></button></footer>
    </form>
  </dialog>
  <div class="profile-toast" id="profileToast" role="status" aria-live="polite" aria-atomic="true"><span class="profile-toast-icon" aria-hidden="true"><i class="bi bi-check2"></i></span><span id="profileToastMessage"></span></div>
    </div>
@endsection

@push('scripts')
    <script src='{{ asset('front/designer/source/earnings/js/earnings-core-adapter.js') }}'></script>
    <script src='{{ asset('front/designer/source/earnings/js/pages/designerEarnings.js') }}'></script>
    <script src='{{ asset('front/designer/source/earnings/js/interactions.js') }}'></script>
@endpush
