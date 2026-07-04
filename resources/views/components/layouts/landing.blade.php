<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Opticedge Credit is a secure device financing platform for KYC, face verification, payments, inventory control, field operations, and HQ approvals.">
    <title>{{ $title ?? config('app.name', 'Opticedge Credit') }}</title>

    <link rel="icon" href="{{ asset('opticedge_credit_website_logo.png') }}" type="image/png" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('opticedge_credit_website_logo.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        @keyframes landing-float {
            0%, 100% { transform: translateY(0) rotate(-7deg); }
            50% { transform: translateY(-12px) rotate(-5deg); }
        }
        @keyframes landing-float-delayed {
            0%, 100% { transform: translateY(0) rotate(9deg); }
            50% { transform: translateY(-10px) rotate(11deg); }
        }
        @keyframes landing-drift {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .animate-landing-float { animation: landing-float 7s ease-in-out infinite; }
        .animate-landing-float-delayed { animation: landing-float-delayed 8s ease-in-out infinite; }
        .animate-landing-drift { animation: landing-drift 6s ease-in-out infinite; }

        /* iPhone 15 Pro–style device chrome */
        .iphone-device {
            position: relative;
            filter: drop-shadow(0 32px 48px rgba(16, 52, 84, 0.28)) drop-shadow(0 12px 24px rgba(0, 0, 0, 0.18));
        }
        .iphone-btn {
            position: absolute;
            border-radius: 2px;
            background: linear-gradient(180deg, #aeaeb2 0%, #636366 45%, #48484a 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
            z-index: 2;
        }
        .iphone-btn--mute {
            left: -2px;
            top: 22%;
            width: 3px;
            height: 1.1rem;
        }
        .iphone-btn--vol-up {
            left: -2px;
            top: 30%;
            width: 3px;
            height: 2rem;
        }
        .iphone-btn--vol-down {
            left: -2px;
            top: 40%;
            width: 3px;
            height: 2rem;
        }
        .iphone-btn--power {
            right: -2px;
            top: 30%;
            width: 3px;
            height: 3.25rem;
        }
        .iphone-frame {
            position: relative;
            border-radius: 2.85rem;
            padding: 2px;
            background: linear-gradient(
                155deg,
                #d1d1d6 0%,
                #8e8e93 12%,
                #3a3a3c 38%,
                #1c1c1e 52%,
                #48484a 72%,
                #aeaeb2 100%
            );
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.22) inset,
                0 1px 0 rgba(255, 255, 255, 0.12) inset;
        }
        .iphone-bezel {
            border-radius: 2.75rem;
            padding: 0.55rem 0.45rem 0.7rem;
            background: linear-gradient(180deg, #1c1c1e 0%, #000 100%);
        }
        .iphone-screen {
            position: relative;
            overflow: hidden;
            border-radius: 2.35rem;
            background: #f2f5f9;
            min-height: 380px;
        }
        .iphone-screen--dark {
            background: #0a0f18;
        }
        .iphone-dynamic-island {
            position: absolute;
            top: 0.55rem;
            left: 50%;
            z-index: 40;
            width: 5.75rem;
            height: 1.45rem;
            transform: translateX(-50%);
            border-radius: 9999px;
            background: #000;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.06),
                inset 0 1px 2px rgba(0, 0, 0, 0.8);
        }
        .iphone-dynamic-island::after {
            content: '';
            position: absolute;
            right: 0.85rem;
            top: 50%;
            width: 0.45rem;
            height: 0.45rem;
            transform: translateY(-50%);
            border-radius: 9999px;
            background: radial-gradient(circle at 35% 35%, #1a2a3a, #050508);
            box-shadow: inset 0 0 2px rgba(0, 80, 160, 0.35);
        }
        .iphone-status {
            position: relative;
            z-index: 30;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1.15rem 0.35rem;
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .iphone-status--light {
            color: #fff;
        }
        .iphone-status--dark {
            color: #0e1826;
        }
        .iphone-icons {
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }
        .iphone-home-bar {
            margin: 0.45rem auto 0.25rem;
            width: 36%;
            height: 0.28rem;
            border-radius: 9999px;
            background: rgba(0, 0, 0, 0.22);
        }
        .iphone-home-bar--light {
            background: rgba(255, 255, 255, 0.55);
        }
        .iphone-tab-bar {
            display: flex;
            align-items: center;
            justify-content: space-around;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            background: rgba(255, 255, 255, 0.92);
            padding: 0.35rem 0.25rem 0.15rem;
            backdrop-filter: blur(12px);
        }
        .iphone-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.1rem;
        }
        .iphone-tab svg {
            width: 1rem;
            height: 1rem;
        }
        .iphone-tab span {
            font-size: 0.45rem;
            font-weight: 700;
        }
        .landing-hero-gradient {
            background: linear-gradient(135deg, #103454 0%, #1e5987 52%, #1f5a88 100%);
        }
        @keyframes scan-pulse {
            0%, 100% { opacity: 0.55; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.02); }
        }
        .animate-scan-pulse {
            animation: scan-pulse 2.4s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden overflow-y-auto bg-brand-surface text-brand-ink antialiased selection:bg-brand-blue/15">
    {{ $slot }}
</body>
</html>
