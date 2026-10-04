<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>جارٍ التحميل | PALPRINTS</title>
    <link rel="stylesheet" href="{{ asset('front/errors/loading.css') }}?v={{ filemtime(public_path('front/errors/loading.css')) }}" />
  </head>
  <body class="loading-page">
    <main class="loading-content" role="status" aria-live="polite" aria-busy="true">
      <div class="loading-products" aria-hidden="true">
        <svg class="loading-product loading-product--shirt" viewBox="0 0 200 190" fill="none">
          <path d="M72 24 48 35 24 75l30 19 10-17v88h72V77l10 17 30-19-24-40-24-11" />
          <path d="M72 24c3 18 14 28 28 28s25-10 28-28" />
          <path d="M120 84c-8 12-18 19-30 23M108 111c-9 7-19 11-30 11" />
        </svg>

        <svg class="loading-product loading-product--mug" viewBox="0 0 200 190" fill="none">
          <path d="M48 44h91v106c0 10-8 18-18 18H66c-10 0-18-8-18-18Z" />
          <path d="M139 67h10c19 0 31 12 31 30s-12 30-31 30h-10" />
          <path d="M72 27c-7-8 6-13 0-21M96 27c-7-8 6-13 0-21M120 27c-7-8 6-13 0-21" />
          <path d="M76 96c11 9 23 13 36 0" />
        </svg>

        <svg class="loading-product loading-product--bag" viewBox="0 0 200 190" fill="none">
          <path d="M50 56h100v108H50z" />
          <path d="M50 72h100" />
          <path d="M72 56V43c0-22 11-35 28-35s28 13 28 35v29" />
          <circle cx="72" cy="82" r="4" />
          <circle cx="128" cy="82" r="4" />
        </svg>
      </div>

      <p class="loading-brand" dir="ltr" aria-hidden="true">PalPrints</p>

      <h1 class="loading-title">جارٍ التحميل...</h1>
    </main>
  </body>
</html>
