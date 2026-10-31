<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0b0c0f">

    <title inertia>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}" inertia="description">
    <meta name="robots" content="{{ $seo['robots'] }}" inertia="robots">
    @if ($seo['canonical'])
        <link rel="canonical" href="{{ $seo['canonical'] }}" inertia="canonical">
    @endif

    <meta property="og:type" content="{{ $seo['type'] }}" inertia="og:type">
    <meta property="og:site_name" content="{{ $seo['site_name'] }}" inertia="og:site_name">
    <meta property="og:title" content="{{ $seo['title'] }}" inertia="og:title">
    <meta property="og:description" content="{{ $seo['description'] }}" inertia="og:description">
    <meta property="og:image" content="{{ $seo['image'] }}" inertia="og:image">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}" inertia="og:url">
    <meta name="twitter:card" content="summary_large_image" inertia="twitter:card">
    <meta name="twitter:title" content="{{ $seo['title'] }}" inertia="twitter:title">
    <meta name="twitter:description" content="{{ $seo['description'] }}" inertia="twitter:description">
    <meta name="twitter:image" content="{{ $seo['image'] }}" inertia="twitter:image">
    @if ($seo['twitter'])
        <meta name="twitter:site" content="{{ '@'.$seo['twitter'] }}">
    @endif
    @if ($seo['google_verification'])
        <meta name="google-site-verification" content="{{ $seo['google_verification'] }}">
    @endif
    @if ($seo['bing_verification'])
        <meta name="msvalidate.01" content="{{ $seo['bing_verification'] }}">
    @endif

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16.png">
    <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    @if ($seo['jsonld'])
        <script type="application/ld+json" id="seo-jsonld">{!! json_encode($seo['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif

    {{-- apply the saved / device theme before first paint to avoid a flash --}}
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var light = saved ? saved === 'light' : window.matchMedia('(prefers-color-scheme: light)').matches;
                if (light) document.documentElement.classList.add('light');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
