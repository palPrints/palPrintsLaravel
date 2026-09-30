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

    @if (session('success'))
        <section class="printshop-profile-alert" role="status">
            <div class="printshop-profile-alert__message">
                <span class="printshop-profile-alert__icon" aria-hidden="true"><i class="bi bi-check-circle"></i></span>
                <div><h2>تم بنجاح</h2><p>{{ session('success') }}</p></div>
            </div>
        </section>
    @endif

    @unless (auth()->user()->hasApprovedBusinessAccount())
        @php
            $accountStatus = auth()->user()->approvalStatus();
            [$alertTitle, $alertText, $alertAction] = match (true) {
                $accountStatus === 'rejected' => ['تم رفض طلب التوثيق', 'عدّل بيانات ملفك الشخصي وأرسلها للمراجعة مرة أخرى.', 'تعديل البيانات'],
                in_array($accountStatus, ['submitted', 'under_review'], true) => ['حسابك قيد المراجعة', 'ستتمكن من استخدام باقي الصفحات بعد اعتماد الحساب من الإدارة.', 'عرض الملف الشخصي'],
                auth()->user()->hasCompletedRoleProfile() => ['أرسل حسابك للمراجعة', 'ملفك مكتمل. أرسله للإدارة ليتم توثيق الحساب وفتح باقي الصفحات.', 'إرسال للمراجعة'],
                default => ['أكمل ملفك الشخصي', 'أكمل بيانات المطبعة ثم أرسلها للتوثيق حتى تتمكن من استخدام باقي الصفحات.', 'أكمل البيانات'],
            };
        @endphp
        <section class="printshop-profile-alert" role="alert" aria-labelledby="profileAlertTitle">
            <div class="printshop-profile-alert__message">
                <span class="printshop-profile-alert__icon" aria-hidden="true">
                    <i class="bi bi-exclamation-triangle"></i>
                </span>
                <div>
                    <h2 id="profileAlertTitle">{{ $alertTitle }}</h2>
                    <p>{{ session('warning') ?: $alertText }}</p>
                </div>
            </div>
            <a class="printshop-accent-button" href="{{ route('print-provider.profile') }}">
                <span>{{ $alertAction }}</span>
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </a>
        </section>
    @endunless

    @php
        $statCards = [
            ['key' => 'earnings', 'title' => 'الأرباح المستحقة', 'icon' => 'bi-cash-coin', 'tone' => 'is-green', 'currency' => true],
            ['key' => 'completed', 'title' => 'الطلبات المكتملة هذا الشهر', 'icon' => 'bi-check-circle', 'tone' => 'is-blue', 'currency' => false],
            ['key' => 'progress', 'title' => 'الطلبات قيد التنفيذ', 'icon' => 'bi-clock', 'tone' => 'is-amber', 'currency' => false],
            ['key' => 'new', 'title' => 'الطلبات الجديدة', 'icon' => 'bi-bag', 'tone' => 'is-purple', 'currency' => false],
        ];
    @endphp

    <section class="printshop-stats" aria-label="ملخص أداء المطبعة">
        @foreach ($statCards as $card)
            @php
                $stat = $stats[$card['key']];
                $value = $card['currency'] ? number_format($stat['value'], 2) : $stat['value'];
            @endphp
            <article class="printshop-stat-card">
                <span class="printshop-stat-card__icon {{ $card['tone'] }}" aria-hidden="true">
                    <i class="bi {{ $card['icon'] }}"></i>
                </span>
                <div class="printshop-stat-card__content">
                    <h2>{{ $card['title'] }}</h2>
                    <strong @if ($card['currency']) dir="ltr" data-counter-currency="true" @endif data-dashboard-counter="{{ $stat['value'] }}">{{ $value }}{{ $card['currency'] ? ' ₪' : '' }}</strong>
                    @if ($stat['trend'])
                        <p>
                            <span class="printshop-trend {{ $stat['trend'][0] === 'down' ? 'is-down' : '' }}">
                                @if ($stat['trend'][0] !== 'flat')
                                    <i class="bi bi-arrow-{{ $stat['trend'][0] }}" aria-hidden="true"></i>
                                @endif
                                {{ $stat['trend'][1] }}
                            </span> مقارنة بالأسبوع الماضي
                        </p>
                    @else
                        <p>لا يوجد نشاط هذا الأسبوع</p>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <section class="printshop-panel printshop-alerts-panel" aria-labelledby="alertsTitle">
        <header class="printshop-panel__header">
            <h2 id="alertsTitle"><i class="bi bi-bell" aria-hidden="true"></i> التنبيهات</h2>
        </header>

        <div class="printshop-alert-list">
            @forelse ($alerts as $alert)
                <article class="printshop-alert-item">
                    <span class="printshop-alert-item__icon {{ $alert['tone'] }}" aria-hidden="true"><i class="bi {{ $alert['icon'] }}"></i></span>
                    <div class="printshop-alert-item__content">
                        <h3>{{ $alert['title'] }}</h3>
                        <p>{{ $alert['text'] }}</p>
                    </div>
                    <a class="printshop-secondary-button" href="{{ $alert['url'] }}">{{ $alert['action'] }}</a>
                </article>
            @empty
                <article class="printshop-alert-item">
                    <span class="printshop-alert-item__icon is-success" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                    <div class="printshop-alert-item__content">
                        <h3>لا توجد تنبيهات حاليًا</h3>
                        <p>كل شيء يسير على ما يرام.</p>
                    </div>
                </article>
            @endforelse
        </div>
    </section>

    <section class="printshop-panel printshop-orders-panel" aria-labelledby="ordersTitle">
        <header class="printshop-panel__header">
            <h2 id="ordersTitle"><i class="bi bi-box-seam" aria-hidden="true"></i> آخر الطلبات</h2>
            <a href="{{ route('print-provider.requests') }}">عرض الكل <i class="bi bi-arrow-up-left" aria-hidden="true"></i></a>
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
                    @forelse ($recentOrders as $order)
                        <tr data-order-row data-search="#{{ $order['number'] }} {{ $order['number'] }} {{ $order['product'] }} {{ $order['status_label'] }}">
                            <td><strong dir="ltr">#{{ $order['number'] }}</strong></td>
                            <td>{{ $order['product'] }}</td>
                            <td><span class="printshop-status {{ $order['status_class'] }}">{{ $order['status_label'] }}</span></td>
                            <td><time datetime="{{ $order['date']->toDateString() }}">{{ $order['date']->translatedFormat('j F Y') }}</time></td>
                            <td><a class="printshop-view-button" href="{{ route('print-provider.requests') }}"><i class="bi bi-eye" aria-hidden="true"></i> عرض</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <strong>لا توجد طلبات بعد</strong>
                                <span>ستظهر هنا الطلبات الموجهة إلى مطبعتك.</span>
                            </td>
                        </tr>
                    @endforelse
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
