<div x-data="{ open: false }" class="relative">
    <button
        @click="open = !open"
        @click.outside="open = false"
        class="relative flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:text-oe hover:bg-oe-soft transition-colors"
        title="Alerts"
    >
        <flux:icon name="bell" class="size-5" />
        @if($this->count > 0)
            <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold leading-none">
                {{ $this->count > 99 ? '99+' : $this->count }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-gray-200 dark:border-zinc-700 z-50"
        style="display:none"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-zinc-800">
            <span class="text-sm font-semibold text-gray-900 dark:text-white">Alerts</span>
            @if($this->count > 0)
                <flux:badge color="red" size="sm">{{ $this->count }} overdue</flux:badge>
            @else
                <flux:badge color="green" size="sm">All clear</flux:badge>
            @endif
        </div>

        <div class="max-h-72 overflow-y-auto divide-y divide-gray-50 dark:divide-zinc-800">
            @forelse($this->alerts as $alert)
                <div class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    <div class="mt-0.5 flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full
                        @if($alert['type'] === 'danger') bg-rose-100 text-rose-600
                        @elseif($alert['type'] === 'warning') bg-amber-100 text-amber-600
                        @else bg-oe-soft text-oe
                        @endif">
                        <flux:icon name="{{ $alert['icon'] }}" class="size-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $alert['message'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $alert['detail'] }}</p>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <flux:icon name="check-circle" class="size-8 mx-auto mb-2 text-emerald-400" />
                    <p class="text-sm text-gray-500">No alerts at this time</p>
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800">
            <a href="{{ route('credit.defaulters') }}" wire:navigate class="text-xs font-semibold text-oe dark:text-oe hover:underline">
                View all defaulters →
            </a>
        </div>
    </div>
</div>
