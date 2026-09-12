<!DOCTYPE html>
<html
  lang="ar"
  dir="rtl"
  data-bs-theme="light"
>

<head>
  <meta charset="UTF-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
  >

  <meta
    name="description"
    content="منصة PalPrints للطباعة حسب الطلب والتصاميم والمنتجات المخصصة"
  >

  <title>@yield('title', 'PALPRINTS')</title>

  <!-- Bootstrap Icons -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
  >

  <!-- Cairo Font -->
  <link
    rel="preconnect"
    href="https://fonts.googleapis.com"
  >

  <link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
  >

  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
  >

  <link
    rel="stylesheet"
    href="{{ asset('front/css/shared/site-footer.css') }}?v={{ filemtime(public_path('front/css/shared/site-footer.css')) }}"
  >

  @stack('styles')

  <script>
    /* Light is the default; opt into dark only when the browser/device requests it. */
    if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
      document.documentElement.dataset.bsTheme = "dark";
    }
  </script>
</head>

<body class="@yield('body-class')">

  @include('partials.header')

  @yield('content')

  @include('partials.footer')

  @stack('scripts')

</body>
</html>
