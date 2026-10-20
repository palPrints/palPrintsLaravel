<style>
    body { font-family: dejavusans; font-size: 10pt; color: #0b1f3a; }
    table { border-collapse: collapse; width: 100%; }
    td { text-align: right; vertical-align: middle; }
    .band td { background-color: #1677ff; color: #ffffff; padding: 10pt 14pt; }
    .band .title { font-size: 16pt; font-weight: bold; }
    .band .sub { font-size: 9pt; color: #dbeafe; }
    .band .brand { font-size: 12pt; font-weight: bold; text-align: left; }
    .info td { background-color: #f3f6fb; border: 3pt solid #ffffff; padding: 7pt 9pt; width: 25%; }
    .info .l { font-size: 8pt; color: #5b6b82; }
    .info .v { font-size: 12pt; font-weight: bold; color: #0b1f3a; }
    h2 { font-size: 12pt; color: #0b1f3a; margin: 14pt 0 4pt; border-right: 4pt solid #1677ff; padding-right: 6pt; }
    .note { background-color: #fff8e1; border: 0.8pt solid #f5d78a; padding: 7pt 10pt; margin-top: 8pt; }
    .note b { color: #8a5a00; }
    .spec td { border: 0.5pt solid #d5deeb; padding: 6pt 9pt; width: 33.33%; }
    .spec .l { font-size: 8pt; color: #5b6b82; }
    .spec .v { font-size: 11pt; font-weight: bold; }
    .files td, .files th { border: 0.5pt solid #d5deeb; padding: 6pt 9pt; text-align: right; }
    .files th { background-color: #eaf2ff; font-size: 9pt; color: #5b6b82; }
    .pic { text-align: center; vertical-align: top; border: 0.5pt solid #d5deeb; padding: 6pt; width: 50%; }
    .pic .cap { font-size: 11pt; margin: 0 0 6pt; }
    .pic img { width: 58%; }
    .footer { margin-top: 12pt; font-size: 8pt; color: #8a97ab; text-align: center; }
</style>

@php
    $cells = collect([['label' => 'المنتج', 'value' => $item['name']]])
        ->merge($item['details'])
        ->when($item['design_title'], fn ($all) => $all->push(['label' => 'التصميم', 'value' => $item['design_title']]));
@endphp

<table class="band">
    <tr>
        <td>
            <div class="title">تفاصيل الطلب: {{ $item['name'] }}</div>
            <div class="sub">للاستخدام داخل المطبعة، صور التصميم ملفات منفصلة باسم كل وجه، مرفقة مع الطلب</div>
        </td>
        <td class="brand" style="width: 28%">PALPRINTS</td>
    </tr>
</table>

<table class="info">
    <tr>
        <td><div class="l">رقم الطلب</div><div class="v"><b>{{ $order->order_number }}</b></div></td>
        <td><div class="l">وصل المطبعة</div><div class="v"><b>{{ $item['date_label'] }} · {{ $item['time_label'] }}</b></div></td>
        <td><div class="l">الكمية</div><div class="v"><b>{{ $item['quantity'] }}</b></div></td>
    </tr>
</table>

@if ($item['notes'])
    <div class="note"><b>ملاحظات العميل:</b> {{ $item['notes'] }}</div>
@endif

<h2>مواصفات الطلب</h2>
<table class="spec">
    @foreach ($cells->chunk(3) as $row)
        <tr>
            @foreach ($row as $cell)
                <td><div class="l">{{ $cell['label'] }}</div><div class="v"><b>{{ $cell['value'] }}</b></div></td>
            @endforeach
            @for ($i = $row->count(); $i < 3; $i++)
                <td></td>
            @endfor
        </tr>
    @endforeach
</table>

@if (count($areas))
    <h2>ملفات الطباعة</h2>
    <table class="files">
        <tr><th>الوجه</th><th>اسم الصورة</th><th>مقاس الصورة على المنتج (سم)</th></tr>
        @foreach ($areas as $area)
            <tr>
                <td><b>{{ $area['name'] }}</b></td>
                <td dir="ltr" style="text-align: right">{{ $area['file'] }}</td>
                <td>{{ $area['dimensions'] ?? '—' }}</td>
            </tr>
        @endforeach
    </table>
@endif

<div class="footer">PALPRINTS · الطلب {{ $order->order_number }}</div>
