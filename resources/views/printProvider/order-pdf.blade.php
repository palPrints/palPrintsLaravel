<style>
    body { font-family: dejavusans; font-size: 10pt; color: #0b1f3a; }
    h1 { font-size: 17pt; margin: 0 0 2pt; }
    h2 { font-size: 12pt; margin: 14pt 0 4pt; color: #1677ff; }
    .muted { color: #5b6b82; font-size: 9pt; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 0.5pt solid #c8d2e0; padding: 4pt 6pt; text-align: right; vertical-align: top; }
    th { background-color: #eef2f8; font-size: 9pt; }
    .spec td.k { width: 28%; background-color: #f6f8fc; color: #5b6b82; }
    .pic { text-align: center; border: 0.5pt solid #c8d2e0; padding: 4pt; }
    .pic img { width: 100%; }
    .cap { font-weight: bold; padding-bottom: 3pt; }
</style>

<h1>ورقة طلب طباعة: {{ $item['name'] }}</h1>
<div class="muted">الطلب {{ $order->order_number }} · وصل المطبعة {{ $item['date_label'] }}</div>

<h2>مواصفات الطلب</h2>
<table class="spec">
    <tr><td class="k">المنتج</td><td>{{ $item['name'] }}</td></tr>
    <tr><td class="k">الكمية</td><td>{{ $item['quantity'] }}</td></tr>
    @foreach ($item['details'] as $detail)
        <tr><td class="k">{{ $detail['label'] }}</td><td>{{ $detail['value'] }}</td></tr>
    @endforeach
    @if ($item['design_title'])<tr><td class="k">التصميم</td><td>{{ $item['design_title'] }}</td></tr>@endif
    @if ($item['notes'])<tr><td class="k">ملاحظات العميل</td><td>{{ $item['notes'] }}</td></tr>@endif
</table>

@foreach ($item['paper_files'] ?? [] as $card)
    <h2>{{ $card['title'] }}</h2>
    <table class="spec">
        @foreach ($card['rows'] as $row)
            <tr><td class="k">{{ $row['label'] }}</td><td>{{ $row['value'] }}</td></tr>
        @endforeach
    </table>
@endforeach

@foreach ($previewImages as $preview)
    <h2>شكل المنتج: {{ $preview['name'] }}</h2>
    <table>
        <tr>
            <td class="pic" style="width: 50%"><div class="cap">المنتج بلونه</div><img src="{{ $preview['bare'] }}"></td>
            @if ($preview['with_design'])
                <td class="pic" style="width: 50%"><div class="cap">المنتج مع التصميم</div><img src="{{ $preview['with_design'] }}"></td>
            @endif
        </tr>
    </table>
@endforeach

@foreach ($designImages as $design)
    <pagebreak />
    <h2>صورة الطباعة: {{ $design['name'] }}</h2>
    <div class="muted">هذي الصورة اللي بتنطبع بالمقاس الحقيقي للمنطقة. الخلفية الرمادية للعرض فقط ولا تنطبع.</div>
    <div class="pic" style="margin-top: 6pt"><img src="{{ $design['src'] }}" style="width: 60%"></div>
@endforeach

@foreach ($layout as $area)
    <h2>تفاصيل التصميم: {{ $area['name'] }}</h2>
    <div class="muted">مقاس المنطقة {{ $area['width'] }} × {{ $area['height'] }} سم. القياسات بالسنتيمتر من الزاوية العلوية اليسرى للمنطقة.</div>
    <table style="margin-top: 4pt">
        <tr><th>#</th><th>العنصر</th><th>المقاس (سم)</th><th>من اليسار</th><th>من الأعلى</th><th>ملاحظات</th></tr>
        @foreach ($area['objects'] as $row)
            <tr>
                <td>{{ $row['number'] }}</td>
                <td>{{ $row['type'] }}@if ($row['what'] !== '') <br><span class="muted">{{ $row['what'] }}</span>@endif</td>
                <td>{{ $row['width'] }} × {{ $row['height'] }}</td>
                <td>{{ $row['left'] }}</td>
                <td>{{ $row['top'] }}</td>
                <td>
                    @if ($row['angle'] != 0)دوران {{ $row['angle'] }}°<br>@endif
                    @if ($row['flip'] !== ''){{ $row['flip'] }}<br>@endif
                    @if ($row['font'] !== ''){{ $row['font'] }}{{ $row['bold'] ? ' (عريض)' : '' }}<br>@endif
                    @if ($row['color'] !== '')لون {{ $row['color'] }}@endif
                </td>
            </tr>
        @endforeach
    </table>
@endforeach
