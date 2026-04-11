<div wire:init="loadData">

    {{-- ── Toast ── --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : 'bg-red-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2" style="display:none">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- ── Header ── --}}
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="chart-bar-square" size="lg" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Executive Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Live credit portfolio intelligence &amp; market velocity.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden sm:flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-3 py-1.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-ping"></span> Live
            </span>
            <flux:button variant="primary" wire:click="refreshFeeds" wire:loading.attr="disabled" icon="arrow-path">
                <span wire:loading.remove wire:target="refreshFeeds">Sync</span>
                <span wire:loading wire:target="refreshFeeds">Syncing…</span>
            </flux:button>
        </div>
    </div>

    {{-- ── Quick Actions ── --}}
    <div class="flex flex-wrap gap-2 mb-5">
        @php
        $actions = [
            ['label' => 'New Loan',      'icon' => 'plus-circle',       'href' => route('credit.panel'),    'color' => 'bg-orange-500 hover:bg-orange-600 text-white'],
            ['label' => 'KYC Register',  'icon' => 'identification',    'href' => route('kyc.wizard'),      'color' => 'bg-orange-500 hover:bg-orange-600 text-white'],
            ['label' => 'View Loans',    'icon' => 'document-text',     'href' => route('credit.panel'),    'color' => 'bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-zinc-700'],
            ['label' => 'Stock Manager', 'icon' => 'device-phone-mobile','href' => route('stock.index'),    'color' => 'bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-zinc-700'],
            ['label' => 'Schedules',     'icon' => 'calendar-days',     'href' => route('credit.schedules'),'color' => 'bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-zinc-700'],
            ['label' => 'Collections',   'icon' => 'banknotes',         'href' => route('financials.collections'),'color' => 'bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-zinc-700'],
        ];
        @endphp
        @foreach($actions as $action)
        <a href="{{ $action['href'] }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-all {{ $action['color'] }}">
            <x-fluent-icon :name="$action['icon']" size="xs" />
            {{ $action['label'] }}
        </a>
        @endforeach
    </div>

    {{-- ── Overdue Alert Banner ── --}}
    @if($readyToLoad && $overdueCount > 0)
    <div class="mb-5 flex items-center justify-between bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-2xl px-5 py-3.5">
        <div class="flex items-center gap-3">
            <x-fluent-icon name="exclamation-triangle" palette="rose" />
            <div>
                <p class="font-bold text-red-700 dark:text-red-400 text-sm">
                    {{ number_format($overdueCount) }} {{ Str::plural('loan', $overdueCount) }} overdue or in default
                </p>
                <p class="text-xs text-red-500 dark:text-red-500 mt-0.5">Immediate follow-up required. Portfolio at risk.</p>
            </div>
        </div>
        <a href="{{ route('credit.defaulters') }}" wire:navigate
           class="flex-shrink-0 text-xs font-bold text-red-600 dark:text-red-400 hover:underline flex items-center gap-1">
            View Defaulters →
        </a>
    </div>
    @endif

    {{-- ── Primary KPIs ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        {{-- Portfolio --}}
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-orange-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="banknotes" palette="amber" size="sm" />
                <span class="text-xs font-semibold text-orange-100 uppercase tracking-wider">Portfolio</span>
            </div>
            @if($readyToLoad)
                <p class="text-xl font-black truncate">TZS {{ number_format($portfolioValue, 0) }}</p>
                <p class="text-xs text-orange-100 mt-1">Active loan book</p>
            @else
                <div class="h-7 bg-white/20 rounded-lg w-3/4 animate-pulse mt-1"></div>
                <div class="h-3 bg-white/10 rounded w-1/2 animate-pulse mt-2"></div>
            @endif
        </div>

        {{-- Collection Rate --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="arrow-trending-up" palette="emerald" size="sm" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Collection Rate</span>
            </div>
            @if($readyToLoad)
                <p class="text-xl font-black text-gray-900 dark:text-white">{{ $collectionEfficiency }}%</p>
                <p class="text-xs text-gray-400 mt-1">This month</p>
            @else
                <div class="h-7 bg-gray-100 dark:bg-zinc-800 rounded-lg w-1/2 animate-pulse mt-1"></div>
                <div class="h-3 bg-gray-50 dark:bg-zinc-700 rounded w-1/3 animate-pulse mt-2"></div>
            @endif
        </div>

        {{-- PAR > 30 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="exclamation-triangle" palette="rose" size="sm" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">PAR &gt; 30</span>
            </div>
            @if($readyToLoad)
                <p class="text-xl font-black text-gray-900 dark:text-white">{{ $parPercentage }}%</p>
                <p class="text-xs text-rose-500 mt-1 font-semibold">Risk exposure</p>
            @else
                <div class="h-7 bg-gray-100 dark:bg-zinc-800 rounded-lg w-1/2 animate-pulse mt-1"></div>
                <div class="h-3 bg-gray-50 dark:bg-zinc-700 rounded w-1/3 animate-pulse mt-2"></div>
            @endif
        </div>

        {{-- Active Devices --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="device-phone-mobile" palette="sky" size="sm" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Devices</span>
            </div>
            @if($readyToLoad)
                <p class="text-xl font-black text-gray-900 dark:text-white">{{ number_format($activeDevices) }}</p>
                <p class="text-xs text-gray-400 mt-1">In the field</p>
            @else
                <div class="h-7 bg-gray-100 dark:bg-zinc-800 rounded-lg w-2/3 animate-pulse mt-1"></div>
                <div class="h-3 bg-gray-50 dark:bg-zinc-700 rounded w-1/2 animate-pulse mt-2"></div>
            @endif
        </div>
    </div>

    {{-- ── Secondary KPIs (clickable) ── --}}
    @php
    $secKpis = [
        ['label'=>'Active Loans',     'value'=> number_format($totalActiveLoans),  'icon'=>'document-text',      'bg'=>'bg-orange-100 dark:bg-orange-900/30', 'ic'=>'text-orange-500 dark:text-orange-400', 'href'=> route('credit.panel')],
        ['label'=>'Customers',        'value'=> number_format($totalCustomers),    'icon'=>'users',              'bg'=>'bg-sky-100 dark:bg-sky-900/30',      'ic'=>'text-sky-600 dark:text-sky-400',      'href'=> route('kyc.customers')],
        ['label'=>'New Loans (MTD)',   'value'=> number_format($newLoansThisMonth), 'icon'=>'plus-circle',        'bg'=>'bg-amber-100 dark:bg-amber-900/30',  'ic'=>'text-amber-600 dark:text-amber-400',  'href'=> route('credit.panel')],
        ['label'=>'MTD Collections',  'value'=>'TZS '.number_format($monthCollections,0), 'icon'=>'banknotes',  'bg'=>'bg-teal-100 dark:bg-teal-900/30',    'ic'=>'text-teal-600 dark:text-teal-400',    'href'=> route('financials.collections')],
        ['label'=>'Overdue / Default','value'=> number_format($overdueCount),      'icon'=>'exclamation-circle', 'bg'=>'bg-red-100 dark:bg-red-900/30',      'ic'=>'text-red-600 dark:text-red-400',      'href'=> route('credit.defaulters'),  'alert'=>$overdueCount > 0],
        ['label'=>'Available Stock',  'value'=> number_format($availableStockCount),'icon'=>'device-phone-mobile','bg'=>'bg-orange-100 dark:bg-orange-900/30',   'ic'=>'text-orange-500 dark:text-orange-400',    'href'=> route('stock.index')],
    ];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-6">
        @foreach($secKpis as $k)
        <a href="{{ $k['href'] }}" wire:navigate
           class="group bg-white dark:bg-zinc-900 rounded-xl border {{ isset($k['alert']) && $k['alert'] ? 'border-red-200 dark:border-red-800' : 'border-gray-100 dark:border-zinc-800' }} p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-all">
            <x-fluent-icon :name="$k['icon']" size="sm" class="flex-shrink-0" />
            <div class="min-w-0">
                <p class="text-[10px] text-gray-400 truncate uppercase tracking-wider">{{ $k['label'] }}</p>
                @if($readyToLoad)
                    <p class="text-base font-black {{ isset($k['alert']) && $k['alert'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} truncate">{{ $k['value'] }}</p>
                @else
                    <div class="h-6 bg-gray-100 dark:bg-zinc-800 rounded w-14 animate-pulse mt-0.5"></div>
                @endif
            </div>
        </a>
        @endforeach
    </div>

    {{-- ── Charts + Live Feed ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Charts --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Cashflow Area Chart --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">Weekly Cashflow</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Collections vs Disbursements (TZS) — last 5 weeks</p>
                    </div>
                    <span class="text-xs font-semibold text-gray-400 bg-gray-50 dark:bg-zinc-800 px-2.5 py-1 rounded-lg">Live</span>
                </div>
                <div class="p-4" wire:ignore>
                    <div id="cashflow-area-chart" style="min-height:280px;"></div>
                </div>
            </div>

            {{-- Risk + Donut row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800">
                        <h3 class="font-bold text-gray-900 dark:text-white text-center">Portfolio Risk Meter</h3>
                    </div>
                    <div class="p-2" wire:ignore>
                        <div id="risk-radial-chart" style="min-height:220px;"></div>
                    </div>
                    <p class="pb-4 text-center text-xs text-gray-400">Stage 1 → Stage 2 → Stage 3</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800">
                        <h3 class="font-bold text-gray-900 dark:text-white text-center">Hardware Market Share</h3>
                    </div>
                    <div class="p-2" wire:ignore>
                        <div id="inventory-donut-chart" style="min-height:220px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Live Feed --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden sticky top-4 flex flex-col" style="max-height:640px;">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="font-bold text-orange-500 dark:text-orange-300">Live Activity</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Recent loan disbursements</p>
                    </div>
                    <span class="flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-ping"></span> Live
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <ul class="divide-y divide-gray-50 dark:divide-zinc-800">
                        @if(! $readyToLoad)
                            @for($i = 0; $i < 6; $i++)
                                <li class="p-4 flex gap-3 animate-pulse">
                                    <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-zinc-800 flex-shrink-0"></div>
                                    <div class="flex-1 space-y-2">
                                        <div class="h-4 bg-gray-200 dark:bg-zinc-800 rounded w-1/2"></div>
                                        <div class="h-3 bg-gray-100 dark:bg-zinc-700 rounded w-1/3"></div>
                                    </div>
                                    <div class="w-14 h-4 bg-gray-200 dark:bg-zinc-800 rounded flex-shrink-0"></div>
                                </li>
                            @endfor
                        @else
                            @forelse($liveSalesFeed as $feed)
                                <li class="hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors">
                                    <a href="{{ route('credit.panel') }}" wire:navigate class="p-4 flex gap-3 items-start block">
                                        <div class="w-9 h-9 rounded-full bg-orange-500/10 dark:bg-orange-900/30 flex items-center justify-center text-orange-500 dark:text-orange-300 font-black text-xs border border-orange-500/15 flex-shrink-0 uppercase">
                                            {{ substr($feed['device']['brand'] ?? '?', 0, 2) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $feed['customer']['first_name'] ?? 'Unknown' }} {{ $feed['customer']['last_name'] ?? '' }}
                                            </p>
                                            <p class="text-xs text-gray-400 truncate mt-0.5">
                                                {{ $feed['device']['brand'] }} · {{ $feed['device']['model'] }}
                                            </p>
                                            <p class="text-[10px] text-gray-300 dark:text-zinc-500 mt-1 font-mono">
                                                {{ $feed['loan_number'] }} · {{ $feed['created_at'] ? \Carbon\Carbon::parse($feed['created_at'])->diffForHumans() : 'Just now' }}
                                            </p>
                                        </div>
                                        <div class="text-right flex-shrink-0 space-y-1">
                                            <p class="text-xs font-black text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                                TZS {{ number_format(($feed['principal_amount'] ?? 0) / 1000, 0) }}K
                                            </p>
                                            @php $s = $feed['status'] ?? 'pending'; @endphp
                                            <span @class([
                                                'text-[10px] font-semibold px-1.5 py-0.5 rounded-full',
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' => $s === 'active',
                                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'             => $s === 'defaulted',
                                                'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'                 => $s === 'completed',
                                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'         => ! in_array($s, ['active','defaulted','completed']),
                                            ])>{{ ucfirst($s) }}</span>
                                        </div>
                                    </a>
                                </li>
                            @empty
                                <div class="p-10 text-center">
                                    <flux:icon name="bolt-slash" class="size-8 text-gray-300 mx-auto mb-2" />
                                    <p class="text-sm text-gray-400">No recent activity.</p>
                                </div>
                            @endforelse
                        @endif
                    </ul>
                </div>

                <div class="p-3 border-t border-gray-100 dark:border-zinc-800 text-center flex-shrink-0">
                    <a href="{{ route('credit.panel') }}" wire:navigate class="text-xs font-bold text-orange-500 dark:text-orange-400 hover:underline">
                        View All Loans →
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Bottom Panels ── --}}
    @if($readyToLoad)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-6">

        {{-- Due Today / Overdue Payments --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-amber-100 dark:border-amber-900/40 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-amber-100 dark:border-amber-900/30 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">Due &amp; Overdue Payments</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Schedules requiring collection</p>
                </div>
                <span class="text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 px-2 py-0.5 rounded-full">
                    {{ count($todayDuePayments) }}
                </span>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-zinc-800 max-h-72 overflow-y-auto">
                @forelse($todayDuePayments as $due)
                <div class="px-5 py-3 flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $due['customer_name'] ?: '—' }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $due['loan_number'] }} · {{ $due['due_date'] }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-red-600">TZS {{ number_format($due['balance']) }}</p>
                        <span class="text-[10px] {{ $due['status'] === 'overdue' ? 'text-red-500' : 'text-amber-500' }} font-semibold uppercase">
                            {{ $due['status'] }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="px-5 py-10 text-center">
                    <flux:icon name="check-circle" class="size-8 text-teal-300 mx-auto mb-2" />
                    <p class="text-sm text-gray-400">All caught up!</p>
                </div>
                @endforelse
            </div>
            <div class="px-5 py-3 border-t border-gray-50 dark:border-zinc-800">
                <a href="{{ route('credit.schedules') }}" wire:navigate class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                    View All Schedules →
                </a>
            </div>
        </div>

        {{-- Recent Repayments --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">Recent Repayments</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Last 7 days</p>
                </div>
                <span class="text-xs font-semibold bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400 px-2 py-0.5 rounded-full">
                    {{ count($recentTransactions) }}
                </span>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-zinc-800 max-h-72 overflow-y-auto">
                @forelse($recentTransactions as $txn)
                <div class="px-5 py-3 flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $txn['customer_name'] ?: '—' }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $txn['loan_number'] }} · {{ $txn['transacted_at'] }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-teal-600">TZS {{ number_format($txn['amount']) }}</p>
                        <span class="text-[10px] text-gray-400 capitalize">{{ $txn['channel'] }}</span>
                    </div>
                </div>
                @empty
                <div class="px-5 py-10 text-center">
                    <flux:icon name="inbox" class="size-8 text-gray-200 mx-auto mb-2" />
                    <p class="text-sm text-gray-400">No recent repayments</p>
                </div>
                @endforelse
            </div>
            <div class="px-5 py-3 border-t border-gray-50 dark:border-zinc-800">
                <a href="{{ route('financials.collections') }}" wire:navigate class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline">
                    View All Collections →
                </a>
            </div>
        </div>

        {{-- Branch Performance + Top Overdue --}}
        <div class="flex flex-col gap-5">

            {{-- Branch Performance --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-800">
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">Branch Performance</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Active loans &amp; MTD collections</p>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-zinc-800">
                    @forelse($branchStats as $branch)
                    <div class="px-5 py-2.5 flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $branch['name'] }}</p>
                            <p class="text-[10px] text-gray-400">{{ $branch['active_loans'] }} active {{ Str::plural('loan', $branch['active_loans']) }}</p>
                        </div>
                        <p class="text-xs font-bold text-orange-500 dark:text-orange-400 flex-shrink-0">
                            TZS {{ number_format($branch['collections'] / 1000, 0) }}K
                        </p>
                    </div>
                    @empty
                    <div class="px-5 py-6 text-center text-xs text-gray-400">No branch data</div>
                    @endforelse
                </div>
            </div>

            {{-- Top Overdue Loans --}}
            @if(count($overdueLoansList))
            <div class="bg-red-50 dark:bg-red-950/20 rounded-2xl border border-red-100 dark:border-red-900/40 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-red-100 dark:border-red-900/30">
                    <h3 class="font-bold text-sm text-red-700 dark:text-red-400">Top Overdue Loans</h3>
                    <p class="text-xs text-red-400 mt-0.5">Highest outstanding balance</p>
                </div>
                <div class="divide-y divide-red-100/50 dark:divide-red-900/20">
                    @foreach($overdueLoansList as $ol)
                    <div class="px-5 py-2.5 flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $ol['customer_name'] ?: '—' }}</p>
                            <p class="text-[10px] text-gray-500 font-mono">{{ $ol['loan_number'] }} · {{ $ol['device'] }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs font-bold text-red-600">TZS {{ number_format($ol['outstanding_balance'] / 1000, 0) }}K</p>
                            <span class="text-[10px] font-semibold {{ $ol['status'] === 'defaulted' ? 'text-red-700' : 'text-amber-600' }} uppercase">
                                {{ $ol['status'] }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="px-5 py-3 border-t border-red-100 dark:border-red-900/30">
                    <a href="{{ route('credit.defaulters') }}" wire:navigate class="text-xs font-bold text-red-600 dark:text-red-400 hover:underline">
                        Manage Defaulters →
                    </a>
                </div>
            </div>
            @endif

        </div>
    </div>
    @endif

    @script
    <script>
        let cashflowChart = null;
        let riskChart     = null;
        let donutChart    = null;

        $wire.on('charts-loaded', ({ collections, disbursements, labels, risk, inventory }) => {
            const invSeries = Object.values(inventory);
            const invLabels = Object.keys(inventory);

            // ── 1. Cashflow area ──────────────────────────────────────
            if (!cashflowChart) {
                cashflowChart = new ApexCharts(document.querySelector('#cashflow-area-chart'), {
                    series: [
                        { name: 'Collections',   data: collections   },
                        { name: 'Disbursements', data: disbursements  },
                    ],
                    chart: {
                        type: 'area', height: 280,
                        toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent',
                        animations: { enabled: true, easing: 'easeinout', speed: 600 },
                    },
                    colors: ['#2563eb', '#60a5fa'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: [3, 2] },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.02, stops: [0, 90, 100] },
                    },
                    xaxis: {
                        categories: labels,
                        axisBorder: { show: false }, axisTicks: { show: false },
                        labels: { style: { colors: '#9ca3af', fontSize: '12px', fontWeight: 500 } },
                    },
                    yaxis: {
                        labels: {
                            formatter: v => (v / 1_000_000).toFixed(1) + 'M',
                            style: { colors: '#9ca3af', fontWeight: 500 },
                        },
                    },
                    grid: { borderColor: '#f3f4f6', strokeDashArray: 4, padding: { left: 8, right: 8 } },
                    legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', fontWeight: 600 },
                    tooltip: { y: { formatter: v => 'TZS ' + v.toLocaleString() } },
                    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                });
                cashflowChart.render();
            } else {
                cashflowChart.updateSeries([
                    { name: 'Collections',   data: collections   },
                    { name: 'Disbursements', data: disbursements  },
                ]);
                cashflowChart.updateOptions({ xaxis: { categories: labels } });
            }

            // ── 2. Risk radial ───────────────────────────────────────
            if (!riskChart) {
                riskChart = new ApexCharts(document.querySelector('#risk-radial-chart'), {
                    series: [risk],
                    chart: {
                        type: 'radialBar', height: 220,
                        background: 'transparent', fontFamily: 'inherit',
                    },
                    plotOptions: {
                        radialBar: {
                            startAngle: -120, endAngle: 120,
                            hollow: { size: '60%' },
                            track: { background: '#f3f4f6', strokeWidth: '100%' },
                            dataLabels: {
                                name: { show: true, fontSize: '11px', color: '#9ca3af', offsetY: 14 },
                                value: {
                                    show: true, fontSize: '26px', fontWeight: 800,
                                    color: '#2563eb', offsetY: -10,
                                    formatter: v => v + '%',
                                },
                            },
                        },
                    },
                    fill: {
                        type: 'gradient',
                        gradient: { shade: 'dark', type: 'horizontal', gradientToColors: ['#fb7185'], stops: [0, 100] },
                    },
                    stroke: { lineCap: 'round' },
                    colors: ['#2563eb'],
                    labels: ['At-Risk Loans'],
                    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                });
                riskChart.render();
            } else {
                riskChart.updateSeries([risk]);
            }

            // ── 3. Inventory donut ───────────────────────────────────
            if (!donutChart) {
                donutChart = new ApexCharts(document.querySelector('#inventory-donut-chart'), {
                    series: invSeries.length ? invSeries : [1],
                    labels: invLabels.length ? invLabels : ['No Data'],
                    chart: { type: 'donut', height: 220, background: 'transparent', fontFamily: 'inherit' },
                    colors: ['#2563eb', '#2563eb', '#60a5fa', '#c084fc', '#e9d5ff'],
                    dataLabels: { enabled: false },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '68%',
                                labels: {
                                    show: true,
                                    name:  { fontSize: '12px', color: '#6b7280' },
                                    value: { fontSize: '22px', fontWeight: 800, color: '#111827' },
                                    total: { show: true, label: 'Units', color: '#6b7280' },
                                },
                            },
                        },
                    },
                    stroke: { width: 2, colors: ['#fff'] },
                    legend: { position: 'bottom', fontSize: '12px', fontWeight: 600 },
                    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                });
                donutChart.render();
            } else {
                donutChart.updateSeries(invSeries);
                donutChart.updateOptions({ labels: invLabels });
            }
        });
    </script>
    @endscript

    <flux:toast />
</div>
