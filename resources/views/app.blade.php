<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php($storeProfile = \App\Models\Setting::storeProfile())
    @php($seo = \App\Support\PublicSeoMeta::forRequest(request(), $storeProfile))
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="{{ $storeProfile['primary_color'] }}">
        <meta name="application-name" content="{{ $storeProfile['store_name'] }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ $storeProfile['store_name'] }}">

        <title>{{ $storeProfile['store_name'] ?: config('app.name', 'Laravel') }}</title>
        <meta name="description" content="{{ $seo['description'] }}">
        <meta name="robots" content="{{ $seo['robots'] }}">
        <meta name="googlebot" content="{{ $seo['robots'] === 'index,follow' ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1' : 'noindex,nofollow,noarchive' }}">
        <link rel="canonical" href="{{ $seo['canonical'] }}">
        <meta property="og:type" content="{{ $seo['type'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:url" content="{{ $seo['canonical'] }}">
        <meta property="og:site_name" content="{{ $storeProfile['store_name'] }}">
        <meta property="og:locale" content="id_ID">
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="{{ $storeProfile['store_favicon_url'] ?: '/pwaicon.svg' }}">
        @if ($storeProfile['store_favicon_url'])
            <link rel="icon" type="image/png" sizes="64x64" href="{{ $storeProfile['store_favicon_url'] }}">
            <link rel="shortcut icon" href="{{ $storeProfile['store_favicon_url'] }}">
        @else
            <link rel="icon" type="image/svg+xml" href="/pwaicon.svg">
        @endif

        @fonts

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
