@props([
    'sidebar' => false,
])

<x-brand.wordmark {{ $attributes->class($sidebar ? '!text-sm' : '!text-lg') }} />
