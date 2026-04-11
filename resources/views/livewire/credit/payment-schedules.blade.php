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
    <div class="flex items-start gap-4">
        <x-fluent-icon name="calendar-days" size="lg" palette="blue" />
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Payment Schedules</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Installment breakdown per loan — track what's due, paid, and overdue</p>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-blue-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="calendar-days" size="sm" />
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">Due This Week</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['due_this_week']) }}</p>
            <p class="text-xs text-white/60 mt-1">Installments due in 7 days</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="exclamation-triangle" size="sm" palette="rose" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Overdue</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['overdue_count']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Unpaid past-due installments</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="banknotes" size="sm" palette="emerald" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Collected ({{ now()->format('M') }})</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['collected_month'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Payments received this month</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="document-text" size="sm" palette="blue" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Loans</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['active_loans']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Active + overdue + pending</p>
        </div>
    </div>

    {{-- Filters Row --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1 flex-wrap">
            @foreach(['' => 'Active', 'active' => 'Performing', 'overdue' => 'Overdue', 'pending' => 'Pending', 'completed' => 'Completed'] as $val => $label)
            <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors
                        {{ $statusFilter === $val
                            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <div class="w-56">
                <flux:input wire:model.live.debounce.300ms="loanSearch" placeholder="Loan #, name, phone…" icon="magnifying-glass" />
            </div>
            <flux:select wire:model.live="branchFilter" class="w-40">
                <flux:select.option value="">All Branches</flux:select.option>
                @foreach($branches as $b)
                <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Main 2-Column Layout --}}
    <div class="grid gap-5 lg:grid-cols-5">

        {{-- ── LEFT: Loan List ── --}}
        <div class="lg:col-span-2 flex flex-col gap-2">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden divide-y divide-gray-50 dark:divide-zinc-800">
                @forelse($loans as $loan)
                @php
                    $lPrincipal   = (float) $loan->principal_amount;
                    $lPaid        = (float) ($loan->amount_paid ?? 0);
                    $lTotalPay    = (float) ($loan->total_payable ?: $lPrincipal);
                    $lProgress    = $lTotalPay > 0 ? min(100, round(($lPaid / $lTotalPay) * 100)) : 0;
                    $isSelected   = $selectedLoanId === $loan->id;
                    $isOverdue    = $loan->status === 'overdue';
                    $lProgColor   = $isOverdue ? 'bg-red-500' : 'bg-blue-500';
                    $lBorder      = $isSelected
                        ? 'border-l-4 border-orange-500 bg-blue-50 dark:bg-blue-900/20'
                        : ($isOverdue ? 'border-l-4 border-red-400' : 'border-l-4 border-transparent');
                @endphp
                <button wire:click="selectLoan('{{ $loan->id }}')"
                        wire:key="sl-{{ $loan->id }}"
                        class="w-full px-4 py-3.5 text-left transition-colors {{ $lBorder }}
                            {{ $isSelected ? '' : 'hover:bg-gray-50 dark:hover:bg-zinc-800/60' }}">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="font-mono text-xs font-bold {{ $isSelected ? 'text-orange-500 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $loan->loan_number }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                            {{ match($loan->status) {
                                'active'    => 'bg-emerald-100 text-emerald-700',
                                'overdue'   => 'bg-red-100 text-red-700',
                                'completed' => 'bg-sky-100 text-sky-700',
                                'pending'   => 'bg-amber-100 text-amber-700',
                                default     => 'bg-zinc-100 text-zinc-600',
                            } }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">{{ $loan->customer?->full_name ?? '—' }}</p>
                    @if($loan->inventoryUnit?->phoneModel)
                    <p class="text-[10px] text-gray-400 mt-0.5">
                        {{ $loan->inventoryUnit->phoneModel->brand?->name }} {{ $loan->inventoryUnit->phoneModel->name }}
                    </p>
                    @endif
                    <div class="mt-2 flex items-center gap-2">
                        <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full {{ $lProgColor }}" style="width: {{ $lProgress }}%"></div>
                        </div>
                        <span class="text-[10px] font-bold text-gray-400">{{ $lProgress }}%</span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-[10px] text-gray-400">Balance: TZS {{ number_format($loan->outstanding_balance ?? 0, 0) }}</span>
                        @if($loan->branch)
                        <span class="text-[10px] text-gray-400">{{ $loan->branch->name }}</span>
                        @endif
                    </div>
                </button>
                @empty
                <div class="px-4 py-10 text-center">
                    <flux:icon name="document-text" class="size-10 mx-auto mb-2 text-gray-300 dark:text-zinc-600" />
                    <p class="text-sm text-gray-400">No loans found</p>
                </div>
                @endforelse
            </div>
            @if($loans->hasPages())
            <div class="px-2">{{ $loans->links() }}</div>
            @endif
        </div>

        {{-- ── RIGHT: Schedule Detail ── --}}
        <div class="lg:col-span-3">
            @if($this->detailLoan)
            @php
                $dl          = $this->detailLoan;
                $dlPrincipal = (float) $dl->principal_amount;
                $dlPaid      = (float) ($dl->amount_paid ?? 0);
                $dlBalance   = (float) ($dl->outstanding_balance ?? 0);
                $dlTotalPay  = (float) ($dl->total_payable ?: $dlPrincipal);
                $dlProgress  = $dlTotalPay > 0 ? min(100, round(($dlPaid / $dlTotalPay) * 100)) : 0;
                $dlProgressColor = match($dl->status) {
                    'completed' => 'bg-emerald-500',
                    'overdue'   => 'bg-red-500',
                    default     => 'bg-blue-500',
                };
                $schedTotal  = $dl->repaymentSchedules->sum('amount_due');
                $schedPaid   = $dl->repaymentSchedules->sum('amount_paid');
                $schedBalance = $dl->repaymentSchedules->sum('balance_remaining');
            @endphp

            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">

                {{-- Detail Header --}}
                <div class="px-5 py-4 bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">Schedule</p>
                            <h2 class="text-lg font-black font-mono mt-0.5">{{ $dl->loan_number }}</h2>
                        </div>
                        <button wire:click="clearSelection" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    {{-- Customer + Device --}}
                    <div class="flex items-center gap-2 mt-3">
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center text-white text-[10px] font-black flex-shrink-0">
                            {{ strtoupper(substr($dl->customer?->first_name ?? '?', 0, 1).substr($dl->customer?->last_name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white leading-none">{{ $dl->customer?->full_name ?? '—' }}</p>
                            <p class="text-[10px] text-white/60 mt-0.5">{{ $dl->customer?->phone }}
                                @if($dl->inventoryUnit?->phoneModel)
                                 · {{ $dl->inventoryUnit->phoneModel->brand?->name }} {{ $dl->inventoryUnit->phoneModel->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Loan Summary Tiles --}}
                <div class="grid grid-cols-4 divide-x divide-gray-100 dark:divide-zinc-800 border-b border-gray-100 dark:border-zinc-800">
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Principal</p>
                        <p class="text-sm font-black text-gray-900 dark:text-white mt-0.5">{{ number_format($dlPrincipal, 0) }}</p>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Paid</p>
                        <p class="text-sm font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ number_format($dlPaid, 0) }}</p>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Balance</p>
                        <p class="text-sm font-black {{ $dlBalance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }} mt-0.5">{{ number_format($dlBalance, 0) }}</p>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Progress</p>
                        <p class="text-sm font-black text-orange-500 dark:text-blue-400 mt-0.5">{{ $dlProgress }}%</p>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="px-5 py-2 border-b border-gray-50 dark:border-zinc-800">
                    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-zinc-700 overflow-hidden">
                        <div class="h-full rounded-full {{ $dlProgressColor }}" style="width: {{ $dlProgress }}%"></div>
                    </div>
                </div>

                {{-- Schedule Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                            <tr>
                                <th class="px-3 py-2.5 text-left font-bold text-gray-400 uppercase tracking-wider">#</th>
                                <th class="px-3 py-2.5 text-left font-bold text-gray-400 uppercase tracking-wider">Due Date</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider">Principal</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider">Interest</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider">Amount Due</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider">Paid</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider hidden sm:table-cell">Balance</th>
                                <th class="px-3 py-2.5 text-center font-bold text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                            @foreach($dl->repaymentSchedules as $sched)
                            @php
                                $isPastDue  = $sched->due_date->isPast() && $sched->status !== 'paid';
                                $isPaid     = $sched->status === 'paid';
                                $isPartial  = $sched->status === 'partial';
                                $sBadge = match($sched->status) {
                                    'paid'    => 'bg-emerald-100 text-emerald-700',
                                    'partial' => 'bg-amber-100 text-amber-700',
                                    'overdue' => 'bg-red-100 text-red-700',
                                    default   => 'bg-zinc-100 text-zinc-500',
                                };
                                $rowBg = $isPastDue ? 'bg-red-50/60 dark:bg-red-900/10' : ($isPaid ? 'bg-emerald-50/30 dark:bg-emerald-900/5' : '');
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors {{ $rowBg }}">
                                <td class="px-3 py-2.5 font-mono text-gray-500 font-semibold">{{ $sched->installment_number }}</td>
                                <td class="px-3 py-2.5">
                                    <p class="{{ $isPastDue ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-600 dark:text-gray-300' }}">
                                        {{ $sched->due_date->format('d M Y') }}
                                    </p>
                                    @if($isPaid && $sched->paid_at)
                                    <p class="text-[9px] text-emerald-600 mt-0.5">Paid {{ $sched->paid_at->format('d M Y') }}</p>
                                    @elseif($isPastDue)
                                    <p class="text-[9px] text-red-500 mt-0.5">{{ $sched->due_date->diffForHumans() }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right text-gray-700 dark:text-gray-300 font-medium">
                                    {{ number_format($sched->principal_component ?? 0, 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right text-orange-500 dark:text-blue-400">
                                    {{ number_format($sched->interest_component ?? 0, 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-bold text-gray-900 dark:text-white">
                                    {{ number_format($sched->amount_due, 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right text-emerald-600 dark:text-emerald-400 font-semibold">
                                    {{ number_format($sched->amount_paid ?? 0, 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right hidden sm:table-cell {{ (float)($sched->balance_remaining ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600' }} font-semibold">
                                    {{ number_format($sched->balance_remaining ?? 0, 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $sBadge }}">
                                        {{ ucfirst($sched->status ?? 'pending') }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        {{-- Totals Row --}}
                        <tfoot class="bg-gray-50 dark:bg-zinc-800/80 border-t-2 border-gray-200 dark:border-zinc-600">
                            <tr>
                                <td colspan="2" class="px-3 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Totals</td>
                                <td class="px-3 py-2.5 text-right text-xs font-bold text-gray-800 dark:text-gray-100"></td>
                                <td class="px-3 py-2.5 text-right text-xs font-bold text-gray-800 dark:text-gray-100"></td>
                                <td class="px-3 py-2.5 text-right text-xs font-black text-gray-900 dark:text-white">
                                    TZS {{ number_format($schedTotal, 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right text-xs font-black text-emerald-600 dark:text-emerald-400">
                                    TZS {{ number_format($schedPaid, 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right text-xs font-black hidden sm:table-cell {{ $schedBalance > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                    TZS {{ number_format($schedBalance, 0) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Recent Transactions --}}
                @if($dl->transactions->count())
                <div class="px-5 py-4 border-t border-gray-100 dark:border-zinc-800">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Recent Transactions</p>
                    <div class="space-y-1.5">
                        @foreach($dl->transactions as $txn)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800 rounded-xl px-3 py-2">
                            <div>
                                <p class="text-xs font-mono font-semibold text-orange-500 dark:text-blue-400">{{ $txn->reference }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ ucfirst($txn->type ?? 'payment') }} · {{ $txn->channel ?? '—' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold {{ $txn->entry_type === 'debit' ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $txn->entry_type === 'debit' ? '-' : '+' }}TZS {{ number_format($txn->amount, 0) }}
                                </p>
                                <p class="text-[10px] text-gray-400">{{ $txn->transacted_at?->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Loan Meta Footer --}}
                <div class="px-5 py-3 bg-gray-50 dark:bg-zinc-800/60 border-t border-gray-100 dark:border-zinc-800">
                    <div class="flex flex-wrap gap-x-5 gap-y-1 text-[10px] text-gray-400">
                        <span>Interest: <strong class="text-gray-600 dark:text-gray-300">{{ $dl->interest_rate ?? 0 }}% {{ $dl->interest_type }}</strong></span>
                        <span>Duration: <strong class="text-gray-600 dark:text-gray-300">{{ $dl->duration_weeks ?? '—' }} weeks</strong></span>
                        <span>Frequency: <strong class="text-gray-600 dark:text-gray-300">{{ ucfirst($dl->repayment_frequency ?? '—') }}</strong></span>
                        <span>Branch: <strong class="text-gray-600 dark:text-gray-300">{{ $dl->branch?->name ?? '—' }}</strong></span>
                        <span>Disbursed by: <strong class="text-gray-600 dark:text-gray-300">{{ $dl->disbursedBy?->name ?? '—' }}</strong></span>
                    </div>
                </div>
            </div>
            @else
            <div class="flex flex-col items-center justify-center min-h-[26rem] rounded-2xl bg-gray-50 dark:bg-zinc-900 border border-dashed border-gray-200 dark:border-zinc-700">
                <flux:icon name="calendar-days" class="size-14 text-gray-300 dark:text-zinc-600 mb-3" />
                <p class="font-semibold text-gray-500">Select a loan to view its schedule</p>
                <p class="text-xs text-gray-400 mt-1">Click any loan from the list on the left</p>
            </div>
            @endif
        </div>
    </div>

</div>
