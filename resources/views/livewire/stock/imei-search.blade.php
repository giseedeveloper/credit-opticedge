<div class="flex flex-col gap-6" x-data="imeiSearch">

    {{-- Toast --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : 'bg-red-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2" style="display:none">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- Header --}}
    <div class="flex items-start gap-4">
        <x-fluent-icon name="magnifying-glass" size="lg" palette="blue" />
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">IMEI / Serial Search</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Search by IMEI or serial: finds the <strong class="text-gray-700 dark:text-gray-200">customer / loan application</strong> first, then <strong class="text-gray-700 dark:text-gray-200">inventory</strong> if the device is already in stock.</p>
        </div>
    </div>

    {{-- Hero Search Box --}}
    <div class="bg-gradient-to-br from-oe to-oe-hover rounded-2xl p-6 shadow-lg shadow-oe/20 relative overflow-hidden">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-6 -bottom-6 w-24 h-24 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative">
            <p class="text-white/70 text-xs font-semibold uppercase tracking-wider mb-3 flex items-center gap-2">
                <x-fluent-icon name="magnifying-glass" size="xs" />
                Enter IMEI or Serial Number
            </p>
            <div class="flex gap-3">
                <input
                    wire:model="query"
                    wire:keydown.enter="search"
                    @keydown.enter="$wire.search()"
                    placeholder="e.g. 358246095872143"
                    class="flex-1 bg-white/10 backdrop-blur border border-white/20 text-white placeholder-white/40 rounded-xl px-4 py-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-white/30"
                />
                <button wire:click="search" wire:loading.attr="disabled"
                        class="flex items-center gap-2 px-5 py-3 bg-white text-oe font-bold text-sm rounded-xl hover:bg-oe-soft transition-colors shadow disabled:opacity-60">
                    <svg wire:loading wire:target="search" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <flux:icon wire:loading.remove wire:target="search" name="magnifying-glass" class="size-4" />
                    <span wire:loading.remove wire:target="search">Search</span>
                    <span wire:loading wire:target="search">Searching…</span>
                </button>
            </div>
            <flux:error name="query" class="mt-2 text-rose-300 text-xs" />

            {{-- Recent Searches --}}
            @if(count($recentSearches))
            <div class="flex flex-wrap items-center gap-2 mt-4">
                <span class="text-white/50 text-xs">Recent:</span>
                @foreach($recentSearches as $recent)
                <button wire:click="searchRecent('{{ $recent }}')"
                        class="px-3 py-1 bg-white/10 hover:bg-white/20 border border-white/20 text-white/80 text-xs font-mono rounded-lg transition-colors">
                    {{ $recent }}
                </button>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Results --}}
    @if($searched)
        @if($result)
        @php
            $customer = $result;
            $phoneModel = $customer->phoneModel;
            $brand = $phoneModel?->brand;
            $loan = $customer->loans?->first();
            $loanStatus = $loan?->status;
            $loanStatusColor = match($loanStatus) {
                'active'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                'completed' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                'defaulted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                'overdue'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                default     => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
            };
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Main Customer Card --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-oe to-oe-hover px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white">
                                <flux:icon name="user" class="size-6" />
                            </div>
                            <div>
                                <p class="text-white/70 text-xs font-semibold uppercase tracking-wider">Customer</p>
                                <h2 class="text-xl font-bold text-white mt-0.5">{{ $customer->full_name ?? 'Unknown Customer' }}</h2>
                            </div>
                        </div>
                        @if($loanStatus)
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold {{ $loanStatusColor }}">
                            {{ ucfirst($loanStatus) }}
                        </span>
                        @endif
                    </div>

                    {{-- Contact --}}
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Contact</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Phone</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $customer->phone ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Email</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $customer->email ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Device Identity --}}
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device Identity</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach([['IMEI 1', $customer->imei_number], ['IMEI 2', $customer->imei_2], ['Serial No.', $customer->serial_number]] as [$label, $val])
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $label }}</p>
                                <div class="flex items-center justify-between mt-1 gap-2">
                                    <p class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $val ?? '—' }}</p>
                                    @if($val)
                                    <button @click="copy('{{ $val }}')" class="flex-shrink-0 p-1 rounded hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-400 hover:text-gray-600 transition-colors" title="Copy">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Device Info --}}
                    <div class="px-6 py-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device Info</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Brand</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $brand?->name ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Model</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $phoneModel?->name ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Loan Summary --}}
            <div class="space-y-4">
                @if($loan)
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-oe/20 dark:border-oe/25 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-oe to-oe text-white">
                        <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">Linked Loan</p>
                        <h3 class="text-lg font-bold mt-0.5 font-mono">{{ $loan->loan_number }}</h3>
                        <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold mt-1 {{ $loanStatusColor }}">
                            {{ ucfirst($loanStatus) }}
                        </span>
                    </div>
                    <div class="px-5 py-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Principal</span>
                            <span class="font-bold text-gray-800 dark:text-gray-100">TZS {{ number_format($loan->principal_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Outstanding</span>
                            <span class="font-bold text-red-600">TZS {{ number_format($loan->outstanding_balance ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Term</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $loan->loan_term_weeks ?? '—' }} weeks</span>
                        </div>
                        @if($loan->disbursed_at)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Disbursed</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ \Carbon\Carbon::parse($loan->disbursed_at)->format('d M Y') }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="px-5 pb-4">
                        <a href="{{ route('credit.panel') }}" wire:navigate
                           class="w-full flex items-center justify-center gap-2 py-2.5 text-sm font-bold rounded-xl bg-oe hover:bg-oe-hover text-white transition-colors">
                            View Loan →
                        </a>
                    </div>
                </div>
                @else
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-6 text-center">
                    <flux:icon name="document-minus" class="size-10 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-gray-500">No loan assigned</p>
                    <p class="text-xs text-gray-400 mt-1">This customer does not have an active loan.</p>
                </div>
                @endif

                {{-- Quick Actions --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Quick Actions</p>
                    <a href="{{ route('kyc.customers') }}" wire:navigate
                       class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-100 transition-colors">
                        <flux:icon name="arrow-left" class="size-4 flex-shrink-0" />
                        Back to Customers
                    </a>
                </div>
            </div>
        </div>

        @elseif($inventoryHit)
        @php
            $unit = $inventoryHit;
            $pm = $unit->phoneModel;
            $br = $pm?->brand;
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-amber-200 dark:border-amber-900/40 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white">
                                <flux:icon name="cube" class="size-6" />
                            </div>
                            <div>
                                <p class="text-white/80 text-xs font-semibold uppercase tracking-wider">Inventory only</p>
                                <h2 class="text-xl font-bold text-white mt-0.5">Stock unit matched</h2>
                                <p class="text-white/80 text-xs mt-1">No customer record yet for this IMEI/serial. After HQ registers the device in stock, it appears here; once a KYC application is submitted with the same IMEI, the customer card will show.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Identifiers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach([['IMEI 1', $unit->imei_1], ['IMEI 2', $unit->imei_2], ['Serial', $unit->serial_number]] as [$label, $val])
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $label }}</p>
                                <p class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1 truncate">{{ $val ?? '—' }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device & location</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Brand / model</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $br?->name ?? '—' }} — {{ $pm?->name ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Dealer counter</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $unit->dealer?->name ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Status</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $unit->status ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-4">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-5">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Use this when OpticEdge has added the IMEI to inventory (e.g. Samsung box) but the customer KYC record is not created or uses a different identifier until the application is filed.</p>
                </div>
            </div>
        </div>
        @else
        {{-- Not Found --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 p-16 text-center shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                <flux:icon name="exclamation-circle" class="size-8 text-red-500" />
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">No match</h3>
            <p class="text-gray-400 text-sm mt-2">No customer or inventory unit matched <span class="font-mono font-bold text-gray-600 dark:text-gray-300">{{ $query }}</span></p>
            <p class="text-gray-400 text-xs mt-1">Confirm the IMEI/serial, or check that the KYC application was submitted and the device was received into stock.</p>
        </div>
        @endif
    @endif

</div>

@script
<script data-navigate-once>
    document.addEventListener('alpine:init', () => {
        Alpine.data('imeiSearch', () => ({
            copy(text) {
                navigator.clipboard.writeText(text).then(() => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Copied: ' + text, type: 'success' } }));
                });
            },
        }));
    });
</script>
@endscript
