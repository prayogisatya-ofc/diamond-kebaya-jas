<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php($storeProfile = \App\Models\Setting::storeProfile())
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="{{ $storeProfile['primary_color'] }}">
        <meta name="application-name" content="{{ $storeProfile['store_name'] }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ $storeProfile['store_name'] }}">

        <title>{{ $storeProfile['store_name'] ?: config('app.name', 'Laravel') }}</title>
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="{{ $storeProfile['store_favicon_url'] ?: '/pwa-icon.svg' }}">
        @if ($storeProfile['store_favicon_url'])
            <link rel="icon" type="image/png" sizes="64x64" href="{{ $storeProfile['store_favicon_url'] }}">
            <link rel="shortcut icon" href="{{ $storeProfile['store_favicon_url'] }}">
        @else
            <link rel="icon" type="image/svg+xml" href="/pwa-icon.svg">
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
