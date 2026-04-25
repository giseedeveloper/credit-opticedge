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
        <div class="flex items-start gap-4">
            <x-fluent-icon name="exclamation-triangle" size="lg" palette="rose" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Defaulters</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Overdue &amp; defaulted loans requiring immediate action</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40">
            <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
            <span class="text-sm font-bold text-red-600 dark:text-red-400">{{ $stats['total'] }} accounts at risk</span>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Defaulters --}}
        <div class="bg-gradient-to-br from-red-600 to-rose-700 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-red-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="exclamation-triangle" size="sm" palette="rose" />
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">Defaulters</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-white/60 mt-1">Overdue + defaulted accounts</p>
        </div>
        {{-- Total Outstanding --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="banknotes" size="sm" palette="rose" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Outstanding</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['outstanding'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Total unpaid balances</p>
        </div>
        {{-- Total Penalty --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="exclamation-triangle" size="sm" palette="amber" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Penalties</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['penalty'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Accumulated late fees</p>
        </div>
        {{-- Total Exposure --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="fire" size="sm" palette="orange" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Exposure</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['exposure'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Outstanding + penalties</p>
        </div>
    </div>

    {{-- Risk Filter Tabs + Search --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1 flex-wrap">
            @foreach(['' => 'All Risk', 'moderate' => 'Moderate', 'high' => 'High', 'critical' => 'Critical'] as $key => $label)
            <button wire:click="$set('riskFilter', '{{ $key }}')"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors
                        {{ $riskFilter === $key
                            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                @if($key === 'moderate')
                    <span class="inline-block w-2 h-2 rounded-full bg-yellow-400 mr-1.5 align-middle"></span>
                @elseif($key === 'high')
                    <span class="inline-block w-2 h-2 rounded-full bg-oe mr-1.5 align-middle"></span>
                @elseif($key === 'critical')
                    <span class="inline-block w-2 h-2 rounded-full bg-red-600 mr-1.5 align-middle"></span>
                @endif
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <div class="w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Loan #, name, phone…" icon="magnifying-glass" />
            </div>
        </div>
    </div>

    {{-- Risk Legend --}}
    <div class="flex items-center gap-4 text-xs text-gray-400">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-yellow-400"></span> Moderate: 1–30 days overdue</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-oe"></span> High: 31–60 days overdue</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-600"></span> Critical: 60+ days overdue</span>
    </div>

    {{-- Defaulters Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-red-50 dark:bg-red-900/10 border-b border-red-100 dark:border-red-900/20">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-red-400 uppercase tracking-wider">Loan</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-red-400 uppercase tracking-wider">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-red-400 uppercase tracking-wider hidden md:table-cell">Device</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-red-400 uppercase tracking-wider">Outstanding</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-red-400 uppercase tracking-wider hidden lg:table-cell">Days Overdue</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-red-400 uppercase tracking-wider hidden md:table-cell">Penalty</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-red-400 uppercase tracking-wider">Recovery</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-red-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                @forelse($defaulters as $loan)
                @php
                    $daysOverdue  = $loan->due_date ? (int) $loan->due_date->diffInDays(now()) : 0;
                    $outstanding  = (float) ($loan->outstanding_balance ?? 0);
                    $penalty      = (float) ($loan->penalty_amount ?? 0);

                    if ($daysOverdue <= 30) {
                        $riskLabel = 'Moderate';
                        $riskDot   = 'bg-yellow-400';
                        $riskText  = 'text-yellow-700 dark:text-yellow-300';
                        $riskBg    = 'bg-yellow-100 dark:bg-yellow-900/30';
                        $rowHover  = 'hover:bg-yellow-50/60 dark:hover:bg-yellow-900/10';
                    } elseif ($daysOverdue <= 60) {
                        $riskLabel = 'High';
                        $riskDot   = 'bg-oe';
                        $riskText  = 'text-oe-hover dark:text-orange-300';
                        $riskBg    = 'bg-oe/15 dark:bg-orange-900/30';
                        $rowHover  = 'hover:bg-oe-soft/60 dark:hover:bg-orange-900/10';
                    } else {
                        $riskLabel = 'Critical';
                        $riskDot   = 'bg-red-600';
                        $riskText  = 'text-red-700 dark:text-red-300';
                        $riskBg    = 'bg-red-100 dark:bg-red-900/30';
                        $rowHover  = 'hover:bg-red-50/60 dark:hover:bg-red-900/10';
                    }
                @endphp
                <tr wire:key="def-{{ $loan->id }}"
                    class="transition-colors cursor-pointer {{ $rowHover }}"
                    wire:click="openDetail('{{ $loan->id }}')">

                    <td class="px-4 py-3.5">
                        <p class="font-mono text-xs font-bold text-red-600 dark:text-red-400">{{ $loan->loan_number }}</p>
                        @if($loan->dealer)
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $loan->dealer->name }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center text-white text-[10px] font-black flex-shrink-0">
                                {{ strtoupper(substr($loan->customer?->first_name ?? '?', 0, 1).substr($loan->customer?->last_name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $loan->customer?->full_name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $loan->customer?->phone }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3.5 hidden md:table-cell">
                        @if($loan->inventoryUnit?->phoneModel)
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $loan->inventoryUnit->phoneModel->brand?->name }} {{ $loan->inventoryUnit->phoneModel->name }}
                        </p>
                        <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $loan->inventoryUnit->serial_number ?? '' }}</p>
                        @elseif($loan->customer?->phoneModel)
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $loan->customer->phoneModel->brand?->name }} {{ $loan->customer->phoneModel->name }}
                        </p>
                        <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $loan->customer->imei_number ?? $loan->customer->serial_number ?? '' }}</p>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3.5">
                        <p class="text-sm font-black text-red-600 dark:text-red-400">
                            TZS {{ number_format($outstanding, 0) }}
                        </p>
                        @if($penalty > 0)
                        <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5 font-semibold">
                            +{{ number_format($penalty, 0) }} penalty
                        </p>
                        @endif
                    </td>

                    <td class="px-4 py-3.5 hidden lg:table-cell">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $riskBg }} {{ $riskText }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $riskDot }}"></span>
                                {{ $riskLabel }}
                            </span>
                        </div>
                        <p class="text-xs font-semibold {{ $riskText }} mt-1">{{ $daysOverdue }} days late</p>
                    </td>

                    <td class="px-4 py-3.5 hidden md:table-cell">
                        @if($penalty > 0)
                        <p class="text-sm font-semibold text-amber-600 dark:text-amber-400">
                            TZS {{ number_format($penalty, 0) }}
                        </p>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $loan->status === 'defaulted'
                                ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                : 'bg-oe/15 text-oe-hover dark:bg-orange-900/30 dark:text-orange-300' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $loan->status === 'defaulted' ? 'bg-red-500' : 'bg-oe' }}"></span>
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>

                    <td class="px-4 py-3.5 text-right">
                        <button wire:click.stop="openDetail('{{ $loan->id }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Details
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-16 text-center">
                        <flux:icon name="check-circle" class="size-14 mx-auto mb-3 text-emerald-400" />
                        <p class="text-gray-600 dark:text-gray-300 font-semibold">No defaulters found</p>
                        <p class="text-gray-400 text-xs mt-1">
                            @if($search || $riskFilter)
                                Try clearing your filters
                            @else
                                All loan accounts are in good standing
                            @endif
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($defaulters->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $defaulters->links() }}</div>
        @endif
    </div>

    {{-- ══ DEFAULTER DETAIL SLIDE-OVER ══ --}}
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

            @if($this->detailLoan)
            @php
                $dl           = $this->detailLoan;
                $dlDays       = $dl->due_date ? (int) $dl->due_date->diffInDays(now()) : 0;
                $dlOutstanding = (float) ($dl->outstanding_balance ?? 0);
                $dlPenalty    = (float) ($dl->penalty_amount ?? 0);
                $dlPrincipal  = (float) $dl->principal_amount;
                $dlPaid       = (float) ($dl->amount_paid ?? 0);
                $dlTotalPayable = (float) ($dl->total_payable ?: $dlPrincipal);
                $dlProgress   = $dlTotalPayable > 0 ? min(100, round(($dlPaid / $dlTotalPayable) * 100)) : 0;

                if ($dlDays <= 30) {
                    $dlRiskLabel = 'Moderate Risk';
                    $dlRiskGrad  = 'from-yellow-500 to-amber-600';
                } elseif ($dlDays <= 60) {
                    $dlRiskLabel = 'High Risk';
                    $dlRiskGrad  = 'from-oe to-red-600';
                } else {
                    $dlRiskLabel = 'Critical Risk';
                    $dlRiskGrad  = 'from-red-600 to-rose-800';
                }
            @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r {{ $dlRiskGrad }} text-white">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 rounded-full bg-white/20 text-xs font-bold">{{ $dlRiskLabel }}</span>
                        <span class="text-white/70 text-xs font-semibold">{{ $dlDays }} days overdue</span>
                    </div>
                    <h2 class="text-xl font-black font-mono">{{ $dl->loan_number }}</h2>
                    <p class="text-white/70 text-xs mt-1">Due: {{ $dl->due_date?->format('d M Y') ?? '—' }}</p>
                </div>
                <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Exposure Strip --}}
            <div class="grid grid-cols-3 divide-x divide-gray-100 dark:divide-zinc-700 bg-gray-50 dark:bg-zinc-800/60 border-b border-gray-100 dark:border-zinc-700">
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Outstanding</p>
                    <p class="text-base font-black text-red-600 dark:text-red-400 mt-0.5">TZS {{ number_format($dlOutstanding, 0) }}</p>
                </div>
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Penalty</p>
                    <p class="text-base font-black text-amber-600 dark:text-amber-400 mt-0.5">TZS {{ number_format($dlPenalty, 0) }}</p>
                </div>
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Total Owed</p>
                    <p class="text-base font-black text-rose-700 dark:text-rose-400 mt-0.5">TZS {{ number_format($dlOutstanding + $dlPenalty, 0) }}</p>
                </div>
            </div>

            {{-- Repayment Progress --}}
            <div class="px-6 py-3 bg-gray-50 dark:bg-zinc-800/40 border-b border-gray-100 dark:border-zinc-700">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-semibold text-gray-500">Repayment Progress</span>
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ $dlProgress }}%</span>
                </div>
                <div class="h-2 rounded-full bg-gray-200 dark:bg-zinc-700 overflow-hidden">
                    <div class="h-full rounded-full bg-red-500" style="width: {{ $dlProgress }}%"></div>
                </div>
                <div class="flex justify-between mt-1 text-[10px] text-gray-400">
                    <span>Paid: TZS {{ number_format($dlPaid, 0) }}</span>
                    <span>Total payable: TZS {{ number_format($dlTotalPayable, 0) }}</span>
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Customer --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Customer</h3>
                    <div class="flex items-center gap-3 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center text-white text-xs font-black flex-shrink-0">
                            {{ strtoupper(substr($dl->customer?->first_name ?? '?', 0, 1).substr($dl->customer?->last_name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $dl->customer?->full_name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $dl->customer?->phone }}</p>
                            @if($dl->customer?->address)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $dl->customer->address }}</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[10px] text-gray-400">Dealer</p>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $dl->customer?->dealer?->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Device (inventory unit when linked, otherwise KYC / customer device) --}}
                @if($dl->inventoryUnit || $dl->customer?->phoneModel)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-400 to-slate-600 flex items-center justify-center flex-shrink-0">
                            <flux:icon name="device-phone-mobile" class="size-5 text-white" />
                        </div>
                        <div>
                            @if($dl->inventoryUnit?->phoneModel)
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $dl->inventoryUnit->phoneModel->brand?->name }} {{ $dl->inventoryUnit->phoneModel->name }}
                            </p>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">
                                IMEI: {{ $dl->inventoryUnit->imei_1 ?? $dl->inventoryUnit->serial_number ?? '—' }}
                            </p>
                            @else
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $dl->customer->phoneModel->brand?->name }} {{ $dl->customer->phoneModel->name }}
                            </p>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">
                                IMEI: {{ $dl->customer->imei_number ?? $dl->customer->serial_number ?? '—' }}
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Loan Terms --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Loan Terms</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Principal</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">TZS {{ number_format($dlPrincipal, 0) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Deposit Paid</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">TZS {{ number_format($dl->deposit_paid ?? 0, 0) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Interest</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $dl->interest_rate ?? 0 }}% <span class="text-xs font-normal text-gray-400">{{ $dl->interest_type }}</span></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Duration</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $dl->duration_weeks ?? '—' }} weeks</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Frequency</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ ucfirst($dl->repayment_frequency ?? '—') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Disbursed</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $dl->disbursed_at?->format('d M Y') ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Administration --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Administration</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Dealer</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dl->dealer?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Disbursed By</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dl->disbursedBy?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Approved By</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dl->approvedBy?->name ?? '—' }}</p>
                        </div>
                    </div>
                    @if($dl->notes)
                    <div class="mt-2 bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 border border-amber-100 dark:border-amber-800/40">
                        <p class="text-[10px] text-amber-600 uppercase font-bold mb-1">Notes</p>
                        <p class="text-xs text-gray-700 dark:text-gray-300">{{ $dl->notes }}</p>
                    </div>
                    @endif
                </div>

                {{-- Recovery Tickets --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">
                        Recovery Tickets
                        @if($dl->recoveryTickets->count())
                        <span class="ml-1 px-1.5 py-0.5 rounded-full bg-red-100 text-red-600 text-[9px] font-bold">{{ $dl->recoveryTickets->count() }}</span>
                        @endif
                    </h3>
                    @if($dl->recoveryTickets->count())
                    <div class="space-y-2">
                        @foreach($dl->recoveryTickets as $ticket)
                        @php
                            $tBadge = match($ticket->status) {
                                'completed'  => 'bg-emerald-100 text-emerald-700',
                                'in_progress'=> 'bg-oe-soft text-oe-hover',
                                'open'       => 'bg-amber-100 text-amber-700',
                                default      => 'bg-zinc-100 text-zinc-600',
                            };
                        @endphp
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-100">
                                        Agent: {{ $ticket->agent?->name ?? '—' }}
                                    </p>
                                    @if($ticket->notes)
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $ticket->notes }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tBadge }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                                    @if($ticket->reward_amount)
                                    <p class="text-[10px] text-gray-400 mt-1">Reward: TZS {{ number_format($ticket->reward_amount, 0) }}</p>
                                    @endif
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1.5">
                                Assigned {{ $ticket->assigned_at?->diffForHumans() }}
                                @if($ticket->completed_at) · Completed {{ $ticket->completed_at->format('d M Y') }} @endif
                            </p>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4 text-center">
                        <flux:icon name="shield-exclamation" class="size-8 mx-auto mb-2 text-gray-300 dark:text-zinc-600" />
                        <p class="text-xs text-gray-400">No recovery ticket assigned yet</p>
                    </div>
                    @endif
                </div>

                {{-- Repayment Schedule --}}
                @if($dl->repaymentSchedules->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">
                        Repayment Schedule
                        <span class="ml-1 text-gray-300">({{ $dl->repaymentSchedules->count() }} installments)</span>
                    </h3>
                    <div class="rounded-xl border border-gray-100 dark:border-zinc-700 overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-zinc-800 border-b border-gray-100 dark:border-zinc-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-400 uppercase">#</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-400 uppercase">Due</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-gray-400 uppercase">Amount</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-gray-400 uppercase">Paid</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-gray-400 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                                @foreach($dl->repaymentSchedules as $sched)
                                @php
                                    $sBadge = match($sched->status) {
                                        'paid'    => 'bg-emerald-100 text-emerald-700',
                                        'partial' => 'bg-amber-100 text-amber-700',
                                        'overdue' => 'bg-red-100 text-red-700',
                                        default   => 'bg-zinc-100 text-zinc-600',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-3 py-2 text-gray-500 font-mono">{{ $sched->installment_number }}</td>
                                    <td class="px-3 py-2 {{ $sched->due_date->isPast() && $sched->status !== 'paid' ? 'text-red-500 font-semibold' : 'text-gray-600 dark:text-gray-300' }}">
                                        {{ $sched->due_date->format('d M Y') }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-800 dark:text-gray-100">
                                        {{ number_format($sched->amount_due, 0) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-emerald-600 dark:text-emerald-400 font-semibold">
                                        {{ number_format($sched->amount_paid ?? 0, 0) }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $sBadge }}">{{ ucfirst($sched->status ?? 'pending') }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Recent Transactions --}}
                @if($dl->transactions->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Recent Transactions</h3>
                    <div class="space-y-1.5">
                        @foreach($dl->transactions as $txn)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800 rounded-xl px-3 py-2.5">
                            <div>
                                <p class="text-xs font-mono font-semibold text-oe dark:text-oe">{{ $txn->reference }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    {{ ucfirst($txn->type ?? 'payment') }} · {{ $txn->channel ?? '—' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold {{ $txn->entry_type === 'debit' ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $txn->entry_type === 'debit' ? '-' : '+' }}TZS {{ number_format($txn->amount, 0) }}
                                </p>
                                <p class="text-[10px] text-gray-400">{{ $txn->transacted_at?->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex gap-2">
                <button wire:click="closeDetail"
                        class="flex-1 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

</div>
