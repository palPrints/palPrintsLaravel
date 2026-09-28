@if (session('warning'))
    <div class="designer-flash" role="alert">
        <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
        <div class="designer-flash-copy">
            <strong>تنبيه</strong>
            <small>{{ session('warning') }}</small>
        </div>
        @if (! request()->routeIs('designer.profile'))
            <a href="{{ route('designer.profile') }}">الملف الشخصي</a>
        @endif
    </div>
@endif

@if (session('success'))
    <div class="designer-flash is-success" role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <div class="designer-flash-copy">
            <strong>{{ session('success') }}</strong>
        </div>
    </div>
@endif
