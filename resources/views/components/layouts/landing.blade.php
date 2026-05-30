<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Opticedge Credit — mobile device financing for field agents, dealers, and customers across Tanzania.">
    <title>{{ $title ?? config('app.name', 'Opticedge Credit') }}</title>

    <link rel="icon" href="{{ asset('opticedgecredity.jpeg') }}" type="image/jpeg" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('opticedgecredity.jpeg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        @keyframes landing-float {
            0%, 100% { transform: translateY(0) rotate(-8deg); }
            50% { transform: translateY(-14px) rotate(-6deg); }
        }
        @keyframes landing-float-delayed {
            0%, 100% { transform: translateY(0) rotate(12deg); }
            50% { transform: translateY(-10px) rotate(14deg); }
        }
        @keyframes landing-drift {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .animate-landing-float { animation: landing-float 7s ease-in-out infinite; }
        .animate-landing-float-delayed { animation: landing-float-delayed 8s ease-in-out infinite; }
        .animate-landing-drift { animation: landing-drift 6s ease-in-out infinite; }

        /* Realistic smartphone frame (hero mockups) */
        .phone-frame {
            border-radius: 2.35rem;
            padding: 0.45rem;
            background: linear-gradient(145deg, #1a1a1e 0%, #3f3f46 48%, #18181b 100%);
            box-shadow:
                0 2px 0 rgba(255, 255, 255, 0.12) inset,
                0 28px 60px -12px rgba(0, 0, 0, 0.45),
                0 12px 24px -8px rgba(0, 0, 0, 0.25);
        }
        .phone-frame::before {
            content: '';
            position: absolute;
            left: -3px;
            top: 28%;
            width: 3px;
            height: 14%;
            border-radius: 2px 0 0 2px;
            background: #27272a;
            box-shadow: 0 52px 0 #27272a;
        }
        .phone-frame { position: relative; }
        .phone-screen {
            overflow: hidden;
            border-radius: 1.95rem;
            background: #fff;
            min-height: 340px;
        }
        .phone-island {
            width: 3.25rem;
            height: 0.95rem;
            border-radius: 9999px;
            background: #09090b;
            margin: 0 auto;
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.06);
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden overflow-y-auto bg-[#eef0f4] text-brand-charcoal antialiased selection:bg-brand-orange/20">
    {{ $slot }}
</body>
</html>
