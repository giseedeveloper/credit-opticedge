<div class="flex flex-col gap-6" x-data="imeiSearch()">

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
        <x-fluent-icon name="magnifying-glass" size="lg" palette="blue" />
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">IMEI / Serial Search</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Instantly locate any device in the system by IMEI or serial number.</p>
        </div>
    </div>

    {{-- KPI Stats --}}
    @php
    $statuses = [
        ['key'=>'available',    'label'=>'Available',    'color'=>'teal'],
        ['key'=>'hq_stock',     'label'=>'HQ Stock',     'color'=>'purple'],
        ['key'=>'vendor_stock', 'label'=>'Vendor',       'color'=>'blue'],
        ['key'=>'in_transit',   'label'=>'In Transit',   'color'=>'amber'],
        ['key'=>'sold',         'label'=>'Sold',         'color'=>'green'],
        ['key'=>'returned',     'label'=>'Returned',     'color'=>'rose'],
    ];
    $colorMap = [
        'teal'   => 'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 border-teal-100 dark:border-teal-800',
        'purple' => 'bg-oe-soft dark:bg-oe/10 text-oe-hover dark:text-oe border-oe/20 dark:border-oe/25',
        'blue'   => 'bg-oe-soft dark:bg-oe/10 text-oe-hover dark:text-oe border-oe/20 dark:border-oe/25',
        'amber'  => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border-amber-100 dark:border-amber-800',
        'green'  => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border-emerald-100 dark:border-emerald-800',
        'rose'   => 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300 border-rose-100 dark:border-rose-800',
    ];
    @endphp
    <div class="grid grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($statuses as $s)
        <div class="rounded-2xl border px-4 py-3 {{ $colorMap[$s['color']] }}">
            <p class="text-[10px] font-semibold uppercase tracking-wider opacity-70">{{ $s['label'] }}</p>
            <p class="text-2xl font-black mt-1">{{ number_format($statusCounts[$s['key']] ?? 0) }}</p>
        </div>
        @endforeach
    </div>

    {{-- Hero Search Box --}}
    <div class="bg-gradient-to-br from-oe to-oe-hover rounded-2xl p-6 shadow-lg shadow-oe/20 relative overflow-hidden">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-6 -bottom-6 w-24 h-24 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative">
            <p class="text-white/70 text-xs font-semibold uppercase tracking-wider mb-3 flex items-center gap-2">
                <x-fluent-icon name="magnifying-glass" size="xs" />
                Enter IMEI 1, IMEI 2, or Serial Number
            </p>
            <div class="flex gap-3">
                <input
                    wire:model="query"
                    wire:keydown.enter="search"
                    @keydown.enter="$wire.search()"
                    placeholder="e.g. 358246095872143"
                    class="flex-1 bg-white/10 backdrop-blur border border-white/20 text-white placeholder-white/40 rounded-xl px-4 py-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-white/30"
                />
                <button wire:click="search" wire:loading.attr="disabled"
                        class="flex items-center gap-2 px-5 py-3 bg-white text-oe font-bold text-sm rounded-xl hover:bg-oe-soft transition-colors shadow disabled:opacity-60">
                    <svg wire:loading wire:target="search" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <flux:icon wire:loading.remove wire:target="search" name="magnifying-glass" class="size-4" />
                    <span wire:loading.remove wire:target="search">Search</span>
                    <span wire:loading wire:target="search">Searching…</span>
                </button>
            </div>
            <flux:error name="query" class="mt-2 text-rose-300 text-xs" />

            {{-- Recent Searches --}}
            @if(count($recentSearches))
            <div class="flex flex-wrap items-center gap-2 mt-4">
                <span class="text-white/50 text-xs">Recent:</span>
                @foreach($recentSearches as $recent)
                <button wire:click="searchRecent('{{ $recent }}')"
                        class="px-3 py-1 bg-white/10 hover:bg-white/20 border border-white/20 text-white/80 text-xs font-mono rounded-lg transition-colors">
                    {{ $recent }}
                </button>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Results --}}
    @if($searched)
        @if($result)
        @php
            $r      = $result;
            $specs  = $r->phoneModel?->specifications ?? [];
            $retail = (float) ($r->phoneModel?->retail_price ?? 0);
            $cost   = (float) ($r->purchase_price ?? 0);
            $margin = ($retail > 0 && $cost > 0) ? round(($retail - $cost) / $retail * 100, 1) : null;
            $statusColors = [
                'available'    => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
                'hq_stock'     => 'bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe',
                'vendor_stock' => 'bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe',
                'in_transit'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                'sold'         => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                'returned'     => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                'lost'         => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            ];
            $sc = $statusColors[$r->status] ?? 'bg-zinc-100 text-zinc-600';
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Left: Identity + Pricing + Specs + Location --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Device Header Card --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-oe to-oe-hover px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white">
                                <flux:icon name="device-phone-mobile" class="size-6" />
                            </div>
                            <div>
                                <p class="text-white/70 text-xs font-semibold uppercase tracking-wider">{{ $r->phoneModel?->brand?->name }}</p>
                                <h2 class="text-xl font-bold text-white mt-0.5">{{ $r->phoneModel?->name ?? 'Unknown Device' }}</h2>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1.5 rounded-xl text-xs font-bold {{ $sc }}">
                                {{ str_replace('_', ' ', ucwords($r->status)) }}
                            </span>
                            @can('devices.edit')
                            <button wire:click="openStatusModal"
                                    class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-colors" title="Change status">
                                <flux:icon name="pencil-square" class="size-4" />
                            </button>
                            @endcan
                        </div>
                    </div>

                    {{-- Identity --}}
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device Identity</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach([['IMEI 1', $r->imei_1], ['IMEI 2', $r->imei_2], ['Serial No.', $r->serial_number]] as [$label, $val])
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $label }}</p>
                                <div class="flex items-center justify-between mt-1 gap-2">
                                    <p class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $val ?? '—' }}</p>
                                    @if($val)
                                    <button @click="copy('{{ $val }}')" class="flex-shrink-0 p-1 rounded hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-400 hover:text-gray-600 transition-colors" title="Copy">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Pricing --}}
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Pricing</h3>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 text-center">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Purchase Cost</p>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-100 mt-1">TZS {{ number_format($cost) }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 text-center">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Retail Price</p>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-100 mt-1">{{ $retail > 0 ? 'TZS '.number_format($retail) : '—' }}</p>
                            </div>
                            <div class="bg-{{ $margin > 0 ? 'teal' : 'gray' }}-50 dark:bg-zinc-800 rounded-xl p-3 text-center">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Margin</p>
                                <p class="text-sm font-bold {{ $margin > 0 ? 'text-teal-600' : 'text-gray-500' }} mt-1">{{ $margin !== null ? $margin.'%' : '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Specs --}}
                    @if(!empty($specs))
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Specifications</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($specs as $specKey => $specVal)
                            <span class="px-3 py-1.5 bg-oe-soft dark:bg-oe/10 text-oe-hover dark:text-oe rounded-lg text-xs font-semibold">
                                {{ ucfirst($specKey) }}: {{ $specVal }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Location --}}
                    <div class="px-6 py-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Location &amp; Assignment</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Branch</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $r->branch?->name ?? '—' }}</p>
                                @if($r->branch?->region)
                                <p class="text-[10px] text-gray-400">{{ $r->branch->region }}</p>
                                @endif
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Vendor</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $r->vendor?->name ?? 'HQ Direct' }}</p>
                                @if($r->vendor?->code)
                                <p class="text-[10px] text-gray-400 font-mono">{{ $r->vendor->code }}</p>
                                @endif
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Received Date</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">
                                    {{ $r->received_at?->format('d M Y') ?? $r->created_at->format('d M Y') }}
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Record Created</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $r->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transfer History --}}
                @if($r->stockTransfers->count())
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Transfer History</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Last {{ $r->stockTransfers->count() }} transfers</p>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-zinc-800">
                        @foreach($r->stockTransfers as $transfer)
                        <div class="px-6 py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"></div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 truncate">
                                        {{ $transfer->from_location ?? '—' }} → {{ $transfer->to_location ?? '—' }}
                                    </p>
                                    @if($transfer->notes)
                                    <p class="text-[10px] text-gray-400 truncate">{{ $transfer->notes }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-oe-soft text-oe dark:bg-oe/10 dark:text-oe uppercase">
                                    {{ $transfer->status ?? 'completed' }}
                                </span>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $transfer->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Right: Loan Card + Repayment Schedules --}}
            <div class="space-y-4">

                @if($r->loan)
                @php
                    $loan = $r->loan;
                    $loanStatusColor = match($loan->status) {
                        'active'    => 'bg-emerald-100 text-emerald-700',
                        'completed' => 'bg-sky-100 text-sky-700',
                        'defaulted' => 'bg-red-100 text-red-700',
                        'overdue'   => 'bg-amber-100 text-amber-700',
                        default     => 'bg-zinc-100 text-zinc-600',
                    };
                @endphp
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-oe/20 dark:border-oe/25 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-oe to-oe text-white">
                        <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">Linked Loan</p>
                        <h3 class="text-lg font-bold mt-0.5 font-mono">{{ $loan->loan_number }}</h3>
                        <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold mt-1 {{ $loanStatusColor }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </div>
                    <div class="px-5 py-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Customer</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100">
                                {{ trim(($loan->customer?->first_name ?? '').' '.($loan->customer?->last_name ?? '')) ?: '—' }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Phone</span>
                            <span class="font-mono font-semibold text-gray-800 dark:text-gray-100">{{ $loan->customer?->phone ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Principal</span>
                            <span class="font-bold text-gray-800 dark:text-gray-100">TZS {{ number_format($loan->principal_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Outstanding</span>
                            <span class="font-bold text-red-600">TZS {{ number_format($loan->outstanding_balance ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Term</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $loan->loan_term_weeks ?? '—' }} weeks</span>
                        </div>
                        @if($loan->disbursed_at)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Disbursed</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ \Carbon\Carbon::parse($loan->disbursed_at)->format('d M Y') }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="px-5 pb-4">
                        <a href="{{ route('credit.panel') }}" wire:navigate
                           class="w-full flex items-center justify-center gap-2 py-2.5 text-sm font-bold rounded-xl bg-oe hover:bg-oe-hover text-white transition-colors">
                            View Loan →
                        </a>
                    </div>
                </div>

                {{-- Repayment Schedule Preview --}}
                @if($loan->repaymentSchedules && $loan->repaymentSchedules->count())
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Repayment Schedule</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Next {{ $loan->repaymentSchedules->count() }} installments</p>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-zinc-800">
                        @foreach($loan->repaymentSchedules as $sched)
                        @php
                            $schedColor = match($sched->status) {
                                'paid'   => 'text-teal-600 bg-teal-50',
                                'overdue'=> 'text-red-600 bg-red-50',
                                default  => 'text-amber-600 bg-amber-50',
                            };
                        @endphp
                        <div class="px-5 py-2.5 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $sched->due_date?->format('d M Y') }}</p>
                                <p class="text-[10px] text-gray-400">Due: TZS {{ number_format($sched->amount_due) }}</p>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $schedColor }} uppercase">
                                {{ $sched->status }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @else
                {{-- No loan assigned --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-6 text-center">
                    <flux:icon name="document-minus" class="size-10 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-gray-500">No loan assigned</p>
                    <p class="text-xs text-gray-400 mt-1">This device is not linked to any loan.</p>
                </div>
                @endif

                {{-- Quick Actions --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Quick Actions</p>
                    <div class="space-y-2">
                        @can('devices.edit')
                        <button wire:click="openStatusModal"
                                class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl bg-oe-soft dark:bg-oe/10 text-oe-hover dark:text-oe text-sm font-semibold hover:bg-oe/15 transition-colors">
                            <flux:icon name="pencil-square" class="size-4 flex-shrink-0" />
                            Change Status
                        </button>
                        @endcan
                        <a href="{{ route('stock.index') }}" wire:navigate
                           class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-100 transition-colors">
                            <flux:icon name="arrow-left" class="size-4 flex-shrink-0" />
                            Back to Stock
                        </a>
                    </div>
                </div>
            </div>

        </div>

        @else
        {{-- Not Found --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 p-16 text-center shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                <flux:icon name="exclamation-circle" class="size-8 text-red-500" />
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Device Not Found</h3>
            <p class="text-gray-400 text-sm mt-2">No device matched <span class="font-mono font-bold text-gray-600 dark:text-gray-300">{{ $query }}</span></p>
            <p class="text-gray-400 text-xs mt-1">Check the IMEI/serial number and try again.</p>
        </div>
        @endif
    @endif

    {{-- Status Update Modal --}}
    @if($result)
    <flux:modal wire:model="showStatusModal" name="update-status-imei">
        <flux:heading size="lg">Change Device Status</flux:heading>
        <flux:separator class="my-4" />
        <div class="space-y-4">
            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                <span class="font-semibold">{{ $result->phoneModel?->brand?->name }} {{ $result->phoneModel?->name }}</span>
                · <span class="font-mono">{{ $result->imei_1 }}</span>
            </div>
            <flux:field>
                <flux:label>New Status</flux:label>
                <flux:select wire:model="newStatus">
                    @foreach(['available','hq_stock','vendor_stock','in_transit','sold','returned','lost'] as $st)
                    <flux:select.option :value="$st">{{ str_replace('_', ' ', ucwords($st)) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="newStatus" />
            </flux:field>
            <flux:field>
                <flux:label>Audit Note <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                <flux:input wire:model="statusNote" placeholder="Reason for status change…" />
                <flux:error name="statusNote" />
            </flux:field>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <flux:button wire:click="$set('showStatusModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="updateStatus" variant="primary">
                Update Status
                <flux:icon wire:loading wire:target="updateStatus" name="arrow-path" class="size-4 animate-spin ml-1" />
            </flux:button>
        </div>
    </flux:modal>
    @endif

</div>

@script
<script>
    function imeiSearch() {
        return {
            copy(text) {
                navigator.clipboard.writeText(text).then(() => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Copied: ' + text, type: 'success' } }));
                });
            }
        };
    }
</script>
@endscript
