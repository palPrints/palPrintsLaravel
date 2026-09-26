<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $description }}">
<title>{{ $title }} | PalPrints</title>

<link rel="preload" href="{{ asset('front/designer/assets/fonts/Cairo-Variable.ttf') }}" as="font" type="font/ttf" crossorigin>
<link rel="stylesheet" href="{{ asset('front/designer/assets/icons/bootstrap-icons.min.css') }}">
<link rel="stylesheet" href="{{ asset('front/auth/css/auth-laravel.css') }}?v={{ filemtime(public_path('front/auth/css/auth-laravel.css')) }}">
<link rel="stylesheet" href="{{ asset('front/auth/css/'.$css.'.css') }}?v={{ filemtime(public_path('front/auth/css/'.$css.'.css')) }}">
