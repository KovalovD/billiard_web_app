<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Primary Meta Tags -->
    <title inertia>{{ config('app.name', 'WinnerBreak') }}</title>
    <meta name="title" content="{{ config('app.name', 'WinnerBreak') }} - Professional Billiard League Platform">
    <meta name="description"
          content="Join competitive billiard leagues, participate in tournaments, and track your progress with our advanced ELO rating system.">
    <meta name="keywords" content="billiards, pool, league, tournament, ELO rating, competition, sports, cue sports">
    <meta name="author" content="WinnerBreak">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ config('app.name', 'WinnerBreak') }} - Professional Billiard League Platform">
    <meta property="og:description"
          content="Join competitive billiard leagues, participate in tournaments, and track your progress with our advanced ELO rating system.">
    <meta property="og:image" content="{{ asset('og-image.jpg') }}">
    <meta property="og:site_name" content="WinnerBreak">
    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title"
          content="{{ config('app.name', 'WinnerBreak') }} - Professional Billiard League Platform">
    <meta property="twitter:description"
          content="Join competitive billiard leagues, participate in tournaments, and track your progress with our advanced ELO rating system.">
    <meta property="twitter:image" content="{{ asset('og-image.jpg') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Manifest and Theme -->
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#33121e">
    <meta name="msapplication-TileColor" content="#33121e">
    <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Language Alternates -->
    <link rel="alternate" hreflang="{{ str_replace('_', '-', app()->getLocale()) }}" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    <!-- RSS Feed (if available) -->
    {{-- <link rel="alternate" type="application/rss+xml" title="WinnerBreak News" href="{{ url('/feed') }}"> --}}

    <!-- Preconnect to External Domains -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://fonts.bunny.net">

    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

    <!-- Scripts and Styles -->
    @routes
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
@inertia

<!-- Schema.org Organization Data -->
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "WinnerBreak",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('logo.png') }}",
        "description": "Professional billiard league management platform for competitive players",
        "sameAs": [
            "https://facebook.com/winnerbreak",
            "https://twitter.com/winnerbreak",
            "https://instagram.com/winnerbreak"
        ],
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Lviv",
            "addressCountry": "UA"
        }
    }
</script>

<!-- WebSite Schema for Sitelinks Search Box -->
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "WinnerBreak",
        "url": "{{ url('/') }}",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "{{ url('/search') }}?q={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        }
    }
</script>

<!-- Additional Scripts for Analytics -->
@production
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-KVT7YEV29R"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'G-KVT7YEV29R');
    </script>
@endproduction
</body>
</html>
