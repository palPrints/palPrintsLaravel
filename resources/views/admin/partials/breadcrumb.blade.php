{{-- Breadcrumb shown at the top of every admin page: الرئيسية (dashboard) › current page. Pass 'label'. --}}
<nav class="admin-breadcrumb" aria-label="مسار التنقل">
    <a href="{{ route('admin.dashboard') }}">الرئيسية</a>
    <i class="bi bi-chevron-left" aria-hidden="true"></i>
    <span aria-current="page">{{ $label }}</span>
</nav>
