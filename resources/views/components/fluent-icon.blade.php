@props([
    'name',
    'palette' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'xs' => ['wrapper' => 'h-7 w-7 rounded-lg', 'icon' => 'size-3.5', 'spark' => 'h-2.5 w-2.5'],
        'sm' => ['wrapper' => 'h-8 w-8 rounded-xl', 'icon' => 'size-4', 'spark' => 'h-3 w-3'],
        'md' => ['wrapper' => 'h-10 w-10 rounded-2xl', 'icon' => 'size-5', 'spark' => 'h-3.5 w-3.5'],
        'lg' => ['wrapper' => 'h-12 w-12 rounded-[1.1rem]', 'icon' => 'size-6', 'spark' => 'h-4 w-4'],
        'xl' => ['wrapper' => 'h-16 w-16 rounded-[1.35rem]', 'icon' => 'size-8', 'spark' => 'h-5 w-5'],
    ];

    $palettes = [
        'orange' => ['from' => 'from-orange-400', 'to' => 'to-orange-600', 'ring' => 'ring-orange-100/80', 'spark' => 'bg-amber-300', 'shadow' => 'shadow-orange-500/25'],
        'sky' => ['from' => 'from-sky-400', 'to' => 'to-blue-600', 'ring' => 'ring-sky-100/80', 'spark' => 'bg-cyan-300', 'shadow' => 'shadow-sky-500/25'],
        'blue' => ['from' => 'from-blue-400', 'to' => 'to-indigo-600', 'ring' => 'ring-blue-100/80', 'spark' => 'bg-sky-300', 'shadow' => 'shadow-blue-500/25'],
        'emerald' => ['from' => 'from-emerald-400', 'to' => 'to-teal-600', 'ring' => 'ring-emerald-100/80', 'spark' => 'bg-lime-300', 'shadow' => 'shadow-emerald-500/25'],
        'teal' => ['from' => 'from-teal-400', 'to' => 'to-cyan-600', 'ring' => 'ring-teal-100/80', 'spark' => 'bg-emerald-300', 'shadow' => 'shadow-teal-500/25'],
        'amber' => ['from' => 'from-amber-400', 'to' => 'to-orange-500', 'ring' => 'ring-amber-100/80', 'spark' => 'bg-yellow-300', 'shadow' => 'shadow-amber-500/25'],
        'rose' => ['from' => 'from-rose-400', 'to' => 'to-red-600', 'ring' => 'ring-rose-100/80', 'spark' => 'bg-orange-300', 'shadow' => 'shadow-rose-500/25'],
        'violet' => ['from' => 'from-violet-400', 'to' => 'to-fuchsia-600', 'ring' => 'ring-violet-100/80', 'spark' => 'bg-pink-300', 'shadow' => 'shadow-violet-500/25'],
        'slate' => ['from' => 'from-slate-500', 'to' => 'to-slate-700', 'ring' => 'ring-slate-100/80', 'spark' => 'bg-sky-200', 'shadow' => 'shadow-slate-500/25'],
    ];

    $paletteMap = [
        'home' => 'orange',
        'chart-bar-square' => 'orange',
        'credit-card' => 'orange',
        'banknotes' => 'emerald',
        'clipboard-document-list' => 'sky',
        'archive-box' => 'sky',
        'tag' => 'violet',
        'magnifying-glass' => 'blue',
        'arrows-right-left' => 'amber',
        'server-stack' => 'blue',
        'shield-check' => 'teal',
        'clock' => 'amber',
        'user-group' => 'sky',
        'user-plus' => 'teal',
        'exclamation-triangle' => 'rose',
        'calendar-days' => 'blue',
        'calculator' => 'violet',
        'building-storefront' => 'amber',
        'chart-bar' => 'orange',
        'chat-bubble-left-right' => 'teal',
        'eye' => 'sky',
        'shield-exclamation' => 'rose',
        'users' => 'sky',
        'user-circle' => 'sky',
        'key' => 'violet',
        'heart' => 'rose',
        'identification' => 'blue',
        'device-phone-mobile' => 'sky',
        'building-office' => 'blue',
        'squares-2x2' => 'violet',
        'circle-stack' => 'blue',
        'truck' => 'amber',
        'check-badge' => 'emerald',
        'arrow-trending-up' => 'emerald',
        'plus-circle' => 'orange',
        'document-text' => 'blue',
        'sun' => 'amber',
        'chat-bubble-oval-left-ellipsis' => 'teal',
        'fire' => 'rose',
        'check-circle' => 'emerald',
        'information-circle' => 'blue',
        'queue-list' => 'violet',
        'envelope' => 'sky',
        'map-pin' => 'rose',
        'x-circle' => 'rose',
    ];

    $resolvedSize = $sizes[$size] ?? $sizes['md'];
    $resolvedPalette = $palette ?? ($paletteMap[$name] ?? 'orange');
    $theme = $palettes[$resolvedPalette] ?? $palettes['orange'];
@endphp

<span
    {{ $attributes->class([
        'relative inline-flex shrink-0 items-center justify-center overflow-hidden',
        $resolvedSize['wrapper'],
    ]) }}
    data-fluent-icon
>
    <span class="{{ implode(' ', ['absolute inset-0 bg-gradient-to-br', $theme['from'], $theme['to'], 'shadow-lg', $theme['shadow']]) }}"></span>
    <span class="{{ implode(' ', ['absolute inset-0 rounded-[inherit] ring-1 ring-inset', $theme['ring']]) }}"></span>
    <span class="{{ implode(' ', ['absolute -right-0.5 -top-0.5 rounded-full border border-white/80 opacity-90', $resolvedSize['spark'], $theme['spark']]) }}"></span>
    <flux:icon :name="$name" class="{{ implode(' ', ['relative z-10 text-white drop-shadow-[0_1px_2px_rgba(15,23,42,0.28)]', $resolvedSize['icon']]) }}" />
</span>
