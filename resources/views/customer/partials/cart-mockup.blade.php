{{--
    Redraws a customer-made design the way the preview showed it: the product picture (tinted with the chosen color
    when it has no picture of its own), the print zone, and the artwork placed inside it. Positions are percentages
    of the same 1 : 1.1 box the preview uses, so the thumbnail matches it. All values are sanitized when saved
    (CartController::cleanMockup).
--}}
@php
    $zone = $mockup['zone'];
    $frame = fn (array $i) => sprintf(
        'left:%s%%;top:%s%%;width:%s%%;height:%s%%;z-index:%d;transform:translate(-50%%,-50%%) rotate(%sdeg) scale(%d,%d)',
        $i['x'], $i['y'], $i['width'], $i['height'], $i['layer'], $i['rotation'], $i['flip_x'] ? -1 : 1, $i['flip_y'] ? -1 : 1
    );
    $mask = fn (string $src, string $color) => sprintf(
        "background-color:%s;-webkit-mask:url('%s') center/contain no-repeat;mask:url('%s') center/contain no-repeat",
        $color, $src, $src
    );
@endphp
<div class="cart-mockup" style="position:relative;height:100%;aspect-ratio:1/1.1;flex:none" role="img" aria-label="{{ $alt }}">
    @if(!empty($mockup['tint']))
        <div style="position:absolute;inset:0;{{ $mask($mockup['image'], $mockup['tint']) }}"></div>
    @endif
    <img src="{{ $mockup['image'] }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;max-width:none;object-fit:contain;@if(!empty($mockup['tint']))mix-blend-mode:multiply @endif">
    <div style="position:absolute;top:{{ $zone['top'] }}%;left:{{ $zone['left'] }}%;width:{{ $zone['width'] }}%;height:{{ $zone['height'] }}%;container-type:size;overflow:hidden">
        @foreach($mockup['images'] as $art)
            @if($art['tint'])
                <span style="position:absolute;{{ $frame($art) }};{{ $mask($art['src'], $art['tint']) }}"></span>
            @else
                <img src="{{ $art['src'] }}" alt="" style="position:absolute;max-width:none;object-fit:contain;{{ $frame($art) }}">
            @endif
        @endforeach
        @foreach($mockup['texts'] as $text)
            <span style="position:absolute;{{ $frame($text) }};height:auto;font-size:{{ $text['size_percent'] }}cqh;font-family:'{{ $text['font_family'] }}',sans-serif;color:{{ $text['color'] }};font-weight:{{ $text['font_weight'] }};font-style:{{ $text['font_style'] }};text-align:{{ $text['text_align'] }};line-height:{{ $text['line_height'] }};white-space:pre-wrap">{{ $text['content'] }}</span>
        @endforeach
    </div>
</div>
