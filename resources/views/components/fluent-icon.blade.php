@props([
    'name',
    'palette' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'xs' => ['wrapper' => 'h-7 w-7 rounded-lg', 'icon' => 'size-3.5'],
        'sm' => ['wrapper' => 'h-8 w-8 rounded-lg', 'icon' => 'size-4'],
        'md' => ['wrapper' => 'h-10 w-10 rounded-xl', 'icon' => 'size-5'],
        'lg' => ['wrapper' => 'h-12 w-12 rounded-xl', 'icon' => 'size-6'],
        'xl' => ['wrapper' => 'h-16 w-16 rounded-2xl', 'icon' => 'size-8'],
    ];

    $themes = [
        'neutral' => 'border-slate-200/90 bg-slate-100 text-[#2D3748]',
        'brand' => 'border-[#F58220]/30 bg-[#F58220]/10 text-[#F58220]',
        'danger' => 'border-red-200/90 bg-red-50 text-red-600',
        'success' => 'border-emerald-200/90 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200/90 bg-amber-50 text-amber-700',
    ];

    $semanticByName = [
        'exclamation-triangle' => 'danger',
        'fire' => 'danger',
        'x-circle' => 'danger',
        'shield-exclamation' => 'danger',
        'check-circle' => 'success',
        'arrow-trending-up' => 'success',
        'heart' => 'danger',
    ];

    /** Map old colourful palette prop → brand system */
    $legacyPalette = [
        'rose' => 'danger',
        'orange' => 'brand',
        'amber' => 'warning',
        'emerald' => 'success',
        'teal' => 'success',
        'violet' => 'brand',
        'sky' => 'neutral',
        'blue' => 'brand',
        'slate' => 'neutral',
    ];

    $themeKey = 'neutral';

    if ($palette !== null && $palette !== '') {
        $themeKey = $legacyPalette[$palette] ?? 'brand';
    } elseif (isset($semanticByName[$name])) {
        $themeKey = $semanticByName[$name];
    }

    $themeClass = $themes[$themeKey];
    $resolvedSize = $sizes[$size] ?? $sizes['md'];
@endphp

<span
    {{ $attributes->class([
        'relative inline-flex shrink-0 items-center justify-center overflow-hidden border shadow-sm',
        $resolvedSize['wrapper'],
        $themeClass,
    ]) }}
    data-fluent-icon
>
    <flux:icon :name="$name" class="{{ implode(' ', ['relative z-10', $resolvedSize['icon']]) }}" />
</span>
