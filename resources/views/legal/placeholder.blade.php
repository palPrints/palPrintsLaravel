<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | PALPRINTS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; display: grid; place-items: center; margin: 0; padding: 24px; color: #111827; background: #f7f8fc; font-family: "Cairo", sans-serif; }
        main { width: min(100%, 680px); padding: clamp(28px, 6vw, 56px); background: #fff; border: 1px solid #dfe3ec; border-radius: 24px; box-shadow: 0 24px 70px rgba(31, 42, 68, .1); text-align: center; }
        img { width: 150px; height: 60px; object-fit: contain; }
        h1 { margin: 24px 0 12px; font-size: clamp(27px, 5vw, 40px); }
        p { margin: 0; color: #667085; line-height: 1.9; }
        a { display: inline-flex; margin-top: 28px; padding: 11px 20px; color: #fff; background: linear-gradient(135deg, #7357e6, #2f80ed); border-radius: 12px; font-weight: 700; text-decoration: none; }
        .legal-logo-link { margin: 0; padding: 0; background: transparent; border-radius: 0; }
    </style>
</head>
<body>
    <main>
        <a class="legal-logo-link" href="{{ route('home') }}" aria-label="PALPRINTS">
            <img src="{{ asset('front/assets/images/palprints-logo.png') }}" alt="PALPRINTS">
        </a>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ route('register') }}">العودة إلى التسجيل</a>
    </main>
</body>
</html>
