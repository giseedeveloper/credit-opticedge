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
        <div class="flex items-start gap-4">
            <x-fluent-icon name="shield-check" size="lg" palette="teal" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">KYC Vault — Pending</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Customers awaiting identity verification before loan eligibility</p>
            </div>
        </div>
        <a href="{{ route('kyc.wizard') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-oe to-oe-hover text-white hover:opacity-90 transition-opacity shadow-sm">
            <x-fluent-icon name="user-plus" size="xs" palette="emerald" />
            New KYC Wizard
        </a>
    </div>

    {{-- Stage Tab Bar --}}
    @php
    $stageDefs = [
        1 => ['label' => 'Stage 1', 'desc' => 'Device Verification',   'icon' => 'device-phone-mobile', 'color' => 'orange'],
        2 => ['label' => 'Stage 2', 'desc' => 'KYC & Financial Data',  'icon' => 'identification',      'color' => 'blue'],
        3 => ['label' => 'Stage 3', 'desc' => 'Confirmation Call',      'icon' => 'phone',               'color' => 'teal'],
        4 => ['label' => 'Stage 4', 'desc' => 'NOK + Final Approval',   'icon' => 'user-group',          'color' => 'emerald'],
    ];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($stageDefs as $n => $sd)
        @php
            $isActive = $activeTab === $n;
            $count    = $stageCounts[$n] ?? 0;
            $tabRing  = $isActive ? 'ring-2 ring-oe' : 'border border-gray-100 dark:border-zinc-800';
            $tabBg    = $isActive ? 'bg-oe-soft dark:bg-orange-900/20' : 'bg-white dark:bg-zinc-900';
        @endphp
        <button wire:click="$set('activeTab', {{ $n }})"
                class="{{ $tabBg }} {{ $tabRing }} rounded-2xl p-4 text-left shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $sd['label'] }}</span>
                <x-fluent-icon :name="$sd['icon']" size="xs" class="{{ $isActive ? 'opacity-100' : 'opacity-75' }}" />
                @if($count > 0)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-oe/15 text-oe-hover">{{ $count }}</span>
                @endif
            </div>
            <p class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-tight">{{ $sd['desc'] }}</p>
        </button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Name, phone, NIDA or IMEI…" icon="magnifying-glass" />
        </div>
    </div>

    {{-- Stage description banner --}}
    @php
    $activeStageDesc = [
        1 => 'Validate IMEI format and device authenticity. Approve to move client to Stage 2 (KYC Data).',
        2 => 'Verify KYC data, ID photos, and facial match. Approve to move client to Stage 3 (Confirmation Call).',
        3 => 'Call the client directly to confirm identity, awareness of loan, and consent. Record call outcome.',
        4 => 'Call Next of Kin to confirm relationship and awareness. Final approval completes onboarding.',
    ];
    @endphp
    <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-amber-50 border border-amber-100 dark:bg-amber-900/10 dark:border-amber-900/30">
        <flux:icon name="information-circle" class="size-4 text-amber-600 mt-0.5 shrink-0" />
        <p class="text-xs text-amber-700 dark:text-amber-300 font-medium">
            <span class="font-bold">Stage {{ $activeTab }}:</span> {{ $activeStageDesc[$activeTab] }}
        </p>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                    @if($activeTab === 1)
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IMEI</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Device</th>
                    @else
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">NIDA</th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Dealer</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Submitted</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                @forelse($customers as $customer)
                <tr wire:key="kyc-{{ $customer->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-oe/90 to-oe text-white text-xs font-black flex-shrink-0">
                                {{ strtoupper(substr($customer->first_name, 0, 1).substr($customer->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $customer->full_name }}</p>
                                <p class="text-xs text-gray-400">{{ $customer->phone }}</p>
                            </div>
                        </div>
                    </td>
                    @if($activeTab === 1)
                    <td class="px-4 py-3.5 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $customer->imei_number ?? '—' }}</td>
                    <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 hidden md:table-cell max-w-[160px] truncate">{{ $customer->device_specs ?? '—' }}</td>
                    @else
                    <td class="px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300">{{ $customer->phone }}</td>
                    <td class="px-4 py-3.5 font-mono text-xs text-gray-600 dark:text-gray-300 hidden md:table-cell">
                        {{ $customer->nida_number ? substr($customer->nida_number, 0, 8).'…' : '—' }}
                    </td>
                    @endif
                    <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ $customer->dealer?->name ?? '—' }}</td>
                    <td class="px-4 py-3.5 text-xs text-gray-400 hidden lg:table-cell">{{ $customer->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button wire:click="openDetail('{{ $customer->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 dark:bg-zinc-800 dark:text-gray-300 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View
                            </button>
                            @can('loans.create')
                            @if($activeTab === 1 || $activeTab === 2)
                            <button wire:click="openApproveModal('{{ $customer->id }}', {{ $activeTab }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Approve
                            </button>
                            <button wire:click="openRejectModal('{{ $customer->id }}', {{ $activeTab }})"
                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            @elseif($activeTab === 3)
                            <button wire:click="openCallModal('{{ $customer->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 dark:bg-teal-900/20 dark:text-teal-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                Record Call
                            </button>
                            @elseif($activeTab === 4)
                            <button wire:click="openNokModal('{{ $customer->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                NOK Call
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-16 text-center">
                        <flux:icon name="check-circle" class="size-12 mx-auto mb-3 text-emerald-400" />
                        <p class="text-gray-500 font-medium">No applications at this stage.</p>
                        <p class="text-gray-400 text-xs mt-1">Stage {{ $activeTab }} queue is empty.</p>
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
    {{-- (detail slide-over below) --}}
    {{-- placeholder for readability --}}
    <div x-data="{ open: @entangle('showDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-lg bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">
            @if($detailCustomer)
            @php
                $dc = $detailCustomer;
                $dcv = $dc->latestKycVerification;
                $dcKycBadge = match($dcv?->status) {
                    'pending'  => 'bg-amber-100 text-amber-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    'approved' => 'bg-emerald-100 text-emerald-700',
                    default    => 'bg-zinc-100 text-zinc-600',
                };
            @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-oe to-oe-hover text-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-lg font-black">
                        {{ strtoupper(substr($dc->first_name, 0, 1).substr($dc->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">KYC Review</p>
                        <h2 class="text-xl font-black mt-0.5">{{ $dc->full_name }}</h2>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold mt-1.5 {{ $dcKycBadge }}">
                            {{ $dcv ? ucfirst($dcv->status) : 'Not Started' }}
                        </span>
                    </div>
                </div>
                <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Personal Info --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Personal Information</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Full Name</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->full_name }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Phone</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->phone }}</p>
                            @if($dc->alt_phone)<p class="text-xs text-gray-400">Alt: {{ $dc->alt_phone }}</p>@endif
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Date of Birth</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">
                                {{ $dc->date_of_birth?->format('d M Y') ?? '—' }}
                                @if($dc->date_of_birth)<span class="text-xs text-gray-400">({{ $dc->date_of_birth->age }} yrs)</span>@endif
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Gender</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ ucfirst($dc->gender ?? '—') }}</p>
                        </div>
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">NIDA Number</p>
                            <p class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->nida_number ?? '—' }}</p>
                        </div>
                        @if($dc->address || $dc->region)
                        <div class="col-span-2 bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Address</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">
                                {{ implode(', ', array_filter([$dc->address, $dc->district, $dc->region])) ?: '—' }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Financial Profile --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Financial Profile</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Occupation</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->occupation ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Employer</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->employer ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Monthly Income</p>
                            <p class="text-sm font-bold {{ $dc->monthly_income ? 'text-teal-600 dark:text-teal-400' : 'text-gray-400' }} mt-0.5">
                                {{ $dc->monthly_income ? 'TZS '.number_format($dc->monthly_income) : '—' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Dealer</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->dealer?->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Registration Info --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Registration</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Registered By</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->registeredBy?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Registered On</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Dealer / agent</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->dealer?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Credit Status</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ ucfirst($dc->credit_status ?? 'unrated') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Face match (FO scan + optional manual override) --}}
                @if($dcv && filled($dcv->face_match_status))
                @php
                    $fmBadge = match($dcv->face_match_status) {
                        'passed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                        'manual_verified' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                        'review' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                    };
                @endphp
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Face match</h3>
                    <div class="rounded-xl border border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/80 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full {{ $fmBadge }}">{{ str_replace('_', ' ', ucfirst($dcv->face_match_status)) }}</span>
                            @if($dcv->face_match_score !== null)
                            <span class="text-sm font-black text-gray-800 dark:text-gray-100 tabular-nums">{{ number_format((float) $dcv->face_match_score * 100, 0) }}% match</span>
                            @endif
                        </div>
                        @if($dcv->face_match_reason)
                        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">{{ $dcv->face_match_reason }}</p>
                        @endif
                        @if($dcv->face_match_status === 'manual_verified' && $dcv->faceMatchManualVerifiedBy)
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">Manual: {{ $dcv->faceMatchManualVerifiedBy->name }} · {{ $dcv->face_match_manual_verified_at?->format('d M Y, H:i') }}</p>
                        @endif
                        @can('loans.create')
                        @if(in_array($dcv->face_match_status, ['review', 'failed'], true))
                        <flux:button type="button" size="sm" variant="primary"
                            wire:click="manualVerifyFaceMatch('{{ $dc->id }}')"
                            wire:confirm="Thibitisha kuwa uso umelingana na kitambulisho (uhakiki wa mkono)?"
                            class="w-full !bg-sky-600 hover:!bg-sky-700 !text-white border-0">
                            Thibitisha uso kwa mkono
                        </flux:button>
                        @endif
                        @endcan
                    </div>
                </div>
                @endif

                {{-- Verification History --}}
                @if($dc->verifications->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Verification History</h3>
                    <div class="space-y-2">
                        @foreach($dc->verifications->sortByDesc('created_at') as $vr)
                        @php
                            $vrBadge = match($vr->status) {
                                'approved' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                'pending'  => 'bg-amber-100 text-amber-700',
                                default    => 'bg-zinc-100 text-zinc-600',
                            };
                        @endphp
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $vrBadge }}">{{ ucfirst($vr->status) }}</span>
                                <span class="text-[10px] text-gray-400">{{ $vr->reviewed_at?->format('d M Y, H:i') ?? $vr->created_at->format('d M Y') }}</span>
                            </div>
                            @if($vr->rejection_reason)
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">Reason: {{ $vr->rejection_reason }}</p>
                            @endif
                            @if($vr->notes)
                            <p class="text-xs text-gray-500 mt-1">{{ $vr->notes }}</p>
                            @endif
                            @if($vr->reviewedBy)
                            <p class="text-[10px] text-gray-400 mt-1">By: {{ $vr->reviewedBy->name }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Loans --}}
                @if($dc->loans?->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Loan History</h3>
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
                                <p class="text-xs font-mono font-semibold text-oe dark:text-oe">{{ $ln->loan_number }}</p>
                                <p class="text-[10px] text-gray-500">TZS {{ number_format($ln->principal_amount) }}</p>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $lnBadge }}">{{ ucfirst($ln->status) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex gap-2">
                @can('loans.create')
                @php $dcStage = $dcv?->stage ?? 1; @endphp
                @if($dcStage === 1 || $dcStage === 2)
                <button wire:click="openApproveModal('{{ $dc->id }}', {{ $dcStage }})" wire:click.stop
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Approve Stage {{ $dcStage }}
                </button>
                <button wire:click="openRejectModal('{{ $dc->id }}', {{ $dcStage }})"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-colors">
                    Reject
                </button>
                @elseif($dcStage === 3)
                <button wire:click="openCallModal('{{ $dc->id }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-teal-600 hover:bg-teal-700 text-white transition-colors">
                    Record Confirmation Call
                </button>
                @elseif($dcStage === 4)
                <button wire:click="openNokModal('{{ $dc->id }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                    Record NOK Call
                </button>
                @endif
                @endcan
                <button wire:click="closeDetail"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- ══ APPROVE MODAL (Stage 1 & 2) ══ --}}
    <flux:modal wire:model="showApproveModal" name="approve-stage">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <flux:heading size="lg">Approve Stage {{ $actionStage }}</flux:heading>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">
            @if($actionStage === 1) Validates IMEI and device. Client will move to Stage 2 (KYC Data).
            @else Validates KYC data and documents. Client will move to Stage 3 (Confirmation Call). @endif
        </p>
        <flux:field>
            <flux:label>Notes <span class="text-gray-400 font-normal">(optional)</span></flux:label>
            <flux:textarea wire:model="approveNotes" rows="2" placeholder="Any notes…" />
            <flux:error name="approveNotes" />
        </flux:field>
        <div class="flex justify-end gap-3 mt-5">
            <flux:button variant="ghost" wire:click="$set('showApproveModal', false)">Cancel</flux:button>
            <flux:button wire:click="approveStage" class="bg-emerald-600 hover:bg-emerald-700 text-white border-0">
                <flux:icon wire:loading wire:target="approveStage" name="arrow-path" class="size-4 animate-spin mr-1" />
                Confirm Approval
            </flux:button>
        </div>
    </flux:modal>

    {{-- ══ REJECT MODAL (Stage 1 & 2) ══ --}}
    <flux:modal wire:model="showRejectModal" name="reject-stage">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <flux:heading size="lg">Reject Stage {{ $actionStage }}</flux:heading>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">Application will be rejected and sent back to Front Officer for correction.</p>
        <div class="space-y-4">
            <flux:field>
                <flux:label>Rejection Reason <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="rejectReason" placeholder="e.g. IMEI invalid, ID photo blurry…" />
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
            <flux:button wire:click="rejectStage" variant="danger">
                <flux:icon wire:loading wire:target="rejectStage" name="arrow-path" class="size-4 animate-spin mr-1" />
                Confirm Rejection
            </flux:button>
        </div>
    </flux:modal>

    {{-- ══ CONFIRMATION CALL MODAL (Stage 3) ══ --}}
    <flux:modal wire:model="showCallModal" name="confirmation-call">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <flux:heading size="lg">Stage 3 — Confirmation Call</flux:heading>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">Record the outcome of the client confirmation call. Confirmed → advances to Stage 4.</p>
        <div class="space-y-4">
            <flux:field>
                <flux:label>Call Outcome <span class="text-red-500">*</span></flux:label>
                <flux:select wire:model="callOutcome">
                    <flux:select.option value="">— Select outcome —</flux:select.option>
                    <flux:select.option value="confirmed">✅ Confirmed</flux:select.option>
                    <flux:select.option value="not_confirmed">❌ Not Confirmed</flux:select.option>
                </flux:select>
                <flux:error name="callOutcome" />
            </flux:field>
            <flux:field>
                <flux:label>Call Notes <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                <flux:textarea wire:model="callNotes" rows="2" placeholder="What did the client say?" />
                <flux:error name="callNotes" />
            </flux:field>
        </div>
        <div class="flex justify-end gap-3 mt-5">
            <flux:button variant="ghost" wire:click="$set('showCallModal', false)">Cancel</flux:button>
            <flux:button wire:click="recordConfirmationCall" class="bg-teal-600 hover:bg-teal-700 text-white border-0">
                <flux:icon wire:loading wire:target="recordConfirmationCall" name="arrow-path" class="size-4 animate-spin mr-1" />
                Save Call Outcome
            </flux:button>
        </div>
    </flux:modal>

    {{-- ══ NOK CALL MODAL (Stage 4) ══ --}}
    <flux:modal wire:model="showNokModal" name="nok-call">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <flux:heading size="lg">Stage 4 — Next of Kin Call</flux:heading>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">Record the outcome of the Next of Kin call. Confirmed → Final Approval &amp; onboarding complete.</p>
        <div class="space-y-4">
            <flux:field>
                <flux:label>Call Outcome <span class="text-red-500">*</span></flux:label>
                <flux:select wire:model="nokOutcome">
                    <flux:select.option value="">— Select outcome —</flux:select.option>
                    <flux:select.option value="confirmed">✅ Confirmed</flux:select.option>
                    <flux:select.option value="not_confirmed">❌ Not Confirmed</flux:select.option>
                </flux:select>
                <flux:error name="nokOutcome" />
            </flux:field>
            <flux:field>
                <flux:label>Call Notes <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                <flux:textarea wire:model="nokNotes" rows="2" placeholder="What did the NOK say?" />
                <flux:error name="nokNotes" />
            </flux:field>
        </div>
        <div class="flex justify-end gap-3 mt-5">
            <flux:button variant="ghost" wire:click="$set('showNokModal', false)">Cancel</flux:button>
            <flux:button wire:click="recordNokCall" class="bg-emerald-600 hover:bg-emerald-700 text-white border-0">
                <flux:icon wire:loading wire:target="recordNokCall" name="arrow-path" class="size-4 animate-spin mr-1" />
                Save &amp; Finalise
            </flux:button>
        </div>
    </flux:modal>

</div>
