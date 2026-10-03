@extends('admin.layouts.app')

@section('title', 'إدارة المستخدمين')
@section('main-id', 'adminUsersMain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminReportsStatistics.css').'?v='.filemtime(public_path('front/css/admin/adminReportsStatistics.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminUsersManagement.css').'?v='.filemtime(public_path('front/css/admin/adminUsersManagement.css')) }}">
@endpush

@section('content')
<main class="admin-main admin-users-main" id="adminUsersMain">
    <section class="users-management" aria-labelledby="usersPageTitle">
        @include('admin.partials.breadcrumb', ['label' => 'إدارة المستخدمين'])
        <h1 id="usersPageTitle">العملاء</h1>

        <div class="users-toolbar">
            <div class="users-filters" id="usersFilters" aria-label="تصفية المستخدمين"></div>
        </div>

        <section class="users-panel theme-customers" id="usersPanel" aria-labelledby="usersListTitle">
            <header class="users-panel-header">
                <h2 id="usersListTitle"><i class="bi bi-person" aria-hidden="true"></i><span>العملاء</span></h2>
                <span class="users-count" id="usersCount">0</span>
            </header>
            <div class="users-list" id="usersList"></div>
            <div class="users-empty" id="usersEmpty" hidden><i class="bi bi-people" aria-hidden="true"></i><strong>لا توجد نتائج مطابقة</strong></div>
        </section>
    </section>
</main>

<dialog class="customer-details-dialog" id="customerDetailsDialog" aria-labelledby="customerDialogTitle">
    <header class="customer-dialog-header">
        <h2 id="customerDialogTitle">تفاصيل المستخدم</h2>
        <button class="customer-dialog-close" id="closeCustomerDialog" type="button" aria-label="إغلاق"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </header>
    <section class="customer-dialog-profile">
        <span class="customer-dialog-avatar" id="customerDialogAvatar">م</span>
        <div><h3 id="customerDialogName">محمد الخطيب</h3><p><span class="customer-type-badge">العملاء</span><span class="user-status is-active" id="customerDialogStatus">نشط</span></p></div>
    </section>
    <div class="customer-dialog-tabs" role="tablist" aria-label="تفاصيل العميل">
        <button class="active" id="customerInfoTab" type="button" role="tab" aria-selected="true" data-customer-tab="info">المعلومات</button>
        <button id="customerOrdersTab" type="button" role="tab" aria-selected="false" data-customer-tab="orders">الطلبات</button>
    </div>
    <div class="customer-dialog-body">
        <dl class="customer-info-grid" id="customerInfoPanel" role="tabpanel">
            <dt>البريد الإلكتروني</dt><dd id="customerDialogEmail" dir="ltr"></dd>
            <dt>رقم الهاتف</dt><dd id="customerDialogPhone" dir="ltr"></dd>
            <dt>المدينة</dt><dd id="customerDialogCity"></dd>
            <dt>تاريخ التسجيل</dt><dd id="customerDialogRegistered"></dd>
            <dt>عدد الطلبات</dt><dd id="customerDialogOrders"></dd>
            <dt>إجمالي الإنفاق</dt><dd id="customerDialogSpend"></dd>
            <dt>آخر طلب</dt><dd id="customerDialogLastOrder"></dd>
        </dl>
        <div class="customer-orders-panel" id="customerOrdersPanel" role="tabpanel" hidden>
            <i class="bi bi-cart3" aria-hidden="true"></i><strong id="customerOrdersSummary"></strong><span>إجمالي طلبات هذا العميل</span>
        </div>
    </div>
    <footer class="customer-dialog-footer">
        <button class="customer-disable-button" id="customerDisableButton" type="button">تعطيل الحساب</button>
    </footer>
</dialog>

<dialog class="customer-details-dialog reject-reason-dialog" id="rejectReasonDialog" aria-labelledby="rejectReasonTitle">
    <header class="customer-dialog-header">
        <h2 id="rejectReasonTitle">سبب رفض الطلب</h2>
        <button class="customer-dialog-close" id="closeRejectReasonDialog" type="button" aria-label="إغلاق"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </header>
    <div class="customer-dialog-body">
        <p class="reject-reason-hint">يرجى كتابة سبب رفض الطلب، سيتم إرساله لصاحب الحساب.</p>
        <textarea class="reject-reason-input" id="rejectReasonInput" rows="4" placeholder="اكتب سبب الرفض هنا..." required></textarea>
        <span class="reject-reason-error" id="rejectReasonError" hidden>سبب الرفض مطلوب.</span>
    </div>
    <footer class="customer-dialog-footer">
        <button class="designer-reject-button" id="confirmRejectReason" type="button">تأكيد الرفض</button>
    </footer>
</dialog>
@endsection

@push('scripts')
    <script id="usersData" type="application/json">@json($usersData)</script>
    <script src="{{ asset('front/js/admin/adminUsersManagement.js').'?v='.filemtime(public_path('front/js/admin/adminUsersManagement.js')) }}"></script>
@endpush
