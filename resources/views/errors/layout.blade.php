<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex" />
    <title>@yield('code') | PALPRINTS</title>
    <link rel="stylesheet" href="{{ asset('front/errors/error.css') }}?v={{ @filemtime(public_path('front/errors/error.css')) }}" />
  </head>
  <body class="error-page error-@yield('code')">

    <main class="error-main">
      <section class="error-card" aria-labelledby="error-title">

        <p class="error-code" aria-hidden="true">@yield('code')</p>
        <span class="error-badge">@yield('badge')</span>
        <h1 class="error-title" id="error-title">@yield('title')</h1>
        <p class="error-text">@yield('text')</p>

        <div class="error-actions">
          @yield('actions')
        </div>
        @hasSection('help')
        <p class="error-help">هل تحتاج إلى مساعدة؟ <a href="{{ $supportUrl }}">تواصل مع الدعم</a></p>
        @endif
      </section>
    </main>
    @hasSection('script')
    <script src="{{ asset('front/errors/error.js') }}?v={{ @filemtime(public_path('front/errors/error.js')) }}" defer></script>
    @endif
  </body>
</html>
