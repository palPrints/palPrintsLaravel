{{-- Every confirm / alert / prompt pop-up of the site: SweetAlert2 + PalPrints styling. Use PalAlert (see pp-alert.js). --}}
<link rel="stylesheet" href="{{ asset('front/shared/pp-alert.css') }}?v={{ filemtime(public_path('front/shared/pp-alert.css')) }}">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('front/shared/pp-alert.js') }}?v={{ filemtime(public_path('front/shared/pp-alert.js')) }}"></script>
