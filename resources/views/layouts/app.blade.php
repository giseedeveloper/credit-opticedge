<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="min-h-screen flex-1 bg-page p-4 md:p-6 lg:p-8">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
