@props([
    'href' => null,
    'wireNavigate' => true,
])

@php
    $href = $href ?? route('dashboard');
@endphp

<a href="{{ $href }}"
   @if ($wireNavigate) wire:navigate @endif
   {{ $attributes->class('inline-block cursor-pointer rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F58220]/40 focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--color-page)]') }}>
    <span class="sr-only">{{ __('Opticedge Credit') }}</span>
    <span class="font-semibold tracking-tight leading-none lowercase" aria-hidden="true">
        <span class="text-[#2D3748]">opticedge</span><span class="text-[#F58220]"> credit</span>
    </span>
</a>
