@extends('printProvider.layouts.app')

@section('title', 'لوحة تحكم المطبعة')

@section('content')
    <section class="printshop-page-heading" aria-labelledby="dashboardTitle">
        <div>
            <h1 id="dashboardTitle">لوحة التحكم</h1>
            <p><time datetime="{{ now()->toDateString() }}">{{ now()->translatedFormat('l، j F Y') }}</time></p>
        </div>
        <a class="printshop-primary-button" href="{{ route('print-provider.services', ['openCatalog' => 1]) }}">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>إضافة منتج جديد</span>
        </a>
    </section>

    <section class="printshop-profile-alert" aria-labelledby="profileAlertTitle">
        <div class="printshop-profile-alert__message">
            <span class="printshop-profile-alert__icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle"></i>
            </span>
            <div>
                <h2 id="profileAlertTitle">أكمل ملفك الشخصي</h2>
                <p>هناك بعض البيانات الناقصة في ملفك الشخصي. أكملها الآن لعرض مطبعتك للعملاء وزيادة فرص الطلبات.</p>
            </div>
        </div>
        <a class="printshop-accent-button" href="#">
            <span>أكمل البيانات</span>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
        </a>
    </section>

    <section class="printshop-stats" aria-label="ملخص أداء المطبعة">
        <article class="printshop-stat-card">
            <span class="printshop-stat-card__icon is-green" aria-hidden="true">
                <i class="bi bi-currency-dollar"></i>
            </span>
            <div class="printshop-stat-card__content">
                <h2>الأرباح المستحقة</h2>
                <strong dir="ltr" data-dashboard-counter="1240" data-counter-currency="true">$1,240.00</strong>
                <p><span class="printshop-trend"><i class="bi bi-arrow-up" aria-hidden="true"></i> 12%</span> مقارنة بالأسبوع الماضي</p>
            </div>
        </article>

        <article class="printshop-stat-card">
            <span class="printshop-stat-card__icon is-blue" aria-hidden="true">
                <i class="bi bi-check-circle"></i>
            </span>
            <div class="printshop-stat-card__content">
                <h2>الطلبات المكتملة هذا الشهر</h2>
                <strong data-dashboard-counter="78">78</strong>
                <p><span class="printshop-trend"><i class="bi bi-arrow-up" aria-hidden="true"></i> 18%</span> مقارنة بالأسبوع الماضي</p>
            </div>
        </article>

        <article class="printshop-stat-card">
            <span class="printshop-stat-card__icon is-amber" aria-hidden="true">
                <i class="bi bi-clock"></i>
            </span>
            <div class="printshop-stat-card__content">
                <h2>الطلبات قيد التنفيذ</h2>
                <strong data-dashboard-counter="12">12</strong>
                <p><span class="printshop-trend"><i class="bi bi-arrow-up" aria-hidden="true"></i> 20%</span> مقارنة بالأسبوع الماضي</p>
            </div>
        </article>

        <article class="printshop-stat-card">
            <span class="printshop-stat-card__icon is-purple" aria-hidden="true">
                <i class="bi bi-bag"></i>
            </span>
            <div class="printshop-stat-card__content">
                <h2>الطلبات الجديدة</h2>
                <strong data-dashboard-counter="8">8</strong>
                <p><span class="printshop-trend"><i class="bi bi-arrow-up" aria-hidden="true"></i> 5%</span> مقارنة بالأسبوع الماضي</p>
            </div>
        </article>
    </section>

    <section class="printshop-panel printshop-alerts-panel" aria-labelledby="alertsTitle">
        <header class="printshop-panel__header">
            <h2 id="alertsTitle"><i class="bi bi-bell" aria-hidden="true"></i> التنبيهات</h2>
            <a href="#">عرض الكل <i class="bi bi-arrow-up-left" aria-hidden="true"></i></a>
        </header>

        <div class="printshop-alert-list">
            <article class="printshop-alert-item">
                <span class="printshop-alert-item__icon is-warning" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span>
                <div class="printshop-alert-item__content">
                    <h3>المنتج "أكواب مخصصة" معطل من 3 أيام</h3>
                    <p>فعّل المنتج ليتاح للعملاء مجددًا.</p>
                </div>
                <a class="printshop-secondary-button" href="#">تفعيل الآن</a>
            </article>

            <article class="printshop-alert-item">
                <span class="printshop-alert-item__icon is-danger" aria-hidden="true"><i class="bi bi-truck"></i></span>
                <div class="printshop-alert-item__content">
                    <h3>الطلب #10352 جاهز للشحن من يومين</h3>
                    <p>بانتظار الاستلام من شركة التوصيل.</p>
                </div>
                <a class="printshop-secondary-button" href="#">عرض الطلب</a>
            </article>

            <article class="printshop-alert-item">
                <span class="printshop-alert-item__icon is-success" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                <div class="printshop-alert-item__content">
                    <h3>رصيدك القابل للسحب وصل $1,240.00</h3>
                    <p>يمكنك الآن طلب سحب الأرباح.</p>
                </div>
                <a class="printshop-secondary-button" href="#">اطلب سحب</a>
            </article>
        </div>
    </section>

    <section class="printshop-panel printshop-orders-panel" aria-labelledby="ordersTitle">
        <header class="printshop-panel__header">
            <h2 id="ordersTitle"><i class="bi bi-box-seam" aria-hidden="true"></i> آخر الطلبات</h2>
            <a href="#">عرض الكل <i class="bi bi-arrow-up-left" aria-hidden="true"></i></a>
        </header>

        <div class="printshop-table-wrapper" tabindex="0" aria-label="جدول آخر الطلبات، قابل للتمرير أفقيًا">
            <table class="printshop-orders-table">
                <thead>
                    <tr>
                        <th scope="col">رقم الطلب</th>
                        <th scope="col">اسم المنتج</th>
                        <th scope="col">الحالة</th>
                        <th scope="col">التاريخ</th>
                        <th scope="col">إجراء</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <tr data-order-row data-search="#10358 10358 تيشيرت مطبوع جديد 12 سبتمبر 2026">
                        <td><strong dir="ltr">#10358</strong></td>
                        <td>تيشيرت مطبوع</td>
                        <td><span class="printshop-status is-new">جديد</span></td>
                        <td><time datetime="2026-09-12">12 سبتمبر 2026</time></td>
                        <td><a class="printshop-view-button" href="#"><i class="bi bi-eye" aria-hidden="true"></i> عرض</a></td>
                    </tr>
                    <tr data-order-row data-search="#10357 10357 أكواب مخصصة قيد التنفيذ 11 سبتمبر 2026">
                        <td><strong dir="ltr">#10357</strong></td>
                        <td>أكواب مخصصة</td>
                        <td><span class="printshop-status is-progress">قيد التنفيذ</span></td>
                        <td><time datetime="2026-09-11">11 سبتمبر 2026</time></td>
                        <td><a class="printshop-view-button" href="#"><i class="bi bi-eye" aria-hidden="true"></i> عرض</a></td>
                    </tr>
                    <tr data-order-row data-search="#10356 10356 ستيكرات مكتمل 10 سبتمبر 2026">
                        <td><strong dir="ltr">#10356</strong></td>
                        <td>ستيكرات</td>
                        <td><span class="printshop-status is-complete">مكتمل</span></td>
                        <td><time datetime="2026-09-10">10 سبتمبر 2026</time></td>
                        <td><a class="printshop-view-button" href="#"><i class="bi bi-eye" aria-hidden="true"></i> عرض</a></td>
                    </tr>
                    <tr data-order-row data-search="#10355 10355 طباعة ورق قيد التنفيذ 9 سبتمبر 2026">
                        <td><strong dir="ltr">#10355</strong></td>
                        <td>طباعة ورق</td>
                        <td><span class="printshop-status is-progress">قيد التنفيذ</span></td>
                        <td><time datetime="2026-09-09">9 سبتمبر 2026</time></td>
                        <td><a class="printshop-view-button" href="#"><i class="bi bi-eye" aria-hidden="true"></i> عرض</a></td>
                    </tr>
                    <tr data-order-row data-search="#10354 10354 هودي مطبوع مكتمل 8 سبتمبر 2026">
                        <td><strong dir="ltr">#10354</strong></td>
                        <td>هودي مطبوع</td>
                        <td><span class="printshop-status is-complete">مكتمل</span></td>
                        <td><time datetime="2026-09-08">8 سبتمبر 2026</time></td>
                        <td><a class="printshop-view-button" href="#"><i class="bi bi-eye" aria-hidden="true"></i> عرض</a></td>
                    </tr>
                    <tr class="printshop-empty-search" id="emptySearchRow" hidden>
                        <td colspan="5">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <strong>لا توجد طلبات مطابقة</strong>
                            <span>جرّب البحث برقم طلب أو اسم منتج آخر.</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection
