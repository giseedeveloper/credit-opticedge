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
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 text-white hover:opacity-90 transition-opacity shadow-sm">
            <flux:icon name="user-plus" class="size-4" />
            New KYC Wizard
        </a>
    </div>

    {{-- Stats Bar --}}
    @php
    $statDefs = [
        ['key' => 'total',    'label' => 'Total Customers', 'grad'   => 'from-[#4b0082] to-[#7c3aed]',                              'hero' => true,  'sub' => 'All registered'],
        ['key' => 'verified', 'label' => 'Verified',        'icolor' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',    'sub' => 'KYC approved'],
        ['key' => 'pending',  'label' => 'In Review',       'icolor' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600',          'sub' => 'Awaiting decision'],
        ['key' => 'rejected', 'label' => 'Rejected',        'icolor' => 'bg-red-100 dark:bg-red-900/30 text-red-600',               'sub' => 'Need re-submission'],
    ];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($statDefs as $sd)
        @if(!empty($sd['hero']))
        <div class="bg-gradient-to-br {{ $sd['grad'] }} rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-purple-900/20">
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
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-xs font-black flex-shrink-0">
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
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
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
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-300 transition-colors">
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
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-lg bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">
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
            @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-700 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-lg font-black">
                        {{ strtoupper(substr($dc->first_name, 0, 1).substr($dc->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">Customer Profile</p>
                        <h2 class="text-xl font-black mt-0.5">{{ $dc->full_name }}</h2>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold mt-1.5 {{ $dcKycBadge }}">
                            {{ $dcKycLabel }}
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
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Phone</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->phone }}</p>
                            @if($dc->alt_phone)<p class="text-xs text-gray-400">Alt: {{ $dc->alt_phone }}</p>@endif
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Gender</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ ucfirst($dc->gender ?? '—') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Date of Birth</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">
                                {{ $dc->date_of_birth?->format('d M Y') ?? '—' }}
                                @if($dc->date_of_birth)<span class="text-xs text-gray-400">({{ $dc->date_of_birth->age }} yrs)</span>@endif
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Email</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5 truncate">{{ $dc->email ?? '—' }}</p>
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
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Credit Status</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ ucfirst($dc->credit_status ?? 'unrated') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Registration --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Registration</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Branch</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->branch?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Vendor/Agent</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->vendor?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Registered By</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->registeredBy?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Joined</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dc->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

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
                                <p class="text-xs font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $ln->loan_number }}</p>
                                <p class="text-[10px] text-gray-500">TZS {{ number_format($ln->principal_amount) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $lnBadge }}">{{ ucfirst($ln->status) }}</span>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $ln->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex gap-2">
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
