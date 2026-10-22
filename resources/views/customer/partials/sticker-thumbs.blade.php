{{-- A sticker bundle shown by the stickers in it (up to 4 tiles, the last one "+N" when there are more). --}}
<div class="sticker-thumbs sticker-thumbs--{{ min(count($images), 4) }}" role="img" aria-label="{{ $alt }}">
    @foreach(array_slice($images, 0, count($images) > 4 ? 3 : 4) as $image)
        <img src="{{ $image }}" alt="" loading="lazy">
    @endforeach
    @if(count($images) > 4)
        <span class="sticker-thumbs__more">+{{ count($images) - 3 }}</span>
    @endif
</div>
