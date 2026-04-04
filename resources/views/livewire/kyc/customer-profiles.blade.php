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
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Customer Profiles</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">All registered customers — verified, pending and unverified</p>
        </div>
        <a href="{{ route('kyc.wizard') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:opacity-90 transition-opacity shadow-sm">
            <flux:icon name="user-plus" class="size-4" />
            New KYC Wizard
        </a>
    </div>

    {{-- Stats Bar --}}
    @php
    $statDefs = [
        ['key' => 'total',    'label' => 'Total Customers', 'grad'   => 'from-[#2563eb] to-[#2563eb]',                              'hero' => true,  'sub' => 'All registered'],
        ['key' => 'verified', 'label' => 'Verified',        'icolor' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',    'sub' => 'KYC approved'],
        ['key' => 'pending',  'label' => 'In Review',       'icolor' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600',          'sub' => 'Awaiting decision'],
        ['key' => 'rejected', 'label' => 'Rejected',        'icolor' => 'bg-red-100 dark:bg-red-900/30 text-red-600',               'sub' => 'Need re-submission'],
    ];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($statDefs as $sd)
        @if(!empty($sd['hero']))
        <div class="bg-gradient-to-br {{ $sd['grad'] }} rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-blue-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-white/20"><flux:icon name="users" class="size-5" /></div>
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">{{ $sd['label'] }}</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($statCounts[$sd['key']] ?? 0) }}</p>
            <p class="text-xs text-white/60 mt-1">{{ $sd['sub'] }}</p>
        </div>
        @else
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg {{ $sd['icolor'] }}">
                    <flux:icon name="user-circle" class="size-5" />
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $sd['label'] }}</span>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($statCounts[$sd['key']] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $sd['sub'] }}</p>
        </div>
        @endif
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Name, phone or NIDA…" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="kycFilter" class="w-44">
            <flux:select.option value="">All KYC Status</flux:select.option>
            <flux:select.option value="verified">Verified</flux:select.option>
            <flux:select.option value="pending">In Review</flux:select.option>
            <flux:select.option value="rejected">Rejected</flux:select.option>
            <flux:select.option value="not_started">Not Started</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="branchFilter" class="w-48">
            <flux:select.option value="">All Branches</flux:select.option>
            @foreach($branches as $b)
            <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">NIDA</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Branch</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">KYC</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Loans</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Joined</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                @forelse($customers as $customer)
                @php
                    $cv = $customer->latestVerification;
                    $isVerified = $cv?->status === 'approved';
                    $kycLabel = match($cv?->status) {
                        'approved' => 'Verified',
                        'pending'  => 'In Review',
                        'rejected' => 'Rejected',
                        default    => 'Not Started',
                    };
                    $kycBadge = match($cv?->status) {
                        'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                        'pending'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        default    => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                    };
                    $kycDot = match($cv?->status) {
                        'approved' => 'bg-emerald-400',
                        'pending'  => 'bg-amber-400',
                        'rejected' => 'bg-red-400',
                        default    => 'bg-zinc-400',
                    };
                    $loanCount = $customer->loans->count();
                @endphp
                <tr wire:key="cust-{{ $customer->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-orange-400 to-orange-500 text-white text-xs font-black flex-shrink-0">
                                {{ strtoupper(substr($customer->first_name, 0, 1).substr($customer->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $customer->full_name }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $customer->occupation ?? ($customer->gender ? ucfirst($customer->gender) : ($customer->email ?? '—')) }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300">{{ $customer->phone }}</td>
                    <td class="px-4 py-3.5 font-mono text-xs text-gray-500 hidden md:table-cell">
                        {{ $customer->nida_number ? substr($customer->nida_number, 0, 8).'…' : '—' }}
                    </td>
                    <td class="px-4 py-3.5 text-xs text-gray-500 hidden lg:table-cell">
                        {{ $customer->branch?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $kycBadge }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $kycDot }}"></span>
                            {{ $kycLabel }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 hidden md:table-cell">
                        @if($loanCount > 0)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-orange-600 dark:bg-blue-900/30 dark:text-blue-300">
                            <flux:icon name="banknotes" class="size-3" />
                            {{ $loanCount }} loan{{ $loanCount !== 1 ? 's' : '' }}
                        </span>
                        @else
                        <span class="text-xs text-gray-400">None</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-xs text-gray-400 hidden lg:table-cell">
                        {{ $customer->created_at->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button wire:click="openDetail('{{ $customer->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-50 text-orange-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View
                            </button>
                            @if(!$isVerified)
                            @can('loans.create')
                            <button wire:click="openApproveModal('{{ $customer->id }}')"
                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @endcan
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-16 text-center">
                        <flux:icon name="users" class="size-12 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                        <p class="text-gray-500 font-medium">No customers found</p>
                        <p class="text-gray-400 text-xs mt-1">
                            @if($search || $kycFilter || $branchFilter) Try clearing your filters @endif
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($customers->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $customers->links() }}</div>
        @endif
    </div>

    {{-- ══ CUSTOMER DETAIL SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div x-show="open"
             x-data="{ tab: 'device' }"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-2xl bg-white dark:bg-zinc-900 shadow-2xl flex flex-col h-full">
            @if($detailCustomer)
            @php
                $dc  = $detailCustomer;
                $dcv = $dc->latestVerification;
                $isApproved = $dcv?->status === 'approved';

                $dcKycBadge = match($dcv?->status) {
                    'approved' => 'bg-emerald-100 text-emerald-700',
                    'pending'  => 'bg-amber-100 text-amber-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    default    => 'bg-zinc-100 text-zinc-600',
                };
                $dcKycLabel = match($dcv?->status) {
                    'approved' => 'Verified',
                    'pending'  => 'In Review',
                    'rejected' => 'Rejected',
                    default    => 'Not Started',
                };

                $autoStatus = $dcv?->auto_check_status;
                $autoBadge = match($autoStatus) {
                    'passed'         => 'bg-emerald-100 text-emerald-700',
                    'needs_correction'=> 'bg-amber-100 text-amber-700',
                    'manual_review'  => 'bg-blue-100 text-blue-700',
                    'auto_rejected'  => 'bg-red-100 text-red-700',
                    default          => 'bg-zinc-100 text-zinc-500',
                };

                $photoBase = fn($path) => $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
            @endphp

            {{-- ── Header ── --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-orange-500 to-orange-600 text-white flex-shrink-0">
                <div class="flex items-center gap-4 min-w-0">
                    @if($dc->headshot_photo_path)
                    <img src="{{ $photoBase($dc->headshot_photo_path) }}"
                         class="w-14 h-14 rounded-2xl object-cover ring-2 ring-white/30 flex-shrink-0" alt="Headshot">
                    @else
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-xl font-black flex-shrink-0">
                        {{ strtoupper(substr($dc->first_name, 0, 1).substr($dc->last_name, 0, 1)) }}
                    </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold text-white/60 uppercase tracking-wider">Customer Profile</p>
                        <h2 class="text-xl font-black mt-0.5 truncate">{{ $dc->full_name }}</h2>
                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $dcKycBadge }}">{{ $dcKycLabel }}</span>
                            @if($autoStatus)
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $autoBadge }}">Auto: {{ str_replace('_', ' ', ucfirst($autoStatus)) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0 ml-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- ── Tab Nav ── --}}
            <div class="flex gap-0 border-b border-gray-100 dark:border-zinc-800 overflow-x-auto flex-shrink-0 bg-white dark:bg-zinc-900 scrollbar-hide">
                @foreach([
                    ['id'=>'device',   'label'=>'Device',    'icon'=>'device-phone-mobile'],
                    ['id'=>'identity', 'label'=>'Identity',  'icon'=>'identification'],
                    ['id'=>'contact',  'label'=>'Contact',   'icon'=>'map-pin'],
                    ['id'=>'income',   'label'=>'Income',    'icon'=>'currency-dollar'],
                    ['id'=>'nok',      'label'=>'NOK',       'icon'=>'users'],
                    ['id'=>'consent',  'label'=>'Consent',   'icon'=>'shield-check'],
                    ['id'=>'checks',   'label'=>'Checks',    'icon'=>'clipboard-document-check'],
                    ['id'=>'history',  'label'=>'History',   'icon'=>'clock'],
                ] as $t)
                <button @click="tab='{{ $t['id'] }}'"
                        :class="tab==='{{ $t['id'] }}' ? 'border-b-2 border-orange-500 text-orange-600 dark:text-orange-400 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                        class="flex items-center gap-1.5 px-4 py-3 text-xs whitespace-nowrap transition-colors flex-shrink-0">
                    <flux:icon name="{{ $t['icon'] }}" class="size-3.5" />
                    {{ $t['label'] }}
                </button>
                @endforeach
            </div>

            {{-- ── Tab Body ── --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                {{-- ▸ DEVICE --}}
                <div x-show="tab==='device'" x-cloak>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Device / Model</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->device_specs ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">IMEI 1</p>
                            <p class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->imei_number ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">IMEI 2</p>
                            <p class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->imei_2 ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Serial Number</p>
                            <p class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->serial_number ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Cash Price</p>
                            <p class="text-sm font-bold text-orange-600 dark:text-orange-400 mt-0.5">
                                {{ $dc->cash_price ? 'TZS '.number_format($dc->cash_price) : '—' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Deposit</p>
                            <p class="text-sm font-bold text-teal-600 dark:text-teal-400 mt-0.5">
                                {{ $dc->deposit_amount ? 'TZS '.number_format($dc->deposit_amount) : '—' }}
                            </p>
                        </div>
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Repayment Cycle</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->preferred_repayment ? ucfirst($dc->preferred_repayment) : '—' }}</p>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Device Photos</p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach([
                            ['path' => $dc->imei_photo_path,      'label' => 'IMEI Label'],
                            ['path' => $dc->device_box_photo_path, 'label' => 'Box Photo'],
                            ['path' => $dc->device_photo_path,     'label' => 'Device Photo'],
                        ] as $img)
                        @if($photoBase($img['path']))
                        <a href="{{ $photoBase($img['path']) }}" target="_blank" class="group relative rounded-xl overflow-hidden bg-gray-100 dark:bg-zinc-800 aspect-square block">
                            <img src="{{ $photoBase($img['path']) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="{{ $img['label'] }}">
                            <span class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[9px] font-semibold text-center py-1">{{ $img['label'] }}</span>
                        </a>
                        @else
                        <div class="rounded-xl bg-gray-100 dark:bg-zinc-800 aspect-square flex flex-col items-center justify-center gap-1">
                            <flux:icon name="photo" class="size-7 text-gray-300 dark:text-zinc-600" />
                            <span class="text-[9px] text-gray-400">{{ $img['label'] }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>

                {{-- ▸ IDENTITY --}}
                <div x-show="tab==='identity'" x-cloak>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Full Name</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->full_name }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Gender</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ ucfirst($dc->gender ?? '—') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Date of Birth</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">
                                {{ $dc->date_of_birth?->format('d M Y') ?? '—' }}
                                @if($dc->date_of_birth) <span class="text-[10px] text-gray-400">({{ $dc->date_of_birth->age }}y)</span>@endif
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">ID Type</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->id_type ? strtoupper(str_replace('_', ' ', $dc->id_type)) : '—' }}</p>
                        </div>
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">NIDA Number</p>
                            <p class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->nida_number ?? '—' }}</p>
                        </div>
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Email</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->email ?? '—' }}</p>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Identity Photos</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            ['path' => $dc->id_front_photo_path,  'label' => 'ID Front'],
                            ['path' => $dc->id_back_photo_path,   'label' => 'ID Back'],
                            ['path' => $dc->headshot_photo_path,  'label' => 'Headshot / Selfie'],
                            ['path' => $dc->client_fo_photo_path, 'label' => 'Client + FO Photo'],
                        ] as $img)
                        @if($photoBase($img['path']))
                        <a href="{{ $photoBase($img['path']) }}" target="_blank" class="group relative rounded-xl overflow-hidden bg-gray-100 dark:bg-zinc-800 aspect-video block">
                            <img src="{{ $photoBase($img['path']) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="{{ $img['label'] }}">
                            <span class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[9px] font-semibold text-center py-1">{{ $img['label'] }}</span>
                        </a>
                        @else
                        <div class="rounded-xl bg-gray-100 dark:bg-zinc-800 aspect-video flex flex-col items-center justify-center gap-1">
                            <flux:icon name="photo" class="size-8 text-gray-300 dark:text-zinc-600" />
                            <span class="text-[9px] text-gray-400">{{ $img['label'] }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>

                {{-- ▸ CONTACT --}}
                <div x-show="tab==='contact'" x-cloak>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Phone</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->phone ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Alt Phone</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->alt_phone ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Branch</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->branch?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Region</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->region ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">District</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->district ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Landmark</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->landmark ?? '—' }}</p>
                        </div>
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Address</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ $dc->address ?? '—' }}</p>
                        </div>
                        @if($dc->latitude && $dc->longitude)
                        <div class="col-span-2 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 flex items-center gap-3">
                            <flux:icon name="map-pin" class="size-5 text-blue-500 flex-shrink-0" />
                            <div>
                                <p class="text-[10px] text-blue-500 uppercase tracking-wider font-semibold">GPS Coordinates</p>
                                <a href="https://maps.google.com/?q={{ $dc->latitude }},{{ $dc->longitude }}" target="_blank"
                                   class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline mt-0.5 block">
                                    {{ $dc->latitude }}, {{ $dc->longitude }}
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ▸ INCOME --}}
                <div x-show="tab==='income'" x-cloak>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Occupation</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->occupation ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Employer</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->employer ?? '—' }}</p>
                        </div>
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Work Location</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->work_location ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Monthly Income</p>
                            <p class="text-sm font-bold text-teal-600 dark:text-teal-400 mt-0.5">
                                {{ $dc->monthly_income ? 'TZS '.number_format($dc->monthly_income) : '—' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Monthly Expenses</p>
                            <p class="text-sm font-bold text-red-500 dark:text-red-400 mt-0.5">
                                {{ $dc->monthly_expenses ? 'TZS '.number_format($dc->monthly_expenses) : '—' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Payment Cycle</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->income_payment_cycle ? ucfirst($dc->income_payment_cycle) : '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Duration at Work</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->duration_at_work ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Credit Status</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ ucfirst($dc->credit_status ?? 'unrated') }}</p>
                        </div>
                    </div>
                    @if($photoBase($dc->business_photo_path))
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Business / Workplace Photo</p>
                    <a href="{{ $photoBase($dc->business_photo_path) }}" target="_blank" class="group relative rounded-xl overflow-hidden bg-gray-100 dark:bg-zinc-800 block w-full aspect-video">
                        <img src="{{ $photoBase($dc->business_photo_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="Business Photo">
                        <span class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[9px] font-semibold text-center py-1">Click to view full size</span>
                    </a>
                    @endif
                </div>

                {{-- ▸ NOK --}}
                <div x-show="tab==='nok'" x-cloak>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Primary Next of Kin</p>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Full Name</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->nok_name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Phone</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->nok_phone ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Relationship</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->nok_relationship ? ucfirst($dc->nok_relationship) : '—' }}</p>
                        </div>
                    </div>
                    @if($dc->nok2_name)
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Secondary Next of Kin</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Full Name</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->nok2_name }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Phone</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->nok2_phone ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Relationship</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->nok2_relationship ? ucfirst($dc->nok2_relationship) : '—' }}</p>
                        </div>
                    </div>
                    @else
                    <div class="rounded-xl border-2 border-dashed border-gray-200 dark:border-zinc-700 p-5 text-center text-gray-400 text-xs">No secondary next of kin recorded</div>
                    @endif
                </div>

                {{-- ▸ CONSENT --}}
                <div x-show="tab==='consent'" x-cloak>
                    <div class="space-y-2 mb-4">
                        @foreach([
                            ['field' => $dc->terms_accepted,         'label' => 'Terms & Conditions accepted'],
                            ['field' => $dc->data_consent_accepted,  'label' => 'Data processing consent given'],
                            ['field' => $dc->call_consent_accepted,  'label' => 'Call consent given'],
                        ] as $cs)
                        <div class="flex items-center gap-3 bg-gray-50 dark:bg-zinc-800 rounded-xl px-4 py-3">
                            @if($cs['field'])
                            <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            @else
                            <div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            @endif
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $cs['label'] }}</p>
                        </div>
                        @endforeach
                        @if($dc->consent_timestamp)
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Consent Timestamp</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->consent_timestamp?->format('d M Y, H:i') }}</p>
                        </div>
                        @endif
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">FO Submission Info</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Field Officer</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">
                                {{ $dcv?->fo?->name ?? $dc->registeredBy?->name ?? '—' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Application Source</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">
                                {{ $dc->application_source ? ucwords(str_replace('_', ' ', $dc->application_source)) : '—' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Branch</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->branch?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Submitted</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        @if($dc->fo_notes)
                        <div class="col-span-2 bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3">
                            <p class="text-[10px] text-amber-600 uppercase tracking-wider font-semibold mb-1">FO Notes</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $dc->fo_notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ▸ AUTO-CHECKS --}}
                <div x-show="tab==='checks'" x-cloak>
                    @if($dcv && $dcv->auto_check_results)
                    @php
                        $acChecks = $dcv->auto_check_results ?? [];
                        $acStatusColors = [
                            'passed'  => 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800',
                            'warning' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
                            'failed'  => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                        ];
                        $acIconColors = [
                            'passed'  => 'text-emerald-500',
                            'warning' => 'text-amber-500',
                            'failed'  => 'text-red-500',
                        ];
                    @endphp
                    <div class="mb-4 flex items-center gap-3 p-4 rounded-xl {{ match($autoStatus) {
                        'passed'         => 'bg-emerald-50 dark:bg-emerald-900/20',
                        'needs_correction'=> 'bg-amber-50 dark:bg-amber-900/20',
                        'manual_review'  => 'bg-blue-50 dark:bg-blue-900/20',
                        'auto_rejected'  => 'bg-red-50 dark:bg-red-900/20',
                        default          => 'bg-gray-50 dark:bg-zinc-800',
                    } }}">
                        <flux:icon name="clipboard-document-check" class="size-8 {{ match($autoStatus) {
                            'passed'         => 'text-emerald-500',
                            'needs_correction'=> 'text-amber-500',
                            'manual_review'  => 'text-blue-500',
                            'auto_rejected'  => 'text-red-500',
                            default          => 'text-gray-400',
                        } }}" />
                        <div>
                            <p class="text-xs text-gray-500">Overall Auto-check Result</p>
                            <p class="text-lg font-black text-gray-900 dark:text-white">{{ $autoStatus ? ucwords(str_replace('_', ' ', $autoStatus)) : 'Not Run' }}</p>
                            @if($dcv->auto_check_ran_at)
                            <p class="text-[10px] text-gray-400">Run at {{ $dcv->auto_check_ran_at->format('d M Y, H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-2">
                        @foreach($acChecks as $check)
                        @php
                            $cs = $check['status'] ?? 'passed';
                            $cardColor = $acStatusColors[$cs] ?? 'bg-gray-50 dark:bg-zinc-800 border-gray-200';
                            $iconColor = $acIconColors[$cs] ?? 'text-gray-400';
                        @endphp
                        <div class="flex items-start gap-3 rounded-xl border px-4 py-3 {{ $cardColor }}">
                            @if($cs === 'passed')
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            @elseif($cs === 'warning')
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            @else
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">{{ $check['check'] ?? 'Check' }}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $check['message'] ?? '' }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-16 text-center gap-3">
                        <flux:icon name="clipboard-document-check" class="size-12 text-gray-300 dark:text-zinc-600" />
                        <p class="text-gray-500 font-medium text-sm">Auto-checks not yet run</p>
                        <p class="text-gray-400 text-xs">Checks run automatically when the application is submitted via the wizard.</p>
                    </div>
                    @endif
                </div>

                {{-- ▸ HISTORY --}}
                <div x-show="tab==='history'" x-cloak>
                    {{-- Verification Records --}}
                    @if($dc->verifications->count())
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Verification Records</p>
                    <div class="space-y-2 mb-5">
                        @foreach($dc->verifications->sortByDesc('created_at') as $vr)
                        @php
                            $vrBadge = match($vr->status) {
                                'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                'pending'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                default    => 'bg-zinc-100 text-zinc-600',
                            };
                        @endphp
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $vrBadge }}">{{ ucfirst($vr->status) }}</span>
                                <span class="text-[10px] text-gray-400">{{ $vr->created_at->format('d M Y, H:i') }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <div>
                                    <span class="text-gray-400">Stage:</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300 ml-1">{{ $vr->stage ?? '—' }} — {{ $vr->currentStageLabel() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Submitted by FO:</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300 ml-1">{{ $vr->fo?->name ?? '—' }}</span>
                                </div>
                            </div>
                            @if($vr->rejection_reason)
                            <p class="text-xs text-red-600 dark:text-red-400 mt-2 font-medium">Reason: {{ $vr->rejection_reason }}</p>
                            @endif
                            @if($vr->notes)
                            <p class="text-xs text-gray-500 mt-1.5 italic">{{ $vr->notes }}</p>
                            @endif
                            @if($vr->reviewedBy)
                            <p class="text-[10px] text-gray-400 mt-1.5">Reviewed by: <span class="font-semibold">{{ $vr->reviewedBy->name }}</span>
                                @if($vr->reviewed_at) · {{ $vr->reviewed_at->format('d M Y, H:i') }}@endif
                            </p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Loans --}}
                    @if($dc->loans?->count())
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Loan History</p>
                    <div class="space-y-1.5">
                        @foreach($dc->loans as $ln)
                        @php
                            $lnBadge = match($ln->status) {
                                'active'    => 'bg-emerald-100 text-emerald-700',
                                'completed' => 'bg-sky-100 text-sky-700',
                                'defaulted' => 'bg-red-100 text-red-700',
                                'overdue'   => 'bg-amber-100 text-amber-700',
                                default     => 'bg-zinc-100 text-zinc-600',
                            };
                        @endphp
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800 rounded-xl px-4 py-2.5">
                            <div>
                                <p class="text-xs font-mono font-semibold text-orange-500 dark:text-orange-400">{{ $ln->loan_number }}</p>
                                <p class="text-[10px] text-gray-500">TZS {{ number_format($ln->principal_amount) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $lnBadge }}">{{ ucfirst($ln->status) }}</span>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $ln->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if(!$dc->verifications->count() && !$dc->loans?->count())
                    <div class="flex flex-col items-center justify-center py-16 text-center gap-3">
                        <flux:icon name="clock" class="size-12 text-gray-300 dark:text-zinc-600" />
                        <p class="text-gray-500 font-medium text-sm">No verification history yet</p>
                    </div>
                    @endif
                </div>

            </div>

            {{-- ── Footer Actions ── --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex gap-2 flex-shrink-0 bg-white dark:bg-zinc-900">
                @if(!$isApproved)
                @can('loans.create')
                <button wire:click="openApproveModal('{{ $dc->id }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Approve KYC
                </button>
                <button wire:click="openRejectModal('{{ $dc->id }}')"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-colors">
                    Reject
                </button>
                @endcan
                @else
                <div class="flex-1 flex items-center gap-2 px-4 py-2.5 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">KYC Verified</span>
                </div>
                @endif
                <button wire:click="closeDetail"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- ══ APPROVE MODAL ══ --}}
    <flux:modal wire:model="showApproveModal" name="approve-kyc-profile">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <flux:heading size="lg">Approve KYC</flux:heading>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">This will mark the customer as identity-verified and unlock loan eligibility.</p>
        <flux:field>
            <flux:label>Reviewer Notes <span class="text-gray-400 font-normal">(optional)</span></flux:label>
            <flux:textarea wire:model="approveNotes" rows="3" placeholder="Any notes about this approval…" />
            <flux:error name="approveNotes" />
        </flux:field>
        <div class="flex justify-end gap-3 mt-5">
            <flux:button variant="ghost" wire:click="$set('showApproveModal', false)">Cancel</flux:button>
            <flux:button wire:click="approveKyc" class="bg-emerald-600 hover:bg-emerald-700 text-white border-0">
                <flux:icon wire:loading wire:target="approveKyc" name="arrow-path" class="size-4 animate-spin mr-1" />
                Confirm Approval
            </flux:button>
        </div>
    </flux:modal>

    {{-- ══ REJECT MODAL ══ --}}
    <flux:modal wire:model="showRejectModal" name="reject-kyc-profile">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <flux:heading size="lg">Reject KYC</flux:heading>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">The customer can re-submit their documents after correction.</p>
        <div class="space-y-4">
            <flux:field>
                <flux:label>Rejection Reason <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="rejectReason" placeholder="e.g. NIDA number mismatch, blurry photo…" />
                <flux:error name="rejectReason" />
            </flux:field>
            <flux:field>
                <flux:label>Additional Notes <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                <flux:textarea wire:model="rejectNotes" rows="2" placeholder="Internal notes…" />
                <flux:error name="rejectNotes" />
            </flux:field>
        </div>
        <div class="flex justify-end gap-3 mt-5">
            <flux:button variant="ghost" wire:click="$set('showRejectModal', false)">Cancel</flux:button>
            <flux:button wire:click="rejectKyc" variant="danger">
                <flux:icon wire:loading wire:target="rejectKyc" name="arrow-path" class="size-4 animate-spin mr-1" />
                Confirm Rejection
            </flux:button>
        </div>
    </flux:modal>

</div>
