@props([
    'href' => null,
    'wireNavigate' => true,
])

@php
    $href = $href ?? route('dashboard');
@endphp

<a href="{{ $href }}"
   @if ($wireNavigate) wire:navigate @endif
   {{ $attributes->class('inline-block cursor-pointer rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange/40 focus-visible:ring-offset-2 focus-visible:ring-offset-page') }}>
    <span class="sr-only">{{ __('Opticedge Credit') }}</span>
    <span class="font-bold tracking-tight leading-none lowercase" aria-hidden="true">
        <span class="text-brand-charcoal">opticedge</span><span class="text-brand-orange"> credit</span>
    </span>
</a>
