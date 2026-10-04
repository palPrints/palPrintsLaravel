@extends('admin.layouts.app')

@section('title', 'إشعارات الدفع')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/adminPaymentNotices.css').'?v='.filemtime(public_path('front/css/admin/adminPaymentNotices.css')) }}">
@endpush

@section('content')
<main id="adminMain">
    <section class="notices-page" aria-labelledby="noticesTitle">
        @include('admin.partials.breadcrumb', ['label' => 'إشعارات الدفع'])
        <h1 id="noticesTitle">إشعارات الدفع</h1>
        <p class="notices-lead">إشعارات التحويل البنكي والمحافظ التي يرفعها العملاء. لا يصل أي طلب إلى المطبعة إلا بعد موافقتك واختيارك للمطبعة.</p>

        @if (session('notice_status'))
            <p class="notices-flash is-success" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i> {{ session('notice_status') }}</p>
        @endif
        @if (session('notice_error'))
            <p class="notices-flash is-error" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> {{ session('notice_error') }}</p>
        @endif
        @if ($errors->any())
            <p class="notices-flash is-error" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> {{ $errors->first() }}</p>
        @endif

        <h2 class="notices-heading">بانتظار المراجعة <span>{{ $pending->count() }}</span></h2>

        @forelse ($pending as $notice)
            <article class="notice-card">
                <header class="notice-head">
                    <div>
                        <strong>طلب <bdi>{{ $notice['orderNumber'] }}</bdi></strong>
                        <small>{{ $notice['date'] }}</small>
                    </div>
                    <bdi class="notice-amount">{{ number_format($notice['amount'], 2) }} ₪</bdi>
                </header>

                <div class="notice-body">
                    <dl class="notice-details">
                        <div><dt>العميل</dt><dd>{{ $notice['customer'] }}</dd></div>
                        <div><dt>الهاتف</dt><dd><bdi>{{ $notice['phone'] ?: '—' }}</bdi></dd></div>
                        <div><dt>طريقة الدفع</dt><dd><i class="bi {{ $notice['icon'] }}" aria-hidden="true"></i> {{ $notice['method'] }}</dd></div>
                        <div><dt>ملف الإشعار</dt><dd><bdi>{{ $notice['receiptName'] ?: '—' }}</bdi></dd></div>
                        <div><dt>المنتجات</dt><dd>{{ implode('، ', $notice['items']) ?: '—' }}</dd></div>
                        @if ($notice['note'])
                            <div class="is-wide"><dt>ملاحظة العميل</dt><dd>{{ $notice['note'] }}</dd></div>
                        @endif
                    </dl>

                    <div class="notice-receipt">
                        @if ($notice['receiptUrl'] && $notice['receiptIsImage'])
                            <a href="{{ $notice['receiptUrl'] }}" target="_blank" rel="noopener"><img src="{{ $notice['receiptUrl'] }}" alt="إشعار الدفع للطلب {{ $notice['orderNumber'] }}" loading="lazy"></a>
                        @elseif ($notice['receiptUrl'])
                            <a class="notice-receipt-file" href="{{ $notice['receiptUrl'] }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> فتح ملف الإشعار</a>
                        @else
                            <p class="notice-receipt-empty"><i class="bi bi-image" aria-hidden="true"></i> لم يرفع العميل صورة إشعار</p>
                        @endif
                    </div>
                </div>

                <div class="notice-actions">
                    <form method="POST" action="{{ $notice['approveUrl'] }}" class="notice-form">
                        @csrf
                        <label>
                            المطبعة التي سيُوجَّه إليها الطلب
                            <select name="branch_id" required @disabled(empty($notice['shops']))>
                                @if (empty($notice['shops']))
                                    <option value="">لا توجد مطبعة تقدّم كل منتجات الطلب</option>
                                @else
                                    <option value="">اختر المطبعة</option>
                                    @foreach ($notice['shops'] as $shop)
                                        <option value="{{ $shop['id'] }}" @selected($shop['suggested'])>{{ $shop['label'] }}{{ $shop['suggested'] ? ' — مقترحة' : '' }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </label>
                        <button class="notice-approve" type="submit" @disabled(empty($notice['shops']))><i class="bi bi-check2-circle" aria-hidden="true"></i> موافقة وتوجيه</button>
                    </form>

                    <form method="POST" action="{{ $notice['rejectUrl'] }}" class="notice-form">
                        @csrf
                        <label>
                            سبب الرفض (يصل للعميل)
                            <input type="text" name="reason" maxlength="500" required placeholder="مثال: المبلغ في الإشعار لا يطابق قيمة الطلب">
                        </label>
                        <button class="notice-reject" type="submit"><i class="bi bi-x-circle" aria-hidden="true"></i> رفض الطلب</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="notices-empty">
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <strong>لا توجد إشعارات دفع بانتظار المراجعة</strong>
                <p>ستظهر هنا فور أن يرفع العميل إشعار الدفع لطلبه.</p>
            </div>
        @endforelse

        @if ($reviewed->isNotEmpty())
            <h2 class="notices-heading">آخر القرارات</h2>
            <div class="notices-table-wrap" tabindex="0" aria-label="آخر القرارات، قابل للتمرير أفقياً">
                <table class="notices-table">
                    <thead>
                        <tr>
                            <th scope="col">رقم الطلب</th>
                            <th scope="col">العميل</th>
                            <th scope="col">المبلغ</th>
                            <th scope="col">طريقة الدفع</th>
                            <th scope="col">القرار</th>
                            <th scope="col">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviewed as $notice)
                            <tr>
                                <td><bdi>{{ $notice['orderNumber'] }}</bdi></td>
                                <td>{{ $notice['customer'] }}</td>
                                <td><bdi>{{ number_format($notice['amount'], 2) }} ₪</bdi></td>
                                <td>{{ $notice['method'] }}</td>
                                <td>
                                    @if ($notice['status'] === 'paid')
                                        <span class="notice-badge is-approved">تمت الموافقة</span>
                                    @else
                                        <span class="notice-badge is-rejected">مرفوض</span>
                                        @if ($notice['reason'])<small class="notice-reason">{{ $notice['reason'] }}</small>@endif
                                    @endif
                                </td>
                                <td>{{ $notice['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>
@endsection
