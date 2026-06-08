<div class="flex flex-col gap-6" x-data="imeiSearch">

    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : 'bg-red-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2" style="display:none">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    <div class="flex items-start gap-4">
        <x-fluent-icon name="magnifying-glass" size="lg" palette="blue" />
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">IMEI / Serial Search</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full trace: <strong class="text-gray-700 dark:text-gray-200">customer</strong>, <strong class="text-gray-700 dark:text-gray-200">dealer</strong>, <strong class="text-gray-700 dark:text-gray-200">device</strong>, <strong class="text-gray-700 dark:text-gray-200">loan progress</strong>, and <strong class="text-gray-700 dark:text-gray-200">activity</strong>.</p>
        </div>
    </div>

    <div class="bg-gradient-to-br from-oe to-oe-hover rounded-2xl p-6 shadow-lg shadow-oe/20 relative overflow-hidden">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative">
            <p class="text-white/70 text-xs font-semibold uppercase tracking-wider mb-3 flex items-center gap-2">
                <x-fluent-icon name="magnifying-glass" size="xs" />
                Enter IMEI or Serial Number
            </p>
            <div class="flex gap-3">
                <input
                    wire:model="query"
                    wire:keydown.enter="search"
                    placeholder="e.g. 358246095872143"
                    class="flex-1 bg-white/10 backdrop-blur border border-white/20 text-white placeholder-white/40 rounded-xl px-4 py-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-white/30"
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
            @if(count($recentSearches))
            <div class="flex flex-wrap items-center gap-2 mt-4">
                <span class="text-white/50 text-xs">Recent:</span>
                @foreach($recentSearches as $recent)
                <button wire:click="searchRecent('{{ $recent }}')" class="px-3 py-1 bg-white/10 hover:bg-white/20 border border-white/20 text-white/80 text-xs font-mono rounded-lg">{{ $recent }}</button>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    @if($searched && $profile)
        @php
            $match = $profile['match'] ?? 'none';
            $customer = $profile['customer'] ?? null;
            $device = $profile['device'] ?? [];
            $dealer = $profile['dealer'] ?? [];
            $kyc = $profile['kyc'] ?? [];
            $loanSummary = $profile['loan_summary'] ?? null;
            $release = $profile['release'] ?? [];
            $activities = $profile['activities'] ?? [];
            $unit = $profile['inventory_unit'] ?? null;
            $loanStatus = $loanSummary['status'] ?? null;
            $loanStatusColor = match($loanStatus) {
                'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                'completed' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                'defaulted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                'overdue' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
            };
        @endphp

        @if($match === 'customer' && $customer)
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="xl:col-span-2 space-y-5">
                {{-- Customer hero --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-oe to-oe-hover px-6 py-5 flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white">
                                <flux:icon name="user" class="size-7" />
                            </div>
                            <div>
                                <p class="text-white/70 text-xs font-semibold uppercase tracking-wider">Customer</p>
                                <h2 class="text-2xl font-bold text-white mt-0.5">{{ $customer->full_name }}</h2>
                                <p class="text-white/80 text-xs mt-1 font-mono">ID {{ $customer->id }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if(filled($kyc['status'] ?? null))
                            <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white/15 text-white uppercase">{{ str_replace('_', ' ', $kyc['status']) }}</span>
                            @endif
                            @if($loanStatus)
                            <span class="px-3 py-1.5 rounded-xl text-xs font-bold {{ $loanStatusColor }}">{{ ucfirst($loanStatus) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-gray-50 dark:border-zinc-800">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Contact</h3>
                            <div class="space-y-2 text-sm">
                                <p><span class="text-gray-500">Phone:</span> <span class="font-semibold text-gray-900 dark:text-white">{{ $customer->phone ?? '—' }}</span></p>
                                @if(filled($customer->alt_phone))
                                <p><span class="text-gray-500">Alt phone:</span> <span class="font-semibold">{{ $customer->alt_phone }}</span></p>
                                @endif
                                <p><span class="text-gray-500">Email:</span> <span class="font-semibold text-gray-900 dark:text-white">{{ $customer->email ?? '—' }}</span></p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Identity</h3>
                            <div class="space-y-2 text-sm">
                                <p><span class="text-gray-500">{{ strtoupper($kyc['id_type'] ?? 'ID') }}:</span> <span class="font-mono font-semibold">{{ $kyc['nida_number'] ?? '—' }}</span></p>
                                <p><span class="text-gray-500">Region:</span> <span class="font-semibold">{{ $kyc['region'] ?? '—' }}@if(filled($kyc['district'] ?? null)), {{ $kyc['district'] }}@endif</span></p>
                                @if(filled($kyc['address'] ?? null))
                                <p><span class="text-gray-500">Address:</span> <span class="font-semibold">{{ $kyc['address'] }}</span></p>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if(filled($dealer['name'] ?? null))
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800 bg-oe-soft/30 dark:bg-zinc-800/40">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <flux:icon name="building-storefront" class="size-4" />
                            Dealer / Counter (alikopeshwa hapa)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="rounded-xl bg-white dark:bg-zinc-900 p-3 border border-gray-100 dark:border-zinc-700">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">Dealer name</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">{{ $dealer['name'] }}</p>
                                @if(filled($dealer['code'] ?? null))
                                <p class="text-xs text-gray-500 mt-0.5">Code {{ $dealer['code'] }}</p>
                                @endif
                            </div>
                            <div class="rounded-xl bg-white dark:bg-zinc-900 p-3 border border-gray-100 dark:border-zinc-700">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">Dealer contact</p>
                                <p class="text-sm font-semibold mt-1">{{ $dealer['phone'] ?? '—' }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $dealer['email'] ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-white dark:bg-zinc-900 p-3 border border-gray-100 dark:border-zinc-700">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">Location</p>
                                <p class="text-sm font-semibold mt-1">{{ $dealer['address'] ?? '—' }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">Status: {{ ucfirst($dealer['status'] ?? '—') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="px-6 py-4 border-b border-gray-50 dark:border-zinc-800">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            @foreach([['IMEI 1', $device['imei_1'] ?? null], ['IMEI 2', $device['imei_2'] ?? null], ['Serial', $device['serial_number'] ?? null]] as [$label, $val])
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ $label }}</p>
                                <div class="flex items-center justify-between mt-1 gap-2">
                                    <p class="font-mono text-sm font-semibold truncate">{{ $val ?? '—' }}</p>
                                    @if($val)
                                    <button @click="copy('{{ $val }}')" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-400" title="Copy">
                                        <flux:icon name="clipboard-document" class="size-3.5" />
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">Brand</p>
                                <p class="text-sm font-bold mt-1">{{ $device['brand'] ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">Model</p>
                                <p class="text-sm font-bold mt-1">{{ $device['model'] ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">Stock status</p>
                                <p class="text-sm font-bold mt-1">{{ $device['inventory_status'] ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">MDM lock</p>
                                <p class="text-sm font-bold mt-1">{{ $device['lock_status'] ?? '—' }}@if(filled($device['mdm_id'] ?? null)) <span class="text-xs font-normal text-gray-500">({{ $device['mdm_id'] }})</span>@endif</p>
                            </div>
                        </div>
                        @if(filled($device['specs'] ?? null))
                        <p class="text-xs text-gray-500 mt-3"><span class="font-semibold">Specs:</span> {{ $device['specs'] }}</p>
                        @endif
                    </div>

                    <div class="px-6 py-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">KYC & registration</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                            <div><p class="text-gray-500 text-xs">Stage</p><p class="font-semibold">{{ $kyc['stage'] ?? '—' }}</p></div>
                            <div><p class="text-gray-500 text-xs">Face match</p><p class="font-semibold">{{ $kyc['face_match_status'] ?? '—' }}@if(filled($kyc['face_match_score'] ?? null)) ({{ number_format($kyc['face_match_score'] * 100, 1) }}%)@endif</p></div>
                            <div><p class="text-gray-500 text-xs">FO agent</p><p class="font-semibold">{{ $kyc['registered_by'] ?? '—' }}</p></div>
                            <div><p class="text-gray-500 text-xs">Registered</p><p class="font-semibold">{{ filled($kyc['registered_at'] ?? null) ? \Carbon\Carbon::parse($kyc['registered_at'])->format('d M Y') : '—' }}</p></div>
                        </div>
                    </div>
                </div>

                {{-- Activity --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <flux:icon name="signal" class="size-4 text-oe" />
                        Activity timeline
                    </h3>
                    @forelse($activities as $activity)
                    <div class="flex gap-3 py-3 border-b border-gray-50 dark:border-zinc-800 last:border-0">
                        <div class="w-2 h-2 rounded-full bg-oe mt-2 shrink-0"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['description'] }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ strtoupper($activity['log_name']) }}
                                @if(filled($activity['causer'] ?? null)) · {{ $activity['causer'] }} @endif
                                · {{ $activity['created_human'] }}
                            </p>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500">No tracked activity yet for this customer.</p>
                    @endforelse
                </div>
            </div>

            {{-- Right column --}}
            <div class="space-y-5">
                @if($loanSummary)
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-oe/20 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-oe to-oe-hover text-white">
                        <p class="text-xs font-semibold text-white/70 uppercase">Linked loan</p>
                        <h3 class="text-lg font-bold font-mono mt-0.5">{{ $loanSummary['loan_number'] }}</h3>
                        <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold mt-2 {{ $loanStatusColor }}">{{ ucfirst($loanSummary['status']) }}</span>
                    </div>
                    <div class="px-5 py-4 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Principal</span><span class="font-bold">TZS {{ number_format($loanSummary['principal_amount']) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Outstanding</span><span class="font-bold text-red-600">TZS {{ number_format($loanSummary['outstanding_balance']) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Paid so far</span><span class="font-semibold">TZS {{ number_format($loanSummary['amount_paid']) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Term</span><span class="font-semibold">{{ $loanSummary['duration_weeks'] }} weeks · {{ ucfirst($loanSummary['repayment_frequency'] ?? 'weekly') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Rate</span><span class="font-semibold">{{ $loanSummary['interest_rate'] }}% {{ str_replace('_', ' ', $loanSummary['interest_type'] ?? '') }}</span></div>
                        @if(filled($loanSummary['dealer_name'] ?? null))
                        <div class="flex justify-between"><span class="text-gray-500">Loan dealer</span><span class="font-semibold">{{ $loanSummary['dealer_name'] }}</span></div>
                        @endif
                        @if(filled($loanSummary['disbursed_at'] ?? null))
                        <div class="flex justify-between"><span class="text-gray-500">Disbursed</span><span class="font-semibold">{{ \Carbon\Carbon::parse($loanSummary['disbursed_at'])->format('d M Y') }}</span></div>
                        @endif
                        @if(filled($loanSummary['next_due_date'] ?? null))
                        <div class="flex justify-between"><span class="text-gray-500">Next due</span><span class="font-semibold">{{ \Carbon\Carbon::parse($loanSummary['next_due_date'])->format('d M Y') }} · TZS {{ number_format($loanSummary['next_due_amount'] ?? 0) }}</span></div>
                        @endif
                    </div>
                    @if(($loanSummary['installments_total'] ?? 0) > 0)
                    <div class="px-5 pb-4">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Repayment progress</span>
                            <span>{{ $loanSummary['installments_paid'] }}/{{ $loanSummary['installments_total'] }} ({{ $loanSummary['progress_percent'] }}%)</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 dark:bg-zinc-800 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $loanSummary['progress_percent'] }}%"></div>
                        </div>
                    </div>
                    @endif
                    <div class="px-5 pb-4">
                        <a href="{{ route('credit.panel') }}" wire:navigate class="w-full flex items-center justify-center gap-2 py-2.5 text-sm font-bold rounded-xl bg-oe hover:bg-oe-hover text-white">View loans panel →</a>
                    </div>
                </div>
                @endif

                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-5 space-y-3 text-sm">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Release & deposit</h3>
                    <div class="flex justify-between"><span class="text-gray-500">Release</span><span class="font-semibold">{{ ucfirst($release['status'] ?? '—') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Deposit</span><span class="font-semibold">{{ ucfirst($release['deposit_payment_status'] ?? '—') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Agreement</span><span class="font-semibold">{{ ($release['agreement_accepted'] ?? false) ? 'Accepted' : 'Pending' }}</span></div>
                    @if(filled($release['released_by'] ?? null))
                    <div class="flex justify-between"><span class="text-gray-500">Released by</span><span class="font-semibold">{{ $release['released_by'] }}</span></div>
                    @endif
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-4 space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Quick actions</p>
                    <a href="{{ route('kyc.customers') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 text-sm font-semibold hover:bg-gray-100">
                        <flux:icon name="users" class="size-4" /> Customer profiles
                    </a>
                    <a href="{{ route('credit.defaulters') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 text-sm font-semibold hover:bg-gray-100">
                        <flux:icon name="exclamation-triangle" class="size-4" /> Defaulters
                    </a>
                    @can('reports.view')
                    <a href="{{ route('audits.logs') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 text-sm font-semibold hover:bg-gray-100">
                        <flux:icon name="document-text" class="size-4" /> Audit logs
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        @elseif($match === 'inventory_only' && $unit)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-white dark:bg-zinc-900 rounded-2xl border border-amber-200 dark:border-amber-900/40 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-5">
                    <p class="text-white/80 text-xs font-semibold uppercase">Inventory only</p>
                    <h2 class="text-xl font-bold text-white mt-1">Stock unit matched — no customer KYC yet</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @foreach([['IMEI 1', $device['imei_1'] ?? null], ['IMEI 2', $device['imei_2'] ?? null], ['Serial', $device['serial_number'] ?? null]] as [$label, $val])
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ $label }}</p>
                            <p class="font-mono text-sm font-semibold mt-1">{{ $val ?? '—' }}</p>
                        </div>
                        @endforeach
                    </div>
                    <p class="text-sm"><span class="text-gray-500">Brand / model:</span> <span class="font-bold">{{ $device['brand'] ?? '—' }} {{ $device['model'] ?? '' }}</span></p>
                    @if(filled($dealer['name'] ?? null))
                    <p class="text-sm"><span class="text-gray-500">Dealer counter:</span> <span class="font-bold">{{ $dealer['name'] }}</span> · {{ $dealer['phone'] ?? '—' }}</p>
                    @endif
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 p-5">
                <h3 class="text-sm font-bold mb-3">Stock activity</h3>
                @forelse($activities as $activity)
                <p class="text-xs text-gray-600 dark:text-gray-300 py-2 border-b border-gray-50 dark:border-zinc-800">{{ $activity['description'] }} <span class="text-gray-400">· {{ $activity['created_human'] }}</span></p>
                @empty
                <p class="text-sm text-gray-500">No activity logged for this unit.</p>
                @endforelse
            </div>
        </div>

        @else
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 p-16 text-center shadow-sm">
            <flux:icon name="exclamation-circle" class="size-12 mx-auto mb-4 text-red-500" />
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">No match</h3>
            <p class="text-gray-400 text-sm mt-2">No customer or inventory unit matched <span class="font-mono font-bold">{{ $query }}</span></p>
        </div>
        @endif
    @endif
</div>

@script
<script data-navigate-once>
    document.addEventListener('alpine:init', () => {
        Alpine.data('imeiSearch', () => ({
            copy(text) {
                navigator.clipboard.writeText(text).then(() => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Copied: ' + text, type: 'success' } }));
                });
            },
        }));
    });
</script>
@endscript
