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
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Credit Control</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Loan portfolio — active, overdue, pending &amp; completed</p>
        </div>
    </div>

    {{-- Stats Bar --}}
    @php
    $statDefs = [
        ['key' => 'portfolio',   'label' => 'Active Portfolio',   'grad'   => 'from-[#2563eb] to-[#2563eb]', 'hero' => true, 'sub' => 'Active + overdue principal'],
        ['key' => 'collected',   'label' => 'Total Collected',    'icolor' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600', 'sub' => 'All-time repayments'],
        ['key' => 'outstanding', 'label' => 'Total Outstanding',  'icolor' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600',     'sub' => 'Unpaid balances'],
        ['key' => 'overdue_amt', 'label' => 'Overdue Balance',    'icolor' => 'bg-red-100 dark:bg-red-900/30 text-red-600',           'sub' => 'Overdue loans only'],
    ];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($statDefs as $sd)
        @if(!empty($sd['hero']))
        <div class="bg-gradient-to-br {{ $sd['grad'] }} rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-blue-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-white/20"><flux:icon name="banknotes" class="size-5" /></div>
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">{{ $sd['label'] }}</span>
            </div>
            <p class="text-2xl font-black">TZS {{ number_format($stats[$sd['key']], 0) }}</p>
            <p class="text-xs text-white/60 mt-1">{{ $sd['sub'] }}</p>
        </div>
        @else
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg {{ $sd['icolor'] }}">
                    <flux:icon name="banknotes" class="size-5" />
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $sd['label'] }}</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats[$sd['key']], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $sd['sub'] }}</p>
        </div>
        @endif
        @endforeach
    </div>

    {{-- Status Tabs + Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1 flex-wrap">
            @foreach(['active' => 'Active', 'overdue' => 'Overdue', 'pending' => 'Pending', 'completed' => 'Completed', '' => 'All'] as $status => $label)
            <button wire:click="$set('statusFilter', '{{ $status }}')"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors
                        {{ $statusFilter === $status ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                {{ $label }}
                @if(isset($counts[$status]) && $counts[$status] > 0)
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold
                    {{ $status === 'overdue' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-orange-500' }}">
                    {{ $counts[$status] }}
                </span>
                @endif
            </button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <div class="w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Loan #, name, phone…" icon="magnifying-glass" />
            </div>
            <flux:select wire:model.live="branchFilter" class="w-44">
                <flux:select.option value="">All Branches</flux:select.option>
                @foreach($branches as $b)
                <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Loans Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Loan #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Device</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Principal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Progress</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Due</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                @forelse($loans as $loan)
                @php
                    $principal   = (float) $loan->principal_amount;
                    $amountPaid  = (float) ($loan->amount_paid ?? 0);
                    $outstanding = (float) ($loan->outstanding_balance ?? 0);
                    $totalPayable = (float) ($loan->total_payable ?: $principal);
                    $progress    = $totalPayable > 0 ? min(100, round(($amountPaid / $totalPayable) * 100)) : 0;

                    $statusBadge = match($loan->status) {
                        'active'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                        'overdue'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        'completed' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                        'pending'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                        default     => 'bg-zinc-100 text-zinc-600',
                    };
                    $statusDot = match($loan->status) {
                        'active'    => 'bg-emerald-400',
                        'overdue'   => 'bg-red-400',
                        'completed' => 'bg-sky-400',
                        'pending'   => 'bg-amber-400',
                        default     => 'bg-zinc-400',
                    };
                    $progressBar = match($loan->status) {
                        'completed' => 'bg-emerald-500',
                        'overdue'   => 'bg-red-500',
                        default     => 'bg-blue-500',
                    };
                    $isDuePast = $loan->due_date?->isPast() && $loan->status !== 'completed';
                @endphp
                <tr wire:key="loan-{{ $loan->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
                    wire:click="openDetail('{{ $loan->id }}')">
                    <td class="px-4 py-3.5">
                        <p class="font-mono text-xs font-bold text-orange-500 dark:text-blue-400">{{ $loan->loan_number }}</p>
                        @if($loan->branch)
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $loan->branch->name }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-black flex-shrink-0">
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
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">TZS {{ number_format($principal, 0) }}</p>
                        @if($outstanding > 0)
                        <p class="text-xs text-amber-600 dark:text-amber-400 font-medium mt-0.5">
                            {{ number_format($outstanding, 0) }} left
                        </p>
                        @else
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5">Cleared</p>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 hidden lg:table-cell w-36">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-zinc-700 overflow-hidden">
                                <div class="h-full rounded-full {{ $progressBar }} transition-all"
                                     style="width: {{ $progress }}%"></div>
                            </div>
                            <span class="text-[10px] font-bold text-gray-500 w-7 text-right">{{ $progress }}%</span>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">TZS {{ number_format($amountPaid, 0) }} paid</p>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusBadge }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 hidden md:table-cell">
                        <p class="text-xs font-medium {{ $isDuePast ? 'text-red-500 dark:text-red-400' : 'text-gray-500' }}">
                            {{ $loan->due_date?->format('d M Y') ?? '—' }}
                        </p>
                        @if($isDuePast)
                        <p class="text-[10px] text-red-500 font-semibold mt-0.5">
                            {{ $loan->due_date->diffForHumans() }}
                        </p>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <button wire:click.stop="openDetail('{{ $loan->id }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-50 text-orange-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Details
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-16 text-center">
                        <flux:icon name="document-text" class="size-12 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                        <p class="text-gray-500 font-medium">No loans found</p>
                        <p class="text-gray-400 text-xs mt-1">
                            @if($search || $branchFilter) Try clearing your filters @endif
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($loans->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $loans->links() }}</div>
        @endif
    </div>

    {{-- ══ LOAN DETAIL SLIDE-OVER ══ --}}
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
                $dl  = $this->detailLoan;
                $dlPrincipal  = (float) $dl->principal_amount;
                $dlPaid       = (float) ($dl->amount_paid ?? 0);
                $dlOutstanding = (float) ($dl->outstanding_balance ?? 0);
                $dlTotalPayable = (float) ($dl->total_payable ?: $dlPrincipal);
                $dlProgress   = $dlTotalPayable > 0 ? min(100, round(($dlPaid / $dlTotalPayable) * 100)) : 0;
                $dlStatusBadge = match($dl->status) {
                    'active'    => 'bg-emerald-100 text-emerald-700',
                    'overdue'   => 'bg-red-100 text-red-700',
                    'completed' => 'bg-sky-100 text-sky-700',
                    'pending'   => 'bg-amber-100 text-amber-700',
                    default     => 'bg-zinc-100 text-zinc-600',
                };
                $dlProgressBar = match($dl->status) {
                    'completed' => 'bg-emerald-500',
                    'overdue'   => 'bg-red-500',
                    default     => 'bg-blue-500',
                };
            @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                <div>
                    <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">Loan Details</p>
                    <h2 class="text-xl font-black mt-0.5 font-mono">{{ $dl->loan_number }}</h2>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $dlStatusBadge }}">{{ ucfirst($dl->status) }}</span>
                        @if($dl->due_date?->isPast() && $dl->status !== 'completed')
                        <span class="text-xs text-red-200 font-semibold">{{ $dl->due_date->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
                <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Repayment Progress Bar --}}
            <div class="px-6 py-3 bg-gray-50 dark:bg-zinc-800/60 border-b border-gray-100 dark:border-zinc-700">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-semibold text-gray-500">Repayment Progress</span>
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ $dlProgress }}%</span>
                </div>
                <div class="h-2 rounded-full bg-gray-200 dark:bg-zinc-700 overflow-hidden">
                    <div class="h-full rounded-full {{ $dlProgressBar }}" style="width: {{ $dlProgress }}%"></div>
                </div>
                <div class="flex justify-between mt-1.5 text-[10px] text-gray-400">
                    <span>Paid: TZS {{ number_format($dlPaid, 0) }}</span>
                    <span>Outstanding: TZS {{ number_format($dlOutstanding, 0) }}</span>
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Customer --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Customer</h3>
                    <div class="flex items-center gap-3 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-xs font-black flex-shrink-0">
                            {{ strtoupper(substr($dl->customer?->first_name ?? '?', 0, 1).substr($dl->customer?->last_name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $dl->customer?->full_name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $dl->customer?->phone }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[10px] text-gray-400">Branch</p>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $dl->customer?->branch?->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Device --}}
                @if($dl->inventoryUnit)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-400 to-slate-600 flex items-center justify-center flex-shrink-0">
                                <flux:icon name="device-phone-mobile" class="size-5 text-white" />
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $dl->inventoryUnit->phoneModel?->brand?->name }} {{ $dl->inventoryUnit->phoneModel?->name }}
                                </p>
                                <p class="text-xs text-gray-400 font-mono mt-0.5">
                                    IMEI: {{ $dl->inventoryUnit->imei ?? $dl->inventoryUnit->serial_number ?? '—' }}
                                </p>
                            </div>
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
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Interest Rate</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $dl->interest_rate ?? 0 }}% <span class="text-xs font-normal text-gray-400">{{ $dl->interest_type }}</span></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Duration</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $dl->duration_weeks ?? '—' }} wks</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Total Payable</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">TZS {{ number_format($dlTotalPayable, 0) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Frequency</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ ucfirst($dl->repayment_frequency ?? '—') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Disbursed</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $dl->disbursed_at?->format('d M Y') ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Due Date</p>
                            <p class="text-sm font-bold {{ $dl->due_date?->isPast() && $dl->status !== 'completed' ? 'text-red-600' : 'text-gray-900 dark:text-white' }} mt-0.5">
                                {{ $dl->due_date?->format('d M Y') ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Disbursement Info --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Administration</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Branch</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dl->branch?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Vendor</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dl->vendor?->name ?? '—' }}</p>
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
                    <div class="mt-2 bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 border border-amber-100 dark:border-amber-900/40">
                        <p class="text-[10px] text-amber-600 uppercase font-bold mb-1">Notes</p>
                        <p class="text-xs text-gray-700 dark:text-gray-300">{{ $dl->notes }}</p>
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
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300 {{ $sched->due_date->isPast() && $sched->status !== 'paid' ? 'text-red-500' : '' }}">
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
                                <p class="text-xs font-mono font-semibold text-orange-500 dark:text-blue-400">{{ $txn->reference }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    {{ ucfirst($txn->type ?? 'payment') }} · {{ $txn->channel ?? '—' }}
                                    @if($txn->recordedBy) · {{ $txn->recordedBy->name }} @endif
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
