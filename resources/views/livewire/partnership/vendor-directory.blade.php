<div class="flex flex-col gap-6">

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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Vendor Directory</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Distribution partners, performance metrics &amp; commission tracking</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-900/40">
            <flux:icon name="building-storefront" class="size-4 text-purple-500" />
            <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ $stats['total'] }} vendors</span>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-purple-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-white/20"><flux:icon name="building-storefront" class="size-4" /></div>
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">Total Vendors</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-white/60 mt-1">{{ $stats['active'] }} active partners</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600"><flux:icon name="check-circle" class="size-4" /></div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['active']) }}</p>
            <p class="text-xs text-gray-400 mt-1">of {{ $stats['total'] }} registered vendors</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-sky-100 dark:bg-sky-900/30 text-sky-600"><flux:icon name="device-phone-mobile" class="size-4" /></div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Stock</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['total_stock']) }}</p>
            <p class="text-xs text-gray-400 mt-1">inventory units system-wide</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600"><flux:icon name="banknotes" class="size-4" /></div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Portfolio</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['loan_portfolio'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Active + overdue loans</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1">
            @foreach(['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $val => $label)
            <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors
                        {{ $statusFilter === $val
                            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <div class="w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Name, phone, email, code…" icon="magnifying-glass" />
            </div>
            <flux:select wire:model.live="branchFilter" class="w-44">
                <flux:select.option value="">All Branches</flux:select.option>
                @foreach($branches as $b)
                <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Vendor Cards Grid --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($vendors as $vendor)
        @php
            $isActive     = ($vendor->status ?? 'active') === 'active';
            $walletBal    = (float) ($vendor->wallet?->balance ?? 0);
            $totalEarned  = (float) ($vendor->wallet?->total_earned ?? 0);
            $loanValue    = (float) ($vendor->loans_sum_principal_amount ?? 0);
        @endphp
        <div wire:key="vendor-{{ $vendor->id }}"
             wire:click="openDetail('{{ $vendor->id }}')"
             class="group bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 p-5 shadow-sm
                    hover:shadow-lg hover:border-purple-200 dark:hover:border-purple-800 hover:-translate-y-0.5
                    transition-all duration-200 cursor-pointer">

            {{-- Card Header --}}
            <div class="flex items-start gap-3 mb-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white font-black text-sm flex-shrink-0 shadow-md shadow-purple-900/20">
                    {{ strtoupper(substr($vendor->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-900 dark:text-white truncate">{{ $vendor->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $vendor->code ?? '—' }} · {{ $vendor->phone ?? '—' }}</p>
                    @if($vendor->branch)
                    <p class="text-[10px] text-purple-500 dark:text-purple-400 mt-0.5 font-semibold">{{ $vendor->branch->name }}</p>
                    @endif
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0
                    {{ $isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $isActive ? 'Active' : 'Inactive' }}
                </span>
            </div>

            {{-- Metrics Grid --}}
            <div class="grid grid-cols-3 gap-2 mb-3">
                <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Stock</p>
                    <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">{{ $vendor->inventory_units_count }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Loans</p>
                    <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">{{ $vendor->loans_count }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Rate</p>
                    <p class="text-base font-black text-purple-600 dark:text-purple-400 mt-0.5">{{ $vendor->commission_rate ?? 0 }}%</p>
                </div>
            </div>

            {{-- Wallet Balance --}}
            @if($walletBal > 0 || $totalEarned > 0)
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-3 border border-purple-100 dark:border-purple-900/30 mb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] text-purple-500 uppercase font-bold">Wallet Balance</p>
                        <p class="text-sm font-black text-purple-700 dark:text-purple-300">TZS {{ number_format($walletBal, 0) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Total Earned</p>
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-300">TZS {{ number_format($totalEarned, 0) }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Contact Info --}}
            <div class="space-y-1">
                @if($vendor->email)
                <p class="text-[10px] text-gray-400 flex items-center gap-1.5 truncate">
                    <flux:icon name="envelope" class="size-3 flex-shrink-0" />{{ $vendor->email }}
                </p>
                @endif
                @if($vendor->address)
                <p class="text-[10px] text-gray-400 flex items-center gap-1.5 truncate">
                    <flux:icon name="map-pin" class="size-3 flex-shrink-0" />{{ $vendor->address }}
                </p>
                @endif
            </div>

            {{-- Hover cta --}}
            <div class="mt-3 pt-3 border-t border-gray-50 dark:border-zinc-800 flex items-center justify-between">
                <span class="text-[10px] text-gray-300 dark:text-zinc-600 group-hover:text-purple-400 transition-colors font-semibold">Click to view details</span>
                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-zinc-600 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </div>
        @empty
        <div class="md:col-span-3 flex flex-col items-center justify-center py-16">
            <flux:icon name="building-storefront" class="size-16 text-gray-300 dark:text-zinc-600 mb-3" />
            <p class="font-semibold text-gray-500">No vendors found</p>
            <p class="text-xs text-gray-400 mt-1">
                @if($search || $statusFilter || $branchFilter)
                    Try clearing your filters
                @else
                    No vendors registered yet
                @endif
            </p>
        </div>
        @endforelse
    </div>

    @if($vendors->hasPages())
    <div>{{ $vendors->links() }}</div>
    @endif

    {{-- ══ VENDOR DETAIL SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-xl bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">

            @if($this->detailVendor)
            @php
                $dv          = $this->detailVendor;
                $dvActive    = ($dv->status ?? 'active') === 'active';
                $dvWallet    = $dv->wallet;
                $dvBalance   = (float) ($dvWallet?->balance ?? 0);
                $dvEarned    = (float) ($dvWallet?->total_earned ?? 0);
                $dvWithdrawn = (float) ($dvWallet?->total_withdrawn ?? 0);
                $dvLoanValue = (float) ($dv->loans_sum_principal_amount ?? 0);
            @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-purple-600 to-indigo-700 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white font-black text-lg flex-shrink-0">
                        {{ strtoupper(substr($dv->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $dvActive ? 'bg-emerald-400/30 text-emerald-100' : 'bg-red-400/30 text-red-100' }}">
                                {{ $dvActive ? 'Active' : 'Inactive' }}
                            </span>
                            @if($dv->code)
                            <span class="text-white/50 text-[10px] font-mono">{{ $dv->code }}</span>
                            @endif
                        </div>
                        <h2 class="text-xl font-black">{{ $dv->name }}</h2>
                        <p class="text-white/60 text-xs mt-0.5">{{ $dv->branch?->name ?? 'No branch' }}</p>
                    </div>
                </div>
                <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Wallet Strip --}}
            <div class="grid grid-cols-3 divide-x divide-gray-100 dark:divide-zinc-700 bg-gray-50 dark:bg-zinc-800/60 border-b border-gray-100 dark:border-zinc-700">
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Wallet Balance</p>
                    <p class="text-base font-black text-purple-600 dark:text-purple-400 mt-0.5">TZS {{ number_format($dvBalance, 0) }}</p>
                </div>
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Total Earned</p>
                    <p class="text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5">TZS {{ number_format($dvEarned, 0) }}</p>
                </div>
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Withdrawn</p>
                    <p class="text-base font-black text-amber-600 dark:text-amber-400 mt-0.5">TZS {{ number_format($dvWithdrawn, 0) }}</p>
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Contact & Identity --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Contact & Identity</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Phone</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dv->phone ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Email</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5 truncate">{{ $dv->email ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">TIN Number</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5 font-mono">{{ $dv->tin_number ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Commission Rate</p>
                            <p class="text-sm font-black text-purple-600 dark:text-purple-400 mt-0.5">{{ $dv->commission_rate ?? 0 }}%</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 col-span-2">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Address</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100 mt-0.5">{{ $dv->address ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Branch & Owner --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Branch & Management</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Branch</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dv->branch?->name ?? '—' }}</p>
                            @if($dv->branch?->region)
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $dv->branch->region }}</p>
                            @endif
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Account Owner</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dv->ownerUser?->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Performance --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Performance</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Stock Units</p>
                            <p class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $dv->inventory_units_count }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Total Loans</p>
                            <p class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $dv->loans_count }}</p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-purple-500 uppercase font-bold">Loan Value</p>
                            <p class="text-sm font-black text-purple-700 dark:text-purple-300 mt-0.5">{{ number_format($dvLoanValue, 0) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Recent Commission Ledger --}}
                @if($dv->commissionLedgers->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">
                        Recent Commissions
                        <span class="ml-1 px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-600 text-[9px] font-bold">{{ $dv->commissionLedgers->count() }}</span>
                    </h3>
                    <div class="space-y-1.5">
                        @foreach($dv->commissionLedgers as $ledger)
                        @php
                            $lBadge = match($ledger->status) {
                                'paid'    => 'bg-emerald-100 text-emerald-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                default   => 'bg-zinc-100 text-zinc-600',
                            };
                        @endphp
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800 rounded-xl px-3 py-2.5">
                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $ledger->description ?? 'Commission' }}
                                </p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $ledger->posted_at?->format('d M Y') ?? '—' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-purple-600 dark:text-purple-400">
                                    TZS {{ number_format($ledger->commission_amount, 0) }}
                                </p>
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold {{ $lBadge }}">{{ ucfirst($ledger->status) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Recent Loans --}}
                @if($dv->loans->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Recent Loans</h3>
                    <div class="space-y-1.5">
                        @foreach($dv->loans as $loan)
                        @php
                            $loanBadge = match($loan->status) {
                                'active'    => 'bg-emerald-100 text-emerald-700',
                                'overdue'   => 'bg-red-100 text-red-700',
                                'completed' => 'bg-sky-100 text-sky-700',
                                'defaulted' => 'bg-rose-100 text-rose-700',
                                default     => 'bg-zinc-100 text-zinc-600',
                            };
                        @endphp
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800 rounded-xl px-3 py-2.5">
                            <div>
                                <p class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $loan->loan_number }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $loan->customer?->full_name ?? '—' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">TZS {{ number_format($loan->principal_amount, 0) }}</p>
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold {{ $loanBadge }}">{{ ucfirst($loan->status) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800">
                <button wire:click="closeDetail"
                        class="w-full py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

</div>
