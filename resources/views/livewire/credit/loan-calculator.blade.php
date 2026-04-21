<div class="flex flex-col gap-6">

    {{-- Header --}}
    <div class="flex items-start gap-4">
        <x-fluent-icon name="calculator" size="lg" palette="violet" />
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Loan Calculator</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Simulate repayment schedules, compare interest methods, and view full amortization</p>
        </div>
    </div>

    {{-- Main 2-col layout --}}
    <div class="grid gap-5 lg:grid-cols-5">

        {{-- ── LEFT: Input Form ── --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-6 space-y-5">

                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Loan Parameters</p>

                {{-- Principal + Deposit --}}
                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Device Price (TZS)</flux:label>
                        <flux:input wire:model="principal" type="number" min="10000" step="1000" placeholder="500,000" />
                        <flux:error name="principal" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Deposit (TZS)</flux:label>
                        <flux:input wire:model="deposit" type="number" min="0" step="1000" placeholder="0" />
                        <flux:error name="deposit" />
                    </flux:field>
                </div>

                @if($deposit > 0 && $principal > 0)
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-oe-soft dark:bg-oe/10 border border-oe/20 dark:border-oe/25/40">
                    <x-fluent-icon name="information-circle" size="xs" palette="blue" />
                    <p class="text-xs text-oe dark:text-oe">
                        Financed principal: <strong>TZS {{ number_format(max(0, $principal - $deposit), 0) }}</strong>
                    </p>
                </div>
                @endif

                {{-- Interest Rate --}}
                <flux:field>
                    <flux:label>Annual Interest Rate (%)</flux:label>
                    <flux:input wire:model="interestRate" type="number" min="0" max="100" step="0.5" placeholder="24" />
                    <flux:error name="interestRate" />
                </flux:field>

                {{-- Duration --}}
                <flux:field>
                    <flux:label>Duration (Weeks)</flux:label>
                    <flux:input wire:model="durationWeeks" type="number" min="1" max="260" placeholder="12" />
                    <flux:error name="durationWeeks" />
                </flux:field>

                {{-- Start Date --}}
                <flux:field>
                    <flux:label>Loan Start Date</flux:label>
                    <flux:input wire:model="startDate" type="date" />
                    <flux:error name="startDate" />
                </flux:field>

                {{-- Interest Type Pill Toggle --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 mb-2">Interest Method</p>
                    <div class="flex rounded-xl bg-gray-100 dark:bg-zinc-800 p-1">
                        <button wire:click="$set('interestType', 'flat')"
                                class="flex-1 py-2 text-xs font-semibold rounded-lg transition-colors
                                    {{ $interestType === 'flat' ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                            Flat Rate
                        </button>
                        <button wire:click="$set('interestType', 'reducing_balance')"
                                class="flex-1 py-2 text-xs font-semibold rounded-lg transition-colors
                                    {{ $interestType === 'reducing_balance' ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                            Reducing Balance
                        </button>
                    </div>
                </div>

                {{-- Repayment Frequency Pill Toggle --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 mb-2">Repayment Frequency</p>
                    <div class="flex rounded-xl bg-gray-100 dark:bg-zinc-800 p-1">
                        @foreach(['weekly' => 'Weekly', 'biweekly' => 'Bi-Weekly', 'monthly' => 'Monthly'] as $val => $label)
                        <button wire:click="$set('repaymentFrequency', '{{ $val }}')"
                                class="flex-1 py-2 text-xs font-semibold rounded-lg transition-colors
                                    {{ $repaymentFrequency === $val ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Calculate Button --}}
                <button wire:click="calculate"
                        class="w-full py-3 rounded-xl bg-gradient-to-r from-oe to-oe text-white text-sm font-bold shadow-lg shadow-oe/20 hover:from-oe-hover hover:to-oe-hover transition-all flex items-center justify-center gap-2">
                    <svg wire:loading.remove wire:target="calculate" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <svg wire:loading wire:target="calculate" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Calculate Schedule
                </button>

            </div>
        </div>

        {{-- ── RIGHT: Results Panel ── --}}
        <div class="lg:col-span-3 flex flex-col gap-4">
            @if($result)
            @php
                $principalRatio = $result['total_payable'] > 0 ? round(100 - $result['interest_ratio'], 1) : 100;
                $altLabel = $interestType === 'flat' ? 'Reducing Balance' : 'Flat Rate';
            @endphp

            {{-- Hero: Total Payable + Installment --}}
            <div class="bg-gradient-to-br from-oe to-oe-hover rounded-2xl p-6 text-white relative overflow-hidden shadow-xl shadow-oe/25">
                <div class="absolute -right-8 -top-8 w-40 h-40 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-4 -bottom-6 w-32 h-32 bg-blue-900/30 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative">
                    <p class="text-xs font-semibold text-white/60 uppercase tracking-wider mb-1">Total Payable</p>
                    <p class="text-4xl font-black tracking-tight">TZS {{ number_format($result['total_payable'], 0) }}</p>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="bg-white/10 rounded-xl p-3">
                            <p class="text-[10px] text-white/60 uppercase font-bold">Financed</p>
                            <p class="text-sm font-black mt-0.5">TZS {{ number_format($result['financed_principal'], 0) }}</p>
                        </div>
                        <div class="bg-white/10 rounded-xl p-3">
                            <p class="text-[10px] text-white/60 uppercase font-bold">Interest</p>
                            <p class="text-sm font-black mt-0.5 text-amber-300">TZS {{ number_format($result['total_interest'], 0) }}</p>
                        </div>
                        <div class="bg-emerald-500/30 border border-emerald-400/30 rounded-xl p-3">
                            <p class="text-[10px] text-white/70 uppercase font-bold">{{ $result['frequency_label'] }} Pay</p>
                            <p class="text-sm font-black mt-0.5 text-emerald-300">TZS {{ number_format($result['installment_per_pay'], 0) }}</p>
                            <p class="text-[9px] text-white/50 mt-0.5">× {{ $result['installments'] }} payments</p>
                        </div>
                    </div>

                    <p class="text-[10px] text-white/40 mt-3">
                        {{ $interestType === 'flat' ? 'Flat rate' : 'Reducing balance' }} · {{ $durationWeeks }} weeks · {{ ucfirst($repaymentFrequency) }}
                    </p>
                </div>
            </div>

            {{-- Interest / Principal Ratio Bar --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Cost Breakdown</p>
                    <p class="text-xs font-semibold text-gray-400">{{ $result['interest_ratio'] }}% is interest cost</p>
                </div>
                <div class="h-5 rounded-full overflow-hidden flex bg-gray-100 dark:bg-zinc-800">
                    <div class="h-full bg-blue-500 flex items-center justify-center transition-all duration-500"
                         style="width: {{ $principalRatio }}%">
                        @if($principalRatio > 15)
                        <span class="text-[9px] font-black text-white px-1">Principal {{ $principalRatio }}%</span>
                        @endif
                    </div>
                    <div class="h-full bg-amber-400 flex items-center justify-center flex-1"
                         style="width: {{ $result['interest_ratio'] }}%">
                        @if($result['interest_ratio'] > 10)
                        <span class="text-[9px] font-black text-white px-1">Interest {{ $result['interest_ratio'] }}%</span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-4 mt-2 text-[10px] text-gray-400">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-blue-500 inline-block"></span> Principal (TZS {{ number_format($result['financed_principal'], 0) }})</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-amber-400 inline-block"></span> Interest (TZS {{ number_format($result['total_interest'], 0) }})</span>
                </div>
            </div>

            {{-- Method Comparison --}}
            @if($comparison)
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-5">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Method Comparison</p>
                <div class="grid grid-cols-2 gap-3">
                    {{-- Current method --}}
                    <div class="rounded-xl border-2 border-oe bg-oe-soft dark:bg-oe/10 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-bold text-oe dark:text-oe">
                                {{ $interestType === 'flat' ? 'Flat Rate' : 'Reducing Balance' }}
                            </p>
                            <span class="px-1.5 py-0.5 bg-oe text-white text-[9px] font-bold rounded-full">SELECTED</span>
                        </div>
                        <p class="text-lg font-black text-gray-900 dark:text-white">TZS {{ number_format($result['total_payable'], 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Interest: TZS {{ number_format($result['total_interest'], 0) }}</p>
                        <p class="text-xs text-gray-500">Per install: TZS {{ number_format($result['installment_per_pay'], 0) }}</p>
                        <p class="text-[10px] text-oe-hover mt-1 font-semibold">{{ $result['interest_ratio'] }}% cost ratio</p>
                    </div>
                    {{-- Comparison method --}}
                    <div class="rounded-xl border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/50 p-4">
                        <p class="text-xs font-bold text-gray-500 mb-2">{{ $altLabel }}</p>
                        <p class="text-lg font-black text-gray-700 dark:text-gray-300">TZS {{ number_format($comparison['total_payable'], 0) }}</p>
                        <p class="text-xs text-gray-400 mt-1">Interest: TZS {{ number_format($comparison['total_interest'], 0) }}</p>
                        <p class="text-xs text-gray-400">Per install: TZS {{ number_format($comparison['installment_per_pay'], 0) }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ $comparison['interest_ratio'] }}% cost ratio</p>
                    </div>
                </div>
                @php $saving = abs($result['total_payable'] - $comparison['total_payable']); @endphp
                @if($saving > 0)
                <div class="mt-3 px-3 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/40">
                    <p class="text-xs text-emerald-700 dark:text-emerald-300 font-semibold">
                        {{ $result['total_payable'] < $comparison['total_payable'] ? 'Selected method saves' : $altLabel . ' would save' }}
                        <strong>TZS {{ number_format($saving, 0) }}</strong> in total interest
                    </p>
                </div>
                @endif
            </div>
            @endif

            {{-- Show/Hide Amortization Schedule --}}
            @if(count($schedule))
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                <button wire:click="toggleSchedule"
                        class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    <div class="flex items-center gap-2">
                        <flux:icon name="table-cells" class="size-4 text-oe-hover" />
                        <p class="text-sm font-bold text-gray-800 dark:text-white">
                            Amortization Schedule
                        </p>
                        <span class="px-2 py-0.5 rounded-full bg-oe-soft dark:bg-oe/10 text-oe dark:text-oe text-[10px] font-bold">
                            {{ count($schedule) }} rows{{ $result['installments'] > 60 ? ' (first 60 shown)' : '' }}
                        </span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform {{ $showSchedule ? 'rotate-180' : '' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                @if($showSchedule)
                <div class="border-t border-gray-100 dark:border-zinc-800 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-zinc-800 border-b border-gray-100 dark:border-zinc-700">
                            <tr>
                                <th class="px-3 py-2.5 text-left font-bold text-gray-400 uppercase tracking-wider">#</th>
                                <th class="px-3 py-2.5 text-left font-bold text-gray-400 uppercase tracking-wider">Due Date</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider">Principal</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider">Interest</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider">Installment</th>
                                <th class="px-3 py-2.5 text-right font-bold text-gray-400 uppercase tracking-wider">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                            @foreach($schedule as $row)
                            @php
                                $rowFade = $row['balance'] === 0 ? 'bg-emerald-50/40 dark:bg-emerald-900/10' : '';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors {{ $rowFade }}">
                                <td class="px-3 py-2 font-mono text-gray-400 font-semibold">{{ $row['no'] }}</td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['due_date'] }}</td>
                                <td class="px-3 py-2 text-right text-oe dark:text-oe font-semibold">
                                    {{ number_format($row['principal'], 0) }}
                                </td>
                                <td class="px-3 py-2 text-right text-amber-600 dark:text-amber-400">
                                    {{ number_format($row['interest'], 0) }}
                                </td>
                                <td class="px-3 py-2 text-right font-bold text-gray-900 dark:text-white">
                                    {{ number_format($row['amount'], 0) }}
                                </td>
                                <td class="px-3 py-2 text-right {{ $row['balance'] > 0 ? 'text-gray-700 dark:text-gray-200' : 'text-emerald-600 dark:text-emerald-400 font-bold' }}">
                                    {{ $row['balance'] > 0 ? number_format($row['balance'], 0) : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-zinc-800/80 border-t-2 border-gray-200 dark:border-zinc-600">
                            <tr>
                                <td colspan="2" class="px-3 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Totals</td>
                                <td class="px-3 py-2.5 text-right text-xs font-black text-oe dark:text-oe">
                                    TZS {{ number_format(collect($schedule)->sum('principal'), 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right text-xs font-black text-amber-600 dark:text-amber-400">
                                    TZS {{ number_format(collect($schedule)->sum('interest'), 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right text-xs font-black text-gray-900 dark:text-white">
                                    TZS {{ number_format(collect($schedule)->sum('amount'), 0) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    @if($result['installments'] > 60)
                    <div class="px-5 py-3 bg-amber-50 dark:bg-amber-900/20 border-t border-amber-100 dark:border-amber-900/40">
                        <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold">
                            Showing first 60 of {{ $result['installments'] }} installments. Full schedule generated at loan disbursement.
                        </p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @endif

            @else
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center min-h-[24rem] bg-gray-50 dark:bg-zinc-900 rounded-2xl border border-dashed border-gray-200 dark:border-zinc-700">
                <svg class="w-16 h-16 text-gray-200 dark:text-zinc-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <p class="font-semibold text-gray-400">No results yet</p>
                <p class="text-xs text-gray-400 mt-1">Fill the parameters and click <strong class="text-oe-hover">Calculate Schedule</strong></p>
                <div class="mt-6 grid grid-cols-3 gap-3 text-center">
                    <div class="px-4 py-3 bg-white dark:bg-zinc-800 rounded-xl border border-gray-100 dark:border-zinc-700">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Flat Rate</p>
                        <p class="text-xs text-gray-500 mt-1">Equal interest each period</p>
                    </div>
                    <div class="px-4 py-3 bg-white dark:bg-zinc-800 rounded-xl border border-gray-100 dark:border-zinc-700">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Reducing</p>
                        <p class="text-xs text-gray-500 mt-1">Interest on outstanding balance</p>
                    </div>
                    <div class="px-4 py-3 bg-white dark:bg-zinc-800 rounded-xl border border-gray-100 dark:border-zinc-700">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Compare</p>
                        <p class="text-xs text-gray-500 mt-1">Side-by-side cost analysis</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

</div>
