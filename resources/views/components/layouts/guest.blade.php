<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('Opticedge Credit') }}</title>

    <link rel="icon" href="{{ asset('opticedgecredity.jpeg') }}" type="image/jpeg" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('opticedgecredity.jpeg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-full overflow-x-hidden overflow-y-auto bg-[#eef0f4] text-brand-charcoal antialiased selection:bg-brand-orange/20">
    {{ $slot }}

    @fluxScripts
    @livewireScripts
</body>
</html>
