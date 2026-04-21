<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('Opticedge Credit') }}</title>

    <link rel="icon" href="{{ asset('opticedgecredity.jpeg') }}" type="image/jpeg" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('opticedgecredity.jpeg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-slate-50 text-slate-900 selection:bg-[#F58220]/25 selection:text-[#2D3748]">
    {{ $slot }}

    @fluxScripts
    @livewireScripts
</body>
</html>
