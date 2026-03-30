<!DOCTYPE html>
<html lang="en" class="h-full bg-[#0f172a]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Opticedge Credit - Secure Console' }}</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

     
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-[#0f172a] text-white selection:bg-orange-500/30 selection:text-white">
    {{ $slot }}
    
    @fluxScripts
</body>
</html>
