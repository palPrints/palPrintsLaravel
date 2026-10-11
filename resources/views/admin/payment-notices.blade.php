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

        <details class="notices-how">
            <summary><i class="bi bi-lightbulb" aria-hidden="true"></i> على أي أساس تُقترح المطابع؟</summary>
            <p>نقترح فقط المطابع المفعّلة والمعتمدة التي تقدر تنفّذ <strong>كل</strong> بنود الطلب: تقدّم المنتج، وتوفّر اللون والمقاس المطلوبين، وتقدّم مناطق الطباعة المختارة (وحجم التصميم يناسب أقصى حجم تطبعه)، وعندها طريقة طباعة مفعّلة. ثم نرتّبها:</p>
            <ol>
                <li><strong>مدينة العميل أولًا:</strong> المطبعة في نفس مدينة عنوان الشحن.</li>
                <li><strong>الأقل تكلفة:</strong> التكلفة التقديرية = سعر المنتج عند المطبعة + سعر الطباعة لكل منطقة حسب حجم التصميم.</li>
                <li><strong>الأسرع تنفيذًا:</strong> أقل مدة تحضير.</li>
            </ol>
            <p>تظهر أفضل ثلاث مطابع، ويمكنك اختيار مطبعة أخرى يدويًا.</p>
        </details>

        @if ($onlyOrder)
            <p class="notices-flash" role="status"><i class="bi bi-funnel" aria-hidden="true"></i> يظهر هنا إشعار الطلب <bdi>{{ $onlyOrder }}</bdi> فقط. <a href="{{ route('admin.payment-notices') }}">عرض كل الإشعارات</a></p>
        @endif

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
            <details class="notice-card"@if ($pending->count() === 1 || $onlyOrder) open @endif>
                <summary class="notice-head">
                    <div>
                        <strong>طلب <bdi>{{ $notice['orderNumber'] }}</bdi></strong>
                        <small>{{ $notice['date'] }}</small>
                    </div>
                    <span class="notice-head-meta"><span><i class="bi bi-person" aria-hidden="true"></i> {{ $notice["customer"] }}</span><span><i class="bi {{ $notice["icon"] }}" aria-hidden="true"></i> {{ $notice["method"] }}</span></span>
                    <bdi class="notice-amount">{{ number_format($notice["amount"], 2) }} ₪</bdi>
                    <i class="bi bi-chevron-down notice-chevron" aria-hidden="true"></i>
                </summary>

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

                @php($shops = $notice['shops'])
                <div class="notice-actions">
                    <form method="POST" action="{{ $notice['approveUrl'] }}" class="notice-form notice-form--approve">
                        @csrf
                        <fieldset class="shop-picker">
                            <legend class="notice-form__title"><i class="bi bi-printer" aria-hidden="true"></i> المطبعة التي سيُوجَّه إليها الطلب</legend>

                            @if (empty($shops['top']) && empty($shops['others']))
                                <p class="shop-empty"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> لا توجد مطبعة تقدّم كل منتجات الطلب.</p>
                            @else
                                @if (empty($shops['top']))
                                    <p class="shop-empty"><i class="bi bi-info-circle" aria-hidden="true"></i> لا توجد مطبعة تطابق كل مواصفات الطلب (اللون أو المقاس أو مناطق الطباعة). يمكنك اختيار مطبعة يدويًا من القائمة.</p>
                                @else
                                    <p class="shop-hint">أفضل {{ count($shops['top']) === 1 ? 'مطبعة' : count($shops['top']).' مطابع' }} تقدر تنفّذ الطلب بكل مواصفاته:</p>
                                    <div class="shop-options">
                                        @foreach ($shops['top'] as $shop)
                                            <label class="shop-option{{ $shop['rank'] === 1 ? ' is-best' : '' }}">
                                                <input type="radio" name="branch_id" value="{{ $shop['id'] }}" @checked($shop['rank'] === 1)>
                                                <span class="shop-option__body">
                                                    <span class="shop-option__name">{{ $shop['name'] }}</span>
                                                    <span class="shop-option__meta">
                                                        @if ($shop['city'])<span><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $shop['city'] }}</span>@endif
                                                        <span><i class="bi bi-cash-coin" aria-hidden="true"></i> <bdi>{{ number_format($shop['estimate'], 2) }} ₪</bdi></span>
                                                        @if ($shop['days'] > 0)<span><i class="bi bi-clock" aria-hidden="true"></i> حتى {{ $shop['days'] }} يوم</span>@endif
                                                    </span>
                                                    @if ($shop['reasons'])
                                                        <span class="shop-option__reasons">@foreach ($shop['reasons'] as $reason)<em>{{ $reason }}</em>@endforeach</span>
                                                    @endif
                                                </span>
                                                <span class="shop-option__rank">{{ $shop['rank'] === 1 ? 'الأفضل' : '#'.$shop['rank'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif

                                @if (! empty($shops['others']))
                                    <details class="shop-others"@if (empty($shops['top'])) open @endif>
                                        <summary>{{ empty($shops['top']) ? 'اختيار مطبعة يدويًا' : 'مطابع أخرى (لا تطابق كل المواصفات)' }}</summary>
                                        <select name="{{ empty($shops['top']) ? 'branch_id' : 'branch_id_other' }}" @if (empty($shops['top'])) required @endif aria-label="مطبعة أخرى">
                                            <option value="">اختر المطبعة</option>
                                            @foreach ($shops['others'] as $shop)
                                                <option value="{{ $shop['id'] }}">{{ $shop['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </details>
                                @endif
                            @endif
                        </fieldset>
                        <button class="notice-approve" type="submit" @disabled(empty($shops['top']) && empty($shops['others']))><i class="bi bi-check2-circle" aria-hidden="true"></i> موافقة وتوجيه</button>
                    </form>

                    <form method="POST" action="{{ $notice['rejectUrl'] }}" class="notice-form notice-form--reject">
                        @csrf
                        <div class="notice-form__title is-danger"><i class="bi bi-x-octagon" aria-hidden="true"></i> رفض الطلب</div>
                        <label>
                            سبب الرفض (يصل للعميل)
                            <input type="text" name="reason" maxlength="500" required placeholder="مثال: المبلغ في الإشعار لا يطابق قيمة الطلب">
                        </label>
                        <button class="notice-reject" type="submit"><i class="bi bi-x-circle" aria-hidden="true"></i> رفض الطلب</button>
                    </form>
                </div>
            </details>
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

@push('scripts')
    <script>
        /* One choice per order: picking a shop from the "other shops" list replaces the ticked card, and ticking a card clears the list. */
        document.querySelectorAll('.notice-form--approve').forEach(function (form) {
            const other = form.querySelector('select[name="branch_id_other"]');
            if (!other) return;
            other.addEventListener('change', function () {
                if (other.value) form.querySelectorAll('input[name="branch_id"]').forEach(function (radio) { radio.checked = false; });
            });
            form.querySelectorAll('input[name="branch_id"]').forEach(function (radio) {
                radio.addEventListener('change', function () { other.value = ''; });
            });
        });
    </script>
@endpush
