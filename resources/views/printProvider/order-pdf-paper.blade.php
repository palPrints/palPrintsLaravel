<style>
    body { font-family: dejavusans; font-size: 10pt; color: #0b1f3a; }
    table { border-collapse: collapse; width: 100%; }
    td, th { text-align: right; vertical-align: middle; }
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
    .general td { padding: 5pt 9pt; border-bottom: 0.5pt solid #e3e9f2; }
    .general .k { width: 30%; color: #5b6b82; background-color: #f8fafd; }
    .card { page-break-inside: avoid; margin-top: 11pt; border: 1pt solid #b8d4fb; }
    .card .head td { background-color: #eaf2ff; padding: 7pt 10pt; }
    .card .num { width: 26pt; background-color: #1677ff !important; color: #ffffff; font-size: 13pt; font-weight: bold; text-align: center; }
    .card .name { font-size: 11.5pt; font-weight: bold; }
    .card .rows td { padding: 6pt 10pt; border-top: 0.5pt solid #e3e9f2; }
    .card .rows .k { width: 28%; color: #5b6b82; background-color: #f8fafd; }
    .card .rows .v { font-weight: bold; }
    .card .foot td { border-top: 0.5pt solid #e3e9f2; padding: 7pt 10pt; font-size: 9pt; color: #5b6b82; }
    .box { border: 0.9pt solid #5b6b82; padding: 0 6pt; font-size: 9pt; }
    .footer { margin-top: 12pt; font-size: 8pt; color: #8a97ab; text-align: center; }
</style>

<table class="band">
    <tr>
        <td>
            <div class="title">ورقة طلب طباعة أوراق</div>
            <div class="sub">للاستخدام داخل المطبعة، تُطبع وتُرفق مع الطلب</div>
        </td>
        <td class="brand" style="width: 28%">PALPRINTS</td>
    </tr>
</table>

<table class="info">
    <tr>
        <td><div class="l">رقم الطلب</div><div class="v" dir="ltr" style="text-align: right">{{ $order->order_number }}</div></td>
        <td><div class="l">وصل المطبعة</div><div class="v">{{ $item['date_label'] }} · {{ $item['time_label'] }}</div></td>
        <td><div class="l">عدد الملفات</div><div class="v">{{ count($item['paper_files']) }}</div></td>
        <td><div class="l">الكمية</div><div class="v">{{ $item['quantity'] }}</div></td>
    </tr>
</table>

@if ($item['notes'])
    <div class="note"><b>ملاحظات العميل:</b> {{ $item['notes'] }}</div>
@endif

@if (count($item['details']))
    <h2>إعدادات الطلب العامة</h2>
    <table class="general">
        @foreach ($item['details'] as $detail)
            <tr><td class="k">{{ $detail['label'] }}</td><td><b>{{ $detail['value'] }}</b></td></tr>
        @endforeach
    </table>
@endif

<h2>الملفات المطلوب طباعتها</h2>
@foreach ($item['paper_files'] as $card)
    <table class="card">
        <tr class="head">
            <td class="num">{{ $loop->iteration }}</td>
            <td class="name">{{ $card['title'] }}<br><span style="font-weight: normal; font-size: 8.5pt; color: #5b6b82">ملف {{ $loop->iteration }} من {{ $loop->count }}</span></td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 0">
                <table class="rows">
                    @foreach ($card['rows'] as $row)
                        <tr><td class="k">{{ $row['label'] }}</td><td class="v">{{ $row['value'] }}</td></tr>
                    @endforeach
                </table>
            </td>
        </tr>
        <tr class="foot">
            <td colspan="2"><span class="box">&nbsp;&nbsp;</span> تمت الطباعة &nbsp;&nbsp;&nbsp; <span class="box">&nbsp;&nbsp;</span> تم التغليف &nbsp;&nbsp;&nbsp; <span class="box">&nbsp;&nbsp;</span> جاهز للتسليم</td>
        </tr>
    </table>
@endforeach

<div class="footer">PALPRINTS · الطلب {{ $order->order_number }}</div>
