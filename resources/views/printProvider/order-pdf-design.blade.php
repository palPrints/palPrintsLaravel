<style>
    body { font-family: dejavusans; font-size: 10pt; color: #0b1f3a; }
    table { border-collapse: collapse; width: 100%; }
    td { text-align: right; vertical-align: middle; }
    .band td { background-color: #1677ff; color: #ffffff; padding: 10pt 14pt; }
    .band .title { font-size: 16pt; font-weight: bold; }
    .band .sub { font-size: 9pt; color: #dbeafe; }
    .band .brand { font-size: 12pt; font-weight: bold; text-align: left; }
    .info td { background-color: #f3f6fb; border: 3pt solid #ffffff; padding: 7pt 9pt; width: 33.33%; }
    .info .l { font-size: 8pt; color: #5b6b82; }
    .info .v { font-size: 12pt; font-weight: bold; color: #0b1f3a; }
    h2 { font-size: 12pt; color: #0b1f3a; margin: 14pt 0 4pt; border-right: 4pt solid #1677ff; padding-right: 6pt; }
    .pair td { width: 50%; text-align: center; vertical-align: top; border: 0.5pt solid #d5deeb; padding: 8pt; }
    .pair .cap { font-size: 9pt; color: #5b6b82; padding-bottom: 6pt; }
    .board { background-color: #eef2f7; padding: 8pt; }
    .size { font-size: 9pt; color: #5b6b82; padding-top: 4pt; }
    .missing { font-size: 9pt; color: #8a97ab; padding: 30pt 8pt; }
    .footer { margin-top: 12pt; font-size: 8pt; color: #8a97ab; text-align: center; }
</style>

@php
    // One block per print area: the design on its own next to the product wearing it.
    $mockups = collect($previewImages)->keyBy('name');
    $sizes = collect($areas)->pluck('dimensions', 'name');
    $blocks = collect($designs)->map(fn ($design) => $design + ['mockup' => $mockups->get($design['name'])]);
    // Product pictures with no print-ready picture of their own (an area drawn only on the mockup) still show.
    $extra = $mockups->reject(fn ($mockup, $name) => $blocks->contains('name', $name))->values();
@endphp

<table class="band">
    <tr>
        <td>
            <div class="title">التصميم: {{ $item['name'] }}{{ $item['design_title'] ? ' — '.$item['design_title'] : '' }}</div>
            <div class="sub">التصميم الجاهز للطباعة لكل وجه، والمنتج والتصميم عليه بلون الطلب</div>
        </td>
        <td class="brand" style="width: 28%">PALPRINTS</td>
    </tr>
</table>

<table class="info">
    <tr>
        <td><div class="l">رقم الطلب</div><div class="v"><b>{{ $order->order_number }}</b></div></td>
        <td><div class="l">الكمية</div><div class="v"><b>{{ $item['quantity'] }}</b></div></td>
        <td><div class="l">المواصفات</div><div class="v"><b>{{ collect($item['details'])->whereIn('label', ['القياس', 'اللون'])->pluck('value')->implode(' · ') ?: '—' }}</b></div></td>
    </tr>
</table>

@foreach ($blocks as $block)
    <h2>{{ $block['name'] }}</h2>
    <table class="pair">
        <tr>
            <td class="cap">التصميم للطباعة</td>
            <td class="cap">المنتج مع التصميم</td>
        </tr>
        <tr>
            <td class="board">
                <img src="{{ $block['src'] }}" style="max-width: 8cm; max-height: 9cm">
                @if ($sizes->get($block['name']))
                    <div class="size">المقاس على المنتج: {{ $sizes->get($block['name']) }} سم</div>
                @endif
            </td>
            <td>
                @if ($block['mockup'])
                    <img src="{{ $block['mockup']['with_design'] ?: $block['mockup']['bare'] }}" style="width: 7cm">
                @else
                    <div class="missing">لا توجد صورة للمنتج لهذا الوجه</div>
                @endif
            </td>
        </tr>
    </table>
@endforeach

@foreach ($extra as $mockup)
    <h2>{{ $mockup['name'] }}</h2>
    <table class="pair">
        <tr><td class="cap" colspan="2">المنتج مع التصميم</td></tr>
        <tr><td colspan="2"><img src="{{ $mockup['with_design'] ?: $mockup['bare'] }}" style="width: 7cm"></td></tr>
    </table>
@endforeach

<div class="footer">PALPRINTS · الطلب {{ $order->order_number }} · ملف التصميم</div>
