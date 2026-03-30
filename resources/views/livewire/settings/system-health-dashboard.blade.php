<div>

    {{-- ── Toast ──────────────────────────────────────────────────── --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : 'bg-red-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">System Health</h1>
            <p class="mt-1 text-sm text-gray-400">
                Real-time infrastructure, services, and application diagnostics
                @if($lastRefreshed)
                    &mdash; Last refreshed: <span class="font-medium text-gray-500">{{ $lastRefreshed }}</span>
                @endif
            </p>
        </div>
        <button wire:click="refresh" wire:loading.attr="disabled"
                class="flex items-center gap-2 px-4 py-2 text-sm font-semibold bg-orange-500 hover:bg-orange-600 disabled:opacity-60 text-white rounded-xl shadow-sm transition-colors">
            <svg wire:loading.remove wire:target="refresh" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <svg wire:loading wire:target="refresh" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
            Refresh
        </button>
    </div>

    {{-- ── KPI Row ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @php
        $kpis = [
            ['label' => 'Active Users',    'value' => $totalActiveUsers,  'icon' => 'users',           'color' => 'indigo'],
            ['label' => 'Active Loans',    'value' => $totalActiveLoans,  'icon' => 'banknotes',       'color' => 'teal'],
            ['label' => 'Total Customers', 'value' => $totalCustomers,    'icon' => 'user-group',      'color' => 'blue'],
            ['label' => 'Overdue / Default','value' => $overdueLoans,     'icon' => 'exclamation-triangle', 'color' => $overdueLoans > 0 ? 'red' : 'teal'],
        ];
        $colorMap = [
            'indigo' => 'bg-blue-50 text-orange-500 dark:bg-blue-900/20 dark:text-blue-300',
            'teal'   => 'bg-teal-50 text-teal-600 dark:bg-teal-900/20 dark:text-teal-300',
            'blue'   => 'bg-blue-50 text-orange-500 dark:bg-blue-900/20 dark:text-blue-300',
            'red'    => 'bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-400',
        ];
        @endphp
        @foreach($kpis as $kpi)
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 rounded-xl {{ $colorMap[$kpi['color']] }} shrink-0">
                <flux:icon name="{{ $kpi['icon'] }}" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ $kpi['label'] }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">{{ number_format($kpi['value']) }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

        {{-- ── Services Status ───────────────────────────────────── --}}
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-wider">Services Status</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-zinc-800">
                @php
                $services = [
                    [
                        'name'   => 'PostgreSQL Database',
                        'detail' => strtoupper($dbDriver).' › '.$dbName.' › '.$dbSize,
                        'ok'     => $dbConnected,
                        'label'  => $dbConnected ? 'Connected' : 'Unreachable',
                        'icon'   => 'circle-stack',
                    ],
                    [
                        'name'   => 'Redis Cache / Queue',
                        'detail' => $redisStatus,
                        'ok'     => $redisConnected,
                        'label'  => $redisConnected ? 'Online' : 'Offline',
                        'icon'   => 'bolt',
                    ],
                    [
                        'name'   => 'Cache Driver',
                        'detail' => 'Driver: '.strtoupper($cacheDriver),
                        'ok'     => true,
                        'label'  => 'Active',
                        'icon'   => 'archive-box',
                    ],
                    [
                        'name'   => 'Queue Driver',
                        'detail' => 'Driver: '.strtoupper($queueDriver).' — '.$pendingJobs.' pending, '.$failedJobs.' failed',
                        'ok'     => $failedJobs === 0,
                        'label'  => $failedJobs === 0 ? 'Healthy' : $failedJobs.' Failed',
                        'icon'   => 'queue-list',
                    ],
                    [
                        'name'   => 'Mail Driver',
                        'detail' => 'Driver: '.strtoupper($mailDriver),
                        'ok'     => $mailDriver !== 'log',
                        'label'  => $mailDriver !== 'log' ? 'Configured' : 'Log Only',
                        'icon'   => 'envelope',
                    ],
                    [
                        'name'   => 'BEEM Africa SMS',
                        'detail' => $beemStatus,
                        'ok'     => $beemConfigured,
                        'label'  => $beemConfigured ? 'Ready' : 'Not Set',
                        'icon'   => 'chat-bubble-bottom-center-text',
                    ],
                ];
                @endphp
                @foreach($services as $svc)
                <div class="flex items-center justify-between px-6 py-3.5">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg {{ $svc['ok'] ? 'bg-teal-50 text-teal-600 dark:bg-teal-900/20' : 'bg-red-50 text-red-500 dark:bg-red-900/20' }}">
                            <flux:icon name="{{ $svc['icon'] }}" class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $svc['name'] }}</p>
                            <p class="text-xs text-gray-400">{{ $svc['detail'] }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold
                                 {{ $svc['ok'] ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-300' : 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $svc['ok'] ? 'bg-teal-500' : 'bg-red-400' }}"></span>
                        {{ $svc['label'] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── App Info + Disk ────────────────────────────────────── --}}
        <div class="flex flex-col gap-5">

            {{-- App Info --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800">
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-wider">Application</h3>
                </div>
                <div class="px-6 py-4 space-y-3 text-sm">
                    @php
                    $appRows = [
                        ['k' => 'PHP Version',     'v' => $phpVersion],
                        ['k' => 'Laravel Version', 'v' => $laravelVersion],
                        ['k' => 'Environment',     'v' => strtoupper($appEnv),  'badge' => $appEnv === 'production' ? 'teal' : 'yellow'],
                        ['k' => 'Debug Mode',      'v' => $appDebug ? 'ON' : 'OFF', 'badge' => $appDebug ? 'red' : 'teal'],
                        ['k' => 'Server Uptime',   'v' => $serverUptime],
                    ];
                    @endphp
                    @foreach($appRows as $r)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 text-xs">{{ $r['k'] }}</span>
                        @if(isset($r['badge']))
                            @php $bc = $r['badge'] === 'teal' ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-300' : ($r['badge'] === 'red' ? 'bg-red-50 text-red-600 dark:bg-red-900/20' : 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20'); @endphp
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $bc }}">{{ $r['v'] }}</span>
                        @else
                            <span class="font-semibold text-gray-800 dark:text-gray-100 text-xs">{{ $r['v'] }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Disk Space --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-6">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-wider mb-4">Disk Space</h3>
                <div class="flex items-center gap-4 mb-3">
                    <div class="relative w-16 h-16 shrink-0">
                        <svg class="w-16 h-16 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.9" fill="none"
                                    stroke="{{ $diskUsedPct > 85 ? '#ef4444' : ($diskUsedPct > 65 ? '#f59e0b' : '#14b8a6') }}"
                                    stroke-width="3"
                                    stroke-dasharray="{{ $diskUsedPct }}, 100"
                                    stroke-linecap="round"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-gray-700 dark:text-gray-200">{{ $diskUsedPct }}%</span>
                    </div>
                    <div class="text-sm space-y-1">
                        <p class="text-gray-400 text-xs">Total: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $diskTotal }}</span></p>
                        <p class="text-gray-400 text-xs">Free:  <span class="font-semibold text-teal-600">{{ $diskFree }}</span></p>
                        <p class="text-gray-400 text-xs">Used:  <span class="font-semibold {{ $diskUsedPct > 85 ? 'text-red-500' : 'text-gray-700 dark:text-gray-200' }}">{{ $diskUsedPct }}%</span></p>
                    </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-zinc-700 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all {{ $diskUsedPct > 85 ? 'bg-red-500' : ($diskUsedPct > 65 ? 'bg-yellow-400' : 'bg-teal-500') }}"
                         style="width: {{ $diskUsedPct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── PHP Extensions ─────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
            <h3 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-wider">PHP Extensions</h3>
            <span class="text-xs text-gray-400">PHP {{ $phpVersion }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-0 divide-x divide-y divide-gray-100 dark:divide-zinc-800">
            @foreach($phpExtensions as $ext => $loaded)
            <div class="px-5 py-4 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full shrink-0 {{ $loaded ? 'bg-teal-500' : 'bg-red-400' }}"></span>
                <div>
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $ext }}</p>
                    <p class="text-[10px] {{ $loaded ? 'text-teal-600' : 'text-red-500' }}">{{ $loaded ? 'Loaded' : 'Missing' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
