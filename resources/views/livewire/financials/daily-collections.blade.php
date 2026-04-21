<div class="flex flex-col gap-6">

    {{-- Alpine Toast --}}
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
            <x-fluent-icon name="banknotes" size="lg" palette="emerald" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Daily Collections</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Payment intake for <strong class="text-gray-700 dark:text-gray-300">{{ now()->parse($date)->format('d M Y') }}</strong>
                · {{ number_format($summary['count']) }} {{ Str::plural('transaction', $summary['count']) }}
            </p>
            </div>
        </div>
        <button wire:click="openPaymentModal"
                class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-900/20 hover:from-emerald-700 hover:to-teal-700 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Record Payment
        </button>
    </div>

    {{-- Stats: top row = total + month; bottom row = channels --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Hero: Today Total --}}
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-emerald-900/20 lg:col-span-2">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">Today's Total</p>
                    <p class="text-4xl font-black mt-1">TZS {{ number_format($summary['total'], 0) }}</p>
                    <p class="text-xs text-white/60 mt-2">{{ now()->parse($date)->format('l, d M Y') }}</p>
                </div>
                <div class="text-right bg-white/10 rounded-xl p-3">
                    <p class="text-xs text-white/60 uppercase font-bold">{{ now()->format('M Y') }}</p>
                    <p class="text-lg font-black text-emerald-200">TZS {{ number_format($summary['month_total'], 0) }}</p>
                    <p class="text-[10px] text-white/50 mt-0.5">Month to date</p>
                </div>
            </div>
        </div>
        {{-- M-Pesa --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-4 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">M-Pesa</p>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($summary['mpesa'], 0) }}</p>
        </div>
        {{-- Cash --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-4 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Cash</p>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($summary['cash'], 0) }}</p>
        </div>
    </div>

    {{-- Secondary channel row --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 border border-gray-100 dark:border-zinc-800 shadow-sm flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></span>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase">Tigo Pesa</p>
                <p class="text-sm font-black text-gray-900 dark:text-white">TZS {{ number_format($summary['tigopesa'], 0) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 border border-gray-100 dark:border-zinc-800 shadow-sm flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-oe flex-shrink-0"></span>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase">Halo Pesa</p>
                <p class="text-sm font-black text-gray-900 dark:text-white">TZS {{ number_format($summary['halopesa'], 0) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 border border-gray-100 dark:border-zinc-800 shadow-sm flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></span>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase">Bank</p>
                <p class="text-sm font-black text-gray-900 dark:text-white">TZS {{ number_format($summary['bank'], 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        {{-- Channel Tabs --}}
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1 flex-wrap">
            @foreach(['' => 'All', 'mpesa' => 'M-Pesa', 'cash' => 'Cash', 'tigopesa' => 'Tigo', 'halopesa' => 'Halo', 'bank' => 'Bank'] as $val => $label)
            <button wire:click="$set('channelFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors
                        {{ $channelFilter === $val
                            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <flux:input wire:model.live="date" type="date" class="w-40" />
            <div class="w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Ref, customer, loan #…" icon="magnifying-glass" />
            </div>
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Loan</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-400 uppercase tracking-wider hidden md:table-cell">Balance After</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Recorded By</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                    @forelse($transactions as $txn)
                    @php
                        $channelColor = match($txn->channel) {
                            'mpesa'       => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'cash'        => 'bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe',
                            'tigopesa'    => 'bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe',
                            'halopesa'    => 'bg-oe/15 text-oe-hover dark:bg-orange-900/30 dark:text-orange-400',
                            'bank'        => 'bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe',
                            default       => 'bg-gray-100 text-gray-600',
                        };
                        $channelLabel = match($txn->channel) {
                            'mpesa' => 'M-Pesa', 'tigopesa' => 'Tigo', 'halopesa' => 'Halo', 'bank' => 'Bank',
                            default => ucfirst($txn->channel),
                        };
                    @endphp
                    <tr wire:key="txn-{{ $txn->id }}"
                        class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
                        wire:click="openDetail('{{ $txn->id }}')">

                        <td class="px-4 py-3">
                            <p class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ $txn->reference }}</p>
                            @if($txn->external_reference)
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $txn->external_reference }}</p>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900 dark:text-white text-xs">{{ $txn->loan?->customer?->full_name ?? '—' }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $txn->loan?->customer?->phone ?? '' }}</p>
                        </td>

                        <td class="px-4 py-3">
                            <p class="font-mono text-xs font-bold text-oe dark:text-oe">{{ $txn->loan?->loan_number ?? '—' }}</p>
                            @if($txn->description)
                            <p class="text-[10px] text-gray-400 mt-0.5 max-w-[140px] truncate">{{ $txn->description }}</p>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-right">
                            <p class="font-black text-emerald-600 dark:text-emerald-400">TZS {{ number_format($txn->amount, 0) }}</p>
                        </td>

                        <td class="px-4 py-3 text-right hidden md:table-cell">
                            @if($txn->loan)
                            <p class="text-xs font-semibold {{ $txn->loan->remaining_balance <= 0 ? 'text-emerald-600' : 'text-gray-600 dark:text-gray-300' }}">
                                {{ $txn->loan->remaining_balance <= 0 ? 'Paid Off' : 'TZS '.number_format($txn->loan->remaining_balance, 0) }}
                            </p>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $channelColor }}">
                                {{ $channelLabel }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $txn->transacted_at->format('H:i') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $txn->transacted_at->diffForHumans() }}</p>
                        </td>

                        <td class="px-4 py-3 hidden lg:table-cell">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $txn->recordedBy?->name ?? '—' }}</p>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <button wire:click.stop="openDetail('{{ $txn->id }}')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-oe dark:text-oe hover:bg-oe-soft dark:hover:bg-oe/10 transition-colors">
                                Details
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center">
                            <flux:icon name="banknotes" class="size-12 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                            <p class="font-semibold text-gray-500">No collections for {{ now()->parse($date)->format('d M Y') }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                @if($search || $channelFilter) Try clearing your filters @else Record the first payment using the button above @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $transactions->links() }}</div>
        @endif
    </div>

    {{-- ══ TRANSACTION DETAIL SLIDE-OVER ══ --}}
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

            @if($this->detailTxn)
            @php
                $dt           = $this->detailTxn;
                $dtChannel    = match($dt->channel) {
                    'mpesa' => 'M-Pesa', 'tigopesa' => 'Tigo Pesa', 'halopesa' => 'Halo Pesa', 'bank' => 'Bank Transfer',
                    default => ucfirst($dt->channel),
                };
                $dtColorGrad  = match($dt->channel) {
                    'mpesa'    => 'from-green-600 to-teal-700',
                    'cash'     => 'from-oe to-oe-hover',
                    'tigopesa' => 'from-oe to-oe-hover',
                    'halopesa' => 'from-oe to-red-600',
                    'bank'     => 'from-oe to-oe-hover',
                    default    => 'from-gray-600 to-gray-700',
                };
            @endphp

            {{-- Header --}}
            <div class="px-6 py-5 bg-gradient-to-r {{ $dtColorGrad }} text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-white/60 uppercase tracking-wider">{{ $dtChannel }} Payment</p>
                        <p class="text-4xl font-black mt-1">TZS {{ number_format($dt->amount, 0) }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-white/70 text-xs font-mono">{{ $dt->reference }}</span>
                            @if($dt->external_reference)
                            <span class="text-white/50 text-xs">· Ext: {{ $dt->external_reference }}</span>
                            @endif
                        </div>
                        <p class="text-white/60 text-xs mt-1">{{ $dt->transacted_at->format('d M Y, H:i') }} · {{ $dt->transacted_at->diffForHumans() }}</p>
                    </div>
                    <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @if($dt->description)
                <p class="text-white/60 text-xs mt-3 italic">"{{ $dt->description }}"</p>
                @endif
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Customer & Loan --}}
                @if($dt->loan)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Customer & Loan</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">{{ $dt->loan->customer?->full_name ?? '—' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $dt->loan->customer?->phone ?? '' }}</p>
                                @if($dt->loan->customer?->national_id)
                                <p class="text-[10px] text-gray-400">NID: {{ $dt->loan->customer->national_id }}</p>
                                @endif
                            </div>
                            <p class="font-mono text-sm font-bold text-oe dark:text-oe">{{ $dt->loan->loan_number }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-100 dark:border-zinc-700">
                            <div class="text-center">
                                <p class="text-[10px] text-gray-400 uppercase font-bold">Principal</p>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100 mt-0.5">{{ number_format($dt->loan->principal_amount, 0) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-gray-400 uppercase font-bold">Paid</p>
                                <p class="text-xs font-bold text-emerald-600 mt-0.5">{{ number_format($dt->loan->amount_paid, 0) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-gray-400 uppercase font-bold">Balance</p>
                                <p class="text-xs font-bold {{ $dt->loan->remaining_balance <= 0 ? 'text-emerald-600' : 'text-amber-600' }} mt-0.5">
                                    {{ $dt->loan->remaining_balance <= 0 ? 'Cleared' : number_format($dt->loan->remaining_balance, 0) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                    {{ match($dt->loan->status) {
                                        'active'    => 'bg-emerald-100 text-emerald-700',
                                        'overdue'   => 'bg-red-100 text-red-700',
                                        'completed' => 'bg-sky-100 text-sky-700',
                                        'defaulted' => 'bg-rose-100 text-rose-700',
                                        default     => 'bg-zinc-100 text-zinc-600',
                                    } }}">{{ ucfirst($dt->loan->status) }}</span>
                                @if($dt->loan->branch)
                                <span class="text-[10px] text-oe-hover font-semibold">{{ $dt->loan->branch->name }}</span>
                                @endif
                            </div>
                            @if($dt->loan->inventoryUnit?->phoneModel)
                            <p class="text-[10px] text-gray-400">
                                {{ $dt->loan->inventoryUnit->phoneModel->brand?->name }}
                                {{ $dt->loan->inventoryUnit->phoneModel->name }}
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Repayment Schedule --}}
                @if($dt->repaymentSchedule)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Applied to Schedule</h3>
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800 rounded-xl px-4 py-3">
                        <div>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Installment #{{ $dt->repaymentSchedule->installment_number ?? '—' }}
                            </p>
                            <p class="text-[10px] text-gray-400 mt-0.5">Due: {{ $dt->repaymentSchedule->due_date?->format('d M Y') ?? '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100">TZS {{ number_format($dt->repaymentSchedule->amount, 0) }}</p>
                            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold
                                {{ $dt->repaymentSchedule->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ ucfirst($dt->repaymentSchedule->status) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Recorded By + Timestamps --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Audit</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Recorded By</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dt->recordedBy?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Entry Type</p>
                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-0.5 capitalize">{{ $dt->entry_type }} / {{ $dt->type }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 col-span-2">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Transacted At</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dt->transacted_at->format('l, d M Y · H:i:s') }}</p>
                        </div>
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

    {{-- ══ RECORD PAYMENT MODAL ══ --}}
    <flux:modal wire:model="showPaymentModal" class="max-w-lg">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Record Payment</h2>
            <p class="text-sm text-gray-500 mb-6">Log a customer repayment and update loan balance.</p>

            <div class="space-y-4">
                {{-- Loan Search --}}
                <flux:field>
                    <flux:label>Search Loan / Customer</flux:label>
                    <flux:input wire:model.live.debounce.300ms="paymentLoanSearch" icon="magnifying-glass" placeholder="Loan no, name or phone..." />
                    @if($searchLoans->isNotEmpty())
                        <div class="mt-1 border border-gray-200 dark:border-zinc-700 rounded-lg max-h-44 overflow-y-auto bg-white dark:bg-zinc-900 shadow-lg">
                            @foreach($searchLoans as $ln)
                                <button type="button"
                                    wire:click="$set('paymentLoanId', '{{ $ln->id }}')"
                                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-oe-soft dark:hover:bg-zinc-800 transition-colors @if($paymentLoanId === $ln->id) bg-oe-soft dark:bg-zinc-800 font-semibold text-oe @endif">
                                    <span class="font-mono text-oe">{{ $ln->loan_number }}</span>
                                    &mdash; {{ $ln->customer?->first_name }} {{ $ln->customer?->last_name }}
                                    <span class="text-xs text-gray-400 ml-1">(bal: TZS {{ number_format($ln->remaining_balance) }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @if($paymentLoanId)
                        @php $selLoan = $searchLoans->firstWhere('id', $paymentLoanId); @endphp
                        <div class="mt-1 flex items-center gap-2 text-sm text-oe dark:text-oe">
                            <flux:icon name="check-circle" class="size-4" />
                            {{ $selLoan?->loan_number }} &mdash; Balance: TZS {{ number_format($selLoan?->remaining_balance ?? 0) }}
                        </div>
                    @endif
                    <flux:error name="paymentLoanId" />
                </flux:field>

                {{-- Amount & Channel --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Amount (TZS)</flux:label>
                        <flux:input wire:model="paymentAmount" type="number" min="1" placeholder="e.g. 50000" />
                        <flux:error name="paymentAmount" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Payment Channel</flux:label>
                        <flux:select wire:model="paymentChannel">
                            <flux:select.option value="mpesa">M-Pesa</flux:select.option>
                            <flux:select.option value="cash">Cash</flux:select.option>
                            <flux:select.option value="tigopesa">Tigo Pesa</flux:select.option>
                            <flux:select.option value="halopesa">Halo Pesa</flux:select.option>
                            <flux:select.option value="bank">Bank Transfer</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                {{-- External Reference --}}
                <flux:field>
                    <flux:label>Transaction Reference (optional)</flux:label>
                    <flux:input wire:model="paymentExternalRef" placeholder="e.g. MPESA code: QWE123" />
                </flux:field>

                {{-- Note --}}
                <flux:field>
                    <flux:label>Note (optional)</flux:label>
                    <flux:input wire:model="paymentNote" placeholder="Remarks..." />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-zinc-800">
                <flux:button variant="ghost" wire:click="$set('showPaymentModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="recordPayment" wire:loading.attr="disabled" icon="banknotes">
                    <span wire:loading.remove wire:target="recordPayment">Save Payment</span>
                    <span wire:loading wire:target="recordPayment">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>
