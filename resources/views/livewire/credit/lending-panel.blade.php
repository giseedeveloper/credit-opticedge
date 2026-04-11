<div>
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="banknotes" size="lg" palette="emerald" />
            <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Active Lending Control</h1>
            <p class="mt-1 text-sm text-gray-500">Monitor active portfolios and dispatch rapid collections logic.</p>
            </div>
        </div>
        
        <div class="flex gap-2">
            <flux:button variant="ghost" wire:click="dispatchBulkSMS" icon="chat-bubble-oval-left-ellipsis">
                Bulk SMS
            </flux:button>
            <flux:button variant="primary" wire:click="openDisbursementModal" icon="plus">
                New Loan
            </flux:button>
        </div>
    </div>

    <!-- Livewire Matrix Array -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: The Loan Table -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-xl border border-gray-100 dark:border-zinc-800">
                <!-- Toolbar -->
                <div class="p-4 border-b border-gray-100 dark:border-zinc-800 flex gap-4 bg-[#E5E4E2]/30 dark:bg-zinc-800 rounded-t-xl">
                    <div class="flex-1">
                        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search Customer or NIDA..." />
                    </div>
                    <div class="w-48">
                        <flux:select wire:model.live="filterStatus">
                            <flux:select.option value="all">All Portfolios</flux:select.option>
                            <flux:select.option value="active">Performing</flux:select.option>
                            <flux:select.option value="overdue">Overdue</flux:select.option>
                            <flux:select.option value="defaulted">Defaulted Level</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                <!-- Active Loans Table with Progress Bars -->
                <div class="overflow-x-auto max-h-[600px] no-scrollbar">
                    <table class="w-full text-xs text-left relative">
                        <thead class="text-xs text-gray-600 uppercase bg-gray-50 dark:bg-zinc-800 border-b border-gray-100 dark:border-zinc-700 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Total Value</th>
                                <th class="px-4 py-3">Repayment Progress</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse($loans as $loan)
                                @php
                                    $progress = 0;
                                    if ($loan->principal_amount > 0) {
                                        $paid = $loan->principal_amount - $loan->remaining_balance;
                                        $progress = round(($paid / $loan->principal_amount) * 100);
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                                     <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $loan->customer->first_name ?? '' }} {{ $loan->customer->last_name ?? 'Unknown' }}</div>
                                        <div class="text-[11px] text-gray-500">ID: {{ $loan->customer->nida_number ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                        TZS {{ number_format($loan->principal_amount) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-between text-[11px] mb-1">
                                            <span>{{ $progress }}% Complete</span>
                                            <span class="text-gray-500">{{ number_format($loan->remaining_balance) }} left</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-zinc-700">
                                            <div class="h-1.5 rounded-full @if($progress < 30) bg-rose-500 @elseif($progress < 80) bg-orange-500 @else bg-emerald-500 @endif" style="width: {{ $progress }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($loan->status === 'active')
                                            <flux:badge color="green" size="sm">Performing</flux:badge>
                                        @elseif($loan->status === 'overdue')
                                            <flux:badge color="yellow" size="sm">DPD {{ $loan->dpd ?? 0 }}</flux:badge>
                                        @elseif($loan->status === 'defaulted')
                                            <flux:badge color="red" size="sm">Stage 3 Loss</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:dropdown position="bottom-end">
                                            <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                                            <flux:menu>
                                                <flux:menu.item wire:click="downloadAgreement('{{ $loan->id }}')" icon="document-text">Print Agreement</flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item wire:click="confirmDeviceLock('{{ $loan->id }}')" icon="lock-closed" class="text-rose-600 dark:text-rose-400">Lock Device (MDM)</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                        <x-fluent-icon name="document-text" size="xl" class="mx-auto mb-3" />
                                        <p class="font-medium">No Loans Matched Filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 bg-[#E5E4E2]/30 dark:bg-zinc-800 border-t border-gray-100 dark:border-zinc-800 rounded-b-xl">
                    {{ $loans->links() }}
                </div>
            </div>
        </div>

        <!-- Right Col: Calculator Matrix -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-xl border border-blue-100 dark:border-zinc-800 sticky top-6">
                <div class="p-6 border-b border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <x-fluent-icon name="calculator" size="sm" palette="violet" />
                        <div>
                            <h3 class="font-bold text-lg text-orange-500 dark:text-blue-400">Live Simulator</h3>
                            <p class="text-sm text-gray-500">Real-time amortization pricing</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 space-y-4">
                    <flux:field>
                        <flux:label>Principal Borrowed</flux:label>
                        <flux:input wire:model.live="calcPrincipal" type="number" placeholder="TZS" />
                    </flux:field>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Term (Months)</flux:label>
                            <flux:input wire:model.live="calcMonths" type="number" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Base Rate (%)</flux:label>
                            <flux:input wire:model.live="calcInterestRate" type="number" step="0.1" />
                        </flux:field>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100 dark:border-zinc-700">
                        <p class="text-gray-500 text-sm mb-1">Expected Monthly Due</p>
                        <p class="text-3xl font-black @if($calcEmi > 0) text-orange-500 dark:text-white @else text-gray-300 @endif">
                            TZS {{ number_format($calcEmi, 2) }}
                        </p>
                    </div>

                    <flux:button variant="primary" class="w-full mt-4" href="{{ route('credit.panel') }}" wire:navigate>
                        Draft Contract
                    </flux:button>
                </div>
            </div>
        </div>

    </div>

    <flux:toast />

    <!-- ── New Loan Disbursement Modal ──────────────────────────────── -->
    <flux:modal wire:model="showDisbursementModal" class="max-w-2xl">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Disburse New Loan</h2>
            <p class="text-sm text-gray-500 mb-6">Create and activate a new device financing agreement.</p>

            <div class="space-y-5">
                {{-- Customer --}}
                <flux:field>
                    <flux:label>Search Verified Customer</flux:label>
                    <flux:input wire:model.live.debounce.300ms="customerSearch" icon="magnifying-glass" placeholder="Name or phone..." />
                    @if($customers->isNotEmpty())
                        <div class="mt-1 border border-gray-200 dark:border-zinc-700 rounded-lg max-h-40 overflow-y-auto bg-white dark:bg-zinc-900 shadow-lg">
                            @foreach($customers as $c)
                                <button type="button"
                                    wire:click="$set('newCustomerId', '{{ $c->id }}')"
                                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800 transition-colors @if($newCustomerId === $c->id) bg-blue-50 dark:bg-zinc-800 font-semibold text-orange-500 @endif">
                                    {{ $c->first_name }} {{ $c->last_name }}
                                    <span class="text-xs text-gray-400 ml-2">{{ $c->phone }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @if($newCustomerId)
                        @php $selected = $customers->firstWhere('id', $newCustomerId); @endphp
                        <div class="mt-1 flex items-center gap-2 text-sm text-orange-500 dark:text-blue-400">
                            <flux:icon name="check-circle" class="size-4" />
                            {{ $selected?->first_name }} {{ $selected?->last_name }} selected
                        </div>
                    @endif
                    <flux:error name="newCustomerId" />
                </flux:field>

                {{-- Inventory Unit --}}
                <flux:field>
                    <flux:label>Select Available Device</flux:label>
                    <flux:input wire:model.live.debounce.300ms="unitSearch" icon="device-phone-mobile" placeholder="IMEI or model name..." />
                    <div class="mt-1">
                        <flux:select wire:model="newUnitId">
                            <flux:select.option value="">-- Select unit --</flux:select.option>
                            @foreach($availableUnits as $unit)
                                <flux:select.option value="{{ $unit->id }}">
                                    {{ $unit->phoneModel?->brand?->name }} {{ $unit->phoneModel?->name }} — {{ $unit->imei_1 }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:error name="newUnitId" />
                </flux:field>

                {{-- Amounts --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Principal (TZS)</flux:label>
                        <flux:input wire:model="newPrincipal" type="number" placeholder="e.g. 500000" />
                        <flux:error name="newPrincipal" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Deposit Paid (TZS)</flux:label>
                        <flux:input wire:model="newDepositPaid" type="number" placeholder="0" />
                    </flux:field>
                </div>

                {{-- Rates & Duration --}}
                <div class="grid grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>Interest Rate (%/mo)</flux:label>
                        <flux:input wire:model="newInterestRate" type="number" step="0.1" />
                        <flux:error name="newInterestRate" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Interest Type</flux:label>
                        <flux:select wire:model="newInterestType">
                            <flux:select.option value="flat">Flat Rate</flux:select.option>
                            <flux:select.option value="reducing_balance">Reducing Balance</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Duration (Weeks)</flux:label>
                        <flux:input wire:model="newDurationWeeks" type="number" min="1" max="208" />
                        <flux:error name="newDurationWeeks" />
                    </flux:field>
                </div>

                {{-- Frequency --}}
                <flux:field>
                    <flux:label>Repayment Frequency</flux:label>
                    <flux:select wire:model="newFrequency">
                        <flux:select.option value="weekly">Weekly</flux:select.option>
                        <flux:select.option value="biweekly">Bi-Weekly</flux:select.option>
                        <flux:select.option value="monthly">Monthly</flux:select.option>
                    </flux:select>
                </flux:field>

                {{-- Notes --}}
                <flux:field>
                    <flux:label>Notes (optional)</flux:label>
                    <flux:textarea wire:model="newNotes" rows="2" placeholder="Any remarks..." />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-zinc-800">
                <flux:button variant="ghost" wire:click="$set('showDisbursementModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="disburseLoan" wire:loading.attr="disabled" icon="credit-card">
                    <span wire:loading.remove wire:target="disburseLoan">Disburse Loan</span>
                    <span wire:loading wire:target="disburseLoan">Processing...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Execute MDM Lock Modal -->
    <flux:modal wire:model="confirmingDeviceLock" class="max-w-md">
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Confirm MDM Enclosure Lock</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Are you sure you want to trigger the MDM API to lock this device? The user will be entirely blocked from hardware access until payment is verified.</p>
            
            @if($lockLoanId)
                @php $lockLoan = \App\Models\Loan::with(['customer', 'inventoryUnit'])->find($lockLoanId); @endphp
                <div class="bg-gray-50 dark:bg-zinc-900 p-4 rounded-lg border border-gray-100 dark:border-zinc-800 mb-6 relative overflow-hidden">
                    <div class="absolute right-0 top-0 bottom-0 w-2 bg-rose-500"></div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Target Hardware</p>
                    <p class="font-mono font-medium text-gray-900 dark:text-white mt-1">IMEI: {{ optional($lockLoan->inventoryUnit)->imei_1 ?? 'Pending Provision' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Customer: {{ optional($lockLoan->customer)->first_name }} {{ optional($lockLoan->customer)->last_name }}</p>
                </div>
            @endif

            <div class="flex justify-end gap-3 mt-4">
                <flux:button variant="ghost" wire:click="$set('confirmingDeviceLock', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="executeDeviceLock" icon="lock-closed">
                    Execute Hard Lock
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
