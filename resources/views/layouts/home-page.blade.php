<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title') | PALPRINTS</title>
    <link rel="stylesheet" href="{{ asset('front/home/css/home.css') }}?v={{ hash_file('sha256', public_path('front/home/css/home.css')) }}" />
    <link rel="stylesheet" href="{{ asset('front/home/css/about.css') }}?v={{ filemtime(public_path('front/home/css/about.css')) }}" />
    <link rel="stylesheet" href="{{ asset('front/shared/theme-dark.css') }}?v={{ filemtime(public_path('front/shared/theme-dark.css')) }}" />
    <script src="{{ asset('front/shared/theme-dark.js') }}?v={{ filemtime(public_path('front/shared/theme-dark.js')) }}"></script>
  </head>
  <body class="home-page about-page">
    @include('partials.home-header', ['active' => $active ?? null])

    <main class="about-main">
      <article class="about-card-page">
        @yield('content')
      </article>
    </main>

    @include('partials.home-footer')

    <script src="{{ asset('front/home/js/home.js') }}?v={{ filemtime(public_path('front/home/js/home.js')) }}"></script>
  @include('partials.page-loader')
  </body>
</html>
