<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php($storeProfile = \App\Models\Setting::storeProfile())
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $storeProfile['store_name'] ?: config('app.name', 'Laravel') }}</title>
        @if ($storeProfile['store_favicon_url'])
            <link rel="icon" type="image/png" sizes="64x64" href="{{ $storeProfile['store_favicon_url'] }}">
            <link rel="shortcut icon" href="{{ $storeProfile['store_favicon_url'] }}">
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
