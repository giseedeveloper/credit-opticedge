<div class="flex flex-col gap-6 p-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Dashboard</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                    {{ now()->format('l, F j, Y') }} — Opticedge Credit Overview
                </p>
            </div>
            <flux:badge color="purple" size="sm">
                <flux:icon name="signal" class="size-3 mr-1" />
                Live
            </flux:badge>
        </div>

        {{-- KPI Cards Row 1 --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            {{-- Active Loans --}}
            <div class="rounded-2xl bg-gradient-to-br from-purple-700 to-indigo-800 p-5 text-white shadow-lg shadow-purple-900/30">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-purple-200 uppercase tracking-wider">Active Loans</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10">
                        <flux:icon name="document-text" class="size-4 text-white" />
                    </div>
                </div>
                <div class="text-3xl font-bold">{{ number_format($stats['active_loans']) }}</div>
                <div class="mt-1 text-xs text-purple-300">Portfolio running</div>
            </div>

            {{-- Overdue Loans --}}
            <div class="rounded-2xl bg-gradient-to-br from-rose-700 to-red-800 p-5 text-white shadow-lg shadow-red-900/30">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-rose-200 uppercase tracking-wider">Overdue</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10">
                        <flux:icon name="exclamation-triangle" class="size-4 text-white" />
                    </div>
                </div>
                <div class="text-3xl font-bold">{{ number_format($stats['overdue_loans']) }}</div>
                <div class="mt-1 text-xs text-rose-300">Require attention</div>
            </div>

            {{-- Today's Collections --}}
            <div class="rounded-2xl bg-gradient-to-br from-emerald-700 to-teal-800 p-5 text-white shadow-lg shadow-emerald-900/30">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-emerald-200 uppercase tracking-wider">Today</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10">
                        <flux:icon name="currency-dollar" class="size-4 text-white" />
                    </div>
                </div>
                <div class="text-3xl font-bold">{{ number_format($stats['daily_collections'], 0) }}</div>
                <div class="mt-1 text-xs text-emerald-300">TZS collected</div>
            </div>

            {{-- Total Customers --}}
            <div class="rounded-2xl bg-gradient-to-br from-sky-700 to-blue-800 p-5 text-white shadow-lg shadow-sky-900/30">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-sky-200 uppercase tracking-wider">Customers</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10">
                        <flux:icon name="users" class="size-4 text-white" />
                    </div>
                </div>
                <div class="text-3xl font-bold">{{ number_format($stats['total_customers']) }}</div>
                <div class="mt-1 text-xs text-sky-300">Registered</div>
            </div>
        </div>

        {{-- KPI Cards Row 2 --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-5 shadow-sm">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider font-semibold mb-2">HQ Stock</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($stats['hq_stock']) }}</div>
                <div class="text-xs text-zinc-400 mt-1">Units at HQ</div>
            </div>

            <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-5 shadow-sm">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider font-semibold mb-2">Vendor Stock</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($stats['vendor_stock']) }}</div>
                <div class="text-xs text-zinc-400 mt-1">Distributed</div>
            </div>

            <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-5 shadow-sm">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider font-semibold mb-2">Pending KYC</div>
                <div class="text-2xl font-bold text-amber-500">{{ number_format($stats['pending_kyc']) }}</div>
                <div class="text-xs text-zinc-400 mt-1">Unverified customers</div>
            </div>

            <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-5 shadow-sm">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider font-semibold mb-2">Monthly Collections</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($stats['monthly_collections'], 0) }}</div>
                <div class="text-xs text-zinc-400 mt-1">TZS this month</div>
            </div>
        </div>

        {{-- Portfolio & Recent Loans --}}
        <div class="grid gap-4 md:grid-cols-3">
            {{-- Portfolio Summary --}}
            <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">Portfolio Balance</flux:heading>
                    <flux:icon name="chart-bar" class="size-5 text-purple-500" />
                </div>

                <div class="text-3xl font-bold text-zinc-900 dark:text-white mb-1">
                    TZS {{ number_format($stats['total_portfolio'], 0) }}
                </div>
                <flux:text size="sm" class="text-zinc-500">outstanding across all active loans</flux:text>

                <div class="mt-6 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">Sold Units</span>
                        <flux:badge color="green" size="sm">{{ $stats['sold_units'] }}</flux:badge>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">Active Loans</span>
                        <flux:badge color="purple" size="sm">{{ $stats['active_loans'] }}</flux:badge>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">Overdue Loans</span>
                        <flux:badge color="red" size="sm">{{ $stats['overdue_loans'] }}</flux:badge>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                    <flux:button :href="route('credit.panel')" size="sm" variant="ghost" wire:navigate class="w-full">
                        View Credit Control →
                    </flux:button>
                </div>
            </div>

            {{-- Recent Loans --}}
            <div class="md:col-span-2 rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">Recent Loans</flux:heading>
                    <flux:button :href="route('credit.panel')" size="xs" variant="ghost" wire:navigate>View All</flux:button>
                </div>

                @if($recentLoans->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <flux:icon name="document-text" class="size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
                        <flux:text size="sm" class="text-zinc-500">No loans disbursed yet</flux:text>
                    </div>
                @else
                    <div class="overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 dark:border-zinc-700">
                                    <th class="pb-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Loan #</th>
                                    <th class="pb-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Customer</th>
                                    <th class="pb-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Amount</th>
                                    <th class="pb-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-50 dark:divide-zinc-700/50">
                                @foreach($recentLoans as $loan)
                                    <tr class="group hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                                        <td class="py-3 font-mono text-xs text-purple-600 dark:text-purple-400">
                                            {{ $loan->loan_number }}
                                        </td>
                                        <td class="py-3 text-zinc-900 dark:text-zinc-100">
                                            {{ $loan->customer?->full_name ?? '—' }}
                                        </td>
                                        <td class="py-3 text-zinc-900 dark:text-zinc-100">
                                            TZS {{ number_format($loan->principal_amount, 0) }}
                                        </td>
                                        <td class="py-3">
                                            @php
                                                $statusColor = match($loan->status) {
                                                    'active'    => 'green',
                                                    'overdue'   => 'red',
                                                    'completed' => 'blue',
                                                    'pending'   => 'yellow',
                                                    default     => 'zinc',
                                                };
                                            @endphp
                                            <flux:badge :color="$statusColor" size="sm">
                                                {{ ucfirst($loan->status) }}
                                            </flux:badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="rounded-2xl bg-gradient-to-r from-purple-900/50 to-indigo-900/50 border border-purple-800/30 p-6">
            <flux:heading size="sm" class="text-white mb-4">Quick Actions</flux:heading>
            <div class="flex flex-wrap gap-3">
                <flux:button :href="route('kyc.customers')" variant="ghost" size="sm" wire:navigate icon="user-plus"
                    class="!text-purple-200 !border-purple-700/50 hover:!border-purple-500">
                    New Customer
                </flux:button>
                <flux:button :href="route('stock.imei')" variant="ghost" size="sm" wire:navigate icon="magnifying-glass"
                    class="!text-purple-200 !border-purple-700/50 hover:!border-purple-500">
                    IMEI Search
                </flux:button>
                <flux:button :href="route('credit.calculator')" variant="ghost" size="sm" wire:navigate icon="calculator"
                    class="!text-purple-200 !border-purple-700/50 hover:!border-purple-500">
                    Loan Calculator
                </flux:button>
                <flux:button :href="route('financials.collections')" variant="ghost" size="sm" wire:navigate icon="currency-dollar"
                    class="!text-purple-200 !border-purple-700/50 hover:!border-purple-500">
                    Record Payment
                </flux:button>
                <flux:button :href="route('kyc.pending')" variant="ghost" size="sm" wire:navigate icon="clock"
                    class="!text-purple-200 !border-purple-700/50 hover:!border-purple-500">
                    Verify KYC ({{ $stats['pending_kyc'] }})
                </flux:button>
            </div>
        </div>

</div>
