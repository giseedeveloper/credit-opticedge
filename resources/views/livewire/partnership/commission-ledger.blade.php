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
    <div>
        <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Commission Ledger</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Vendor commission tracking, payout status &amp; loan attribution</p>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-emerald-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-white/20"><flux:icon name="banknotes" class="size-4" /></div>
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">Total Paid Out</span>
            </div>
            <p class="text-2xl font-black">TZS {{ number_format($stats['paid_sum'], 0) }}</p>
            <p class="text-xs text-white/60 mt-1">{{ number_format($stats['paid_count']) }} paid records</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600"><flux:icon name="clock" class="size-4" /></div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pending</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['pending_sum'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($stats['pending_count']) }} awaiting payout</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-orange-500"><flux:icon name="calendar-days" class="size-4" /></div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">This Month</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['this_month_sum'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Commissions in {{ now()->format('M Y') }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-orange-500"><flux:icon name="queue-list" class="size-4" /></div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Records</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['total_count']) }}</p>
            <p class="text-xs text-gray-400 mt-1">All commission entries</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1">
            @foreach(['' => 'All', 'pending' => 'Pending', 'paid' => 'Paid'] as $val => $label)
            <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors
                        {{ $statusFilter === $val
                            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                {{ $label }}
                @if($val === 'pending' && $stats['pending_count'] > 0)
                <span class="ml-1 px-1.5 py-0.5 bg-amber-400 text-white text-[9px] font-bold rounded-full">{{ $stats['pending_count'] }}</span>
                @endif
            </button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <div class="w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Vendor, loan #, description…" icon="magnifying-glass" />
            </div>
            <flux:select wire:model.live="vendorFilter" class="w-48">
                <flux:select.option value="">All Vendors</flux:select.option>
                @foreach($vendors as $v)
                <flux:select.option :value="$v->id">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Loan / Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider hidden md:table-cell">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Commission</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Posted</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                    @forelse($records as $record)
                    @php
                        $isPaid    = $record->status === 'paid';
                        $rowBg     = $isPaid ? '' : 'bg-amber-50/30 dark:bg-amber-900/5';
                        $statusBadge = $isPaid
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                    @endphp
                    <tr wire:key="comm-{{ $record->id }}"
                        class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer {{ $rowBg }}"
                        wire:click="openDetail('{{ $record->id }}')">

                        {{-- Vendor --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-black flex-shrink-0">
                                    {{ strtoupper(substr($record->vendor?->name ?? '?', 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-xs">{{ $record->vendor?->name ?? '—' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $record->vendor?->code ?? '' }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Loan / Customer --}}
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs font-bold text-orange-500 dark:text-blue-400">
                                {{ $record->loan?->loan_number ?? '—' }}
                            </p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $record->loan?->customer?->full_name ?? '—' }}</p>
                        </td>

                        {{-- Description --}}
                        <td class="px-4 py-3 hidden md:table-cell">
                            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-[180px] truncate">
                                {{ $record->description ?? '—' }}
                            </p>
                        </td>

                        {{-- Rate --}}
                        <td class="px-4 py-3 text-right">
                            <span class="text-xs font-bold text-orange-500 dark:text-blue-400">
                                {{ $record->commission_rate ?? 0 }}%
                            </span>
                        </td>

                        {{-- Commission Amount --}}
                        <td class="px-4 py-3 text-right">
                            <p class="font-black text-gray-900 dark:text-white text-sm">
                                TZS {{ number_format($record->commission_amount, 0) }}
                            </p>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 text-center">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $statusBadge }}">
                                {{ ucfirst($record->status) }}
                            </span>
                        </td>

                        {{-- Posted Date --}}
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <p class="text-xs text-gray-500">{{ $record->posted_at?->format('d M Y') ?? '—' }}</p>
                            <p class="text-[10px] text-gray-400">{{ $record->posted_at?->diffForHumans() ?? '' }}</p>
                        </td>

                        {{-- Action --}}
                        <td class="px-4 py-3 text-right">
                            <button wire:click.stop="openDetail('{{ $record->id }}')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-orange-500 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                Details
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <flux:icon name="banknotes" class="size-12 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                            <p class="font-semibold text-gray-500">No commission records found</p>
                            <p class="text-xs text-gray-400 mt-1">
                                @if($search || $statusFilter || $vendorFilter) Try clearing your filters @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $records->links() }}</div>
        @endif
    </div>

    {{-- ══ COMMISSION DETAIL SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-lg bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">

            @if($this->detailRecord)
            @php
                $dr       = $this->detailRecord;
                $drIsPaid = $dr->status === 'paid';
            @endphp

            {{-- Header --}}
            <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-white/60 uppercase tracking-wider">Commission Entry</p>
                        <p class="text-3xl font-black mt-1">TZS {{ number_format($dr->commission_amount, 0) }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $drIsPaid ? 'bg-emerald-400/30 text-emerald-100' : 'bg-amber-400/30 text-amber-100' }}">
                                {{ ucfirst($dr->status) }}
                            </span>
                            <span class="text-white/50 text-xs">Rate: {{ $dr->commission_rate ?? 0 }}%</span>
                            @if($dr->posted_at)
                            <span class="text-white/50 text-xs">· {{ $dr->posted_at->format('d M Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @if($dr->description)
                <p class="text-white/70 text-xs mt-3 italic">"{{ $dr->description }}"</p>
                @endif
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Vendor Info --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Vendor</h3>
                    <div class="flex items-center gap-3 bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white font-black flex-shrink-0">
                            {{ strtoupper(substr($dr->vendor?->name ?? '?', 0, 2)) }}
                        </div>
                        <div class="flex-1">
                            <p class="font-bold text-gray-900 dark:text-white">{{ $dr->vendor?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $dr->vendor?->phone ?? '' }}{{ $dr->vendor?->code ? ' · '.$dr->vendor->code : '' }}</p>
                            @if($dr->vendor?->branch)
                            <p class="text-[10px] text-blue-500 mt-0.5 font-semibold">{{ $dr->vendor->branch->name }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Comm. Rate</p>
                            <p class="text-lg font-black text-orange-500 dark:text-blue-400">{{ $dr->vendor?->commission_rate ?? $dr->commission_rate ?? 0 }}%</p>
                        </div>
                    </div>
                </div>

                {{-- Loan Info --}}
                @if($dr->loan)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Linked Loan</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-mono text-sm font-bold text-orange-500 dark:text-blue-400">{{ $dr->loan->loan_number }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $dr->loan->customer?->full_name ?? '—' }}</p>
                                @if($dr->loan->customer?->phone)
                                <p class="text-[10px] text-gray-400">{{ $dr->loan->customer->phone }}</p>
                                @endif
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                {{ match($dr->loan->status) {
                                    'active'    => 'bg-emerald-100 text-emerald-700',
                                    'overdue'   => 'bg-red-100 text-red-700',
                                    'completed' => 'bg-sky-100 text-sky-700',
                                    'defaulted' => 'bg-rose-100 text-rose-700',
                                    default     => 'bg-zinc-100 text-zinc-500',
                                } }}">
                                {{ ucfirst($dr->loan->status) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-100 dark:border-zinc-700">
                            <div class="text-center">
                                <p class="text-[10px] text-gray-400 uppercase font-bold">Principal</p>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100 mt-0.5">{{ number_format($dr->loan->principal_amount, 0) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-gray-400 uppercase font-bold">Interest</p>
                                <p class="text-xs font-bold text-orange-500 mt-0.5">{{ $dr->loan->interest_rate }}% {{ $dr->loan->interest_type }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-gray-400 uppercase font-bold">Device</p>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100 mt-0.5 truncate">
                                    {{ $dr->loan->inventoryUnit?->phoneModel?->brand?->name ?? '—' }}
                                    {{ $dr->loan->inventoryUnit?->phoneModel?->name ?? '' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Transaction Info --}}
                @if($dr->transaction)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Linked Transaction</h3>
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800 rounded-xl px-4 py-3">
                        <div>
                            <p class="text-xs font-mono font-bold text-orange-500 dark:text-blue-400">{{ $dr->transaction->reference }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ ucfirst($dr->transaction->type ?? 'payment') }} · {{ $dr->transaction->channel ?? '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                                TZS {{ number_format($dr->transaction->amount, 0) }}
                            </p>
                            <p class="text-[10px] text-gray-400">{{ $dr->transaction->transacted_at?->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Audit Timestamps --}}
                <div class="grid grid-cols-2 gap-2 pt-3 border-t border-gray-100 dark:border-zinc-800">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Posted</p>
                        <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5">{{ $dr->posted_at?->format('d M Y, H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Created</p>
                        <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5">{{ $dr->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>

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
