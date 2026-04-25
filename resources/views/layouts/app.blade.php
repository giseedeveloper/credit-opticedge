<x-layouts::app.sidebar :title="$title ?? null">
    {{-- id on flux:main root (grid-area:main) — do not wrap; extra wrapper breaks Flux shell CSS grid --}}
    <flux:main id="app-shell-main" class="relative min-h-screen flex-1 bg-page p-4 md:p-6 lg:p-8">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
