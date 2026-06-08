<div x-data="{ open: false }" class="relative" wire:poll.60s>
    <button
        @click="open = !open"
        @click.outside="open = false"
        class="relative flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:text-oe hover:bg-oe-soft transition-colors"
        title="System notifications"
        aria-label="Open system notifications"
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
        class="absolute right-0 top-full mt-2 w-[24rem] sm:w-[28rem] bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-zinc-700 z-50 overflow-hidden"
        style="display:none"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-oe-soft/60 to-transparent dark:from-zinc-800/80">
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">System Notifications</p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Live tracking · loans · KYC · devices</p>
            </div>
            @if($this->count > 0)
                <flux:badge color="red" size="sm">{{ $this->count }} action items</flux:badge>
            @else
                <flux:badge color="green" size="sm">All clear</flux:badge>
            @endif
        </div>

        <div class="max-h-[26rem] overflow-y-auto divide-y divide-gray-50 dark:divide-zinc-800">
            @forelse($this->alerts as $alert)
                <a
                    href="{{ $alert['url'] }}"
                    wire:navigate
                    class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-800/80 transition-colors"
                >
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-xl
                            @if($alert['type'] === 'danger') bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300
                            @elseif($alert['type'] === 'warning') bg-amber-100 text-amber-600 dark:bg-amber-950/40 dark:text-amber-300
                            @elseif($alert['type'] === 'success') bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300
                            @else bg-oe-soft text-oe dark:bg-zinc-800 dark:text-oe
                            @endif">
                            <flux:icon name="{{ $alert['icon'] }}" class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $alert['title'] }}</p>
                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-500 dark:bg-zinc-800 dark:text-gray-400">
                                    {{ str_replace('_', ' ', $alert['category']) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">{{ $alert['summary'] }}</p>

                            @if(filled($alert['customer_name']) || filled($alert['device']) || filled($alert['imei']) || filled($alert['customer_phone']) || filled($alert['customer_email']) || filled($alert['nida_number']))
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @if(filled($alert['customer_name']))
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                                            {{ $alert['customer_name'] }}
                                        </span>
                                    @endif
                                    @if(filled($alert['customer_phone']))
                                        <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 dark:bg-sky-950/30 dark:text-sky-300">
                                            {{ $alert['customer_phone'] }}
                                        </span>
                                    @endif
                                    @if(filled($alert['customer_email']))
                                        <span class="inline-flex items-center rounded-md bg-violet-50 px-2 py-0.5 text-[10px] font-medium text-violet-700 dark:bg-violet-950/30 dark:text-violet-300">
                                            {{ $alert['customer_email'] }}
                                        </span>
                                    @endif
                                    @if(filled($alert['nida_number']))
                                        <span class="inline-flex items-center rounded-md bg-orange-50 px-2 py-0.5 text-[10px] font-medium text-orange-700 dark:bg-orange-950/30 dark:text-orange-300">
                                            ID {{ $alert['nida_number'] }}
                                        </span>
                                    @endif
                                    @if(filled($alert['loan_number']))
                                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                            {{ $alert['loan_number'] }}
                                        </span>
                                    @endif
                                    @if(filled($alert['imei']))
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            IMEI {{ $alert['imei'] }}
                                        </span>
                                    @endif
                                    @if(filled($alert['device']))
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700 dark:bg-zinc-800 dark:text-gray-200">
                                            {{ \Illuminate\Support\Str::limit($alert['device'], 28) }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-2">{{ $alert['occurred_human'] }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <flux:icon name="check-circle" class="size-9 mx-auto mb-3 text-emerald-400" />
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">No active alerts</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Loan, KYC, and tracking events will appear here.</p>
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800 flex items-center justify-between gap-3">
            <a href="{{ route('credit.defaulters') }}" wire:navigate class="text-xs font-semibold text-oe hover:underline">
                Defaulters
            </a>
            <a href="{{ route('kyc.pending') }}" wire:navigate class="text-xs font-semibold text-oe hover:underline">
                KYC queue
            </a>
            @can('reports.view')
                <a href="{{ route('audits.logs') }}" wire:navigate class="text-xs font-semibold text-oe hover:underline">
                    Audit logs
                </a>
            @endcan
        </div>
    </div>
</div>
