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

    {{-- Add Vendor / Dealer Modal --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="closeCreateModal">
        <div class="mx-4 w-full max-w-3xl rounded-3xl border border-gray-100 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-5 dark:border-zinc-800">
                <div class="flex items-start gap-3">
                    <x-fluent-icon name="building-storefront" size="md" palette="amber" />
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Add Vendor / Dealer</h3>
                        <p class="mt-0.5 text-xs text-gray-400">Register a partner, assign the operating branch, and optionally link a responsible account owner.</p>
                    </div>
                </div>
                <button wire:click="closeCreateModal" class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-zinc-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 px-6 py-5 md:grid-cols-2">
                <div class="md:col-span-2 rounded-2xl border border-orange-200 bg-oe-soft/80 p-4 dark:border-orange-900/40 dark:bg-orange-950/30">
                    <div class="flex items-start gap-3">
                        <x-fluent-icon name="shield-check" size="sm" palette="amber" />
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-oe-hover dark:text-orange-300">Branch accountability</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Every dealer should be tied to a branch so customers, financed devices, commissions, and cashier reports remain attributable to the correct shop floor.</p>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Vendor / Dealer Name *</label>
                    <input wire:model="newName" type="text" placeholder="Kariakoo Devices Hub"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    @error('newName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Vendor Code</label>
                    <input wire:model="newCode" type="text" placeholder="Leave blank to auto-generate"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    <p class="mt-1 text-[11px] text-gray-400">Useful when reconciling dealer reports or stock handover sheets.</p>
                    @error('newCode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status *</label>
                    <select wire:model="newStatus"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="closed">Closed</option>
                    </select>
                    @error('newStatus') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Branch *</label>
                    <select wire:model.live="newBranchId"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <option value="">Select branch…</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->code }} · {{ $branch->name }}{{ $branch->is_headquarter ? ' (HQ)' : '' }}</option>
                        @endforeach
                    </select>
                    @error('newBranchId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Account Owner</label>
                    <select wire:model="newOwnerUserId"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:disabled:bg-zinc-800/60"
                        @disabled($newBranchId === '')>
                        <option value="">Select owner…</option>
                        @foreach($createOwnerOptions as $owner)
                        <option value="{{ $owner->id }}">
                            {{ $owner->name }}
                            @if($owner->branch)
                                · {{ $owner->branch->name }}
                            @endif
                            @if($owner->roles->isNotEmpty())
                                · {{ ucwords(str_replace(['-', '_'], ' ', $owner->roles->pluck('name')->sort()->first())) }}
                            @endif
                        </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">
                        @if($newBranchId === '')
                            Choose the branch first to narrow staff options for this dealer.
                        @else
                            Optional. Useful when one staff member or manager is responsible for the relationship.
                        @endif
                    </p>
                    @error('newOwnerUserId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Phone</label>
                    <input wire:model="newPhone" type="text" placeholder="+255 7XX XXX XXX"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    @error('newPhone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</label>
                    <input wire:model="newEmail" type="email" placeholder="dealer@example.com"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    @error('newEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">TIN Number</label>
                    <input wire:model="newTinNumber" type="text" placeholder="123-456-789"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    @error('newTinNumber') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Commission Rate (%) *</label>
                    <input wire:model="newCommissionRate" type="number" min="0" max="100" step="0.01" placeholder="5"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    @error('newCommissionRate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Address</label>
                    <textarea wire:model="newAddress" rows="3" placeholder="Shop floor location or dealer address"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-oe dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                    @error('newAddress') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 rounded-b-3xl bg-gray-50 px-6 py-4 dark:bg-zinc-800/60">
                <button wire:click="closeCreateModal" class="rounded-xl px-4 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-700">Cancel</button>
                <button wire:click="createVendor" wire:loading.attr="disabled"
                    class="rounded-xl bg-oe px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-oe-hover disabled:opacity-60">
                    Add Vendor
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="building-storefront" size="lg" palette="amber" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Vendor Directory</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Distribution partners, performance metrics &amp; commission tracking</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 rounded-xl border border-oe/20 bg-oe-soft px-4 py-2 dark:border-oe/25 dark:bg-oe/10">
                <x-fluent-icon name="building-storefront" size="xs" palette="amber" />
                <span class="text-sm font-bold text-oe dark:text-oe">{{ $stats['total'] }} vendors</span>
            </div>
            @if(auth()->user()->canAccess('vendors.create'))
            <button wire:click="openCreateModal"
                class="flex items-center gap-2 rounded-xl bg-oe px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-oe-hover">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Vendor
            </button>
            @endif
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-6">
        <div class="bg-gradient-to-br from-oe to-oe-hover rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-oe/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="building-storefront" size="sm" />
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">Total Vendors</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-white/60 mt-1">{{ $stats['active'] }} active partners</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="check-circle" size="sm" palette="emerald" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['active']) }}</p>
            <p class="text-xs text-gray-400 mt-1">of {{ $stats['total'] }} registered vendors</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="device-phone-mobile" size="sm" palette="sky" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Stock</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['total_stock']) }}</p>
            <p class="text-xs text-gray-400 mt-1">inventory units system-wide</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="users" size="sm" palette="blue" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Customers Served</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['customers_served']) }}</p>
            <p class="text-xs text-gray-400 mt-1">registered through vendor channels</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="banknotes" size="sm" palette="emerald" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Paid Out</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['total_paid_out'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">commissions already withdrawn</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="banknotes" size="sm" palette="amber" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Portfolio</span>
            </div>
            <p class="text-xl font-black text-gray-900 dark:text-white">TZS {{ number_format($stats['loan_portfolio'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">active + overdue financed value</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1">
            @foreach(['' => 'All', 'active' => 'Active', 'suspended' => 'Suspended', 'closed' => 'Closed'] as $val => $label)
            <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors
                        {{ $statusFilter === $val
                            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <div class="w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Name, phone, email, code…" icon="magnifying-glass" />
            </div>
            <flux:select wire:model.live="branchFilter" class="w-44">
                <flux:select.option value="">All Branches</flux:select.option>
                @foreach($branches as $b)
                <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Vendor Cards Grid --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($vendors as $vendor)
        @php
            $status = $vendor->status ?? 'active';
            $statusBadge = match($status) {
                'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                'suspended' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                'closed' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
                default => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
            };
            $walletBal = (float) ($vendor->wallet?->balance ?? 0);
            $totalEarned = (float) ($vendor->wallet?->total_earned ?? 0);
            $loanValue = (float) ($vendor->loans_sum_principal_amount ?? 0);
        @endphp
        <div wire:key="vendor-{{ $vendor->id }}"
             wire:click="openDetail('{{ $vendor->id }}')"
             class="group bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 p-5 shadow-sm
                    hover:shadow-lg hover:border-oe/25 dark:hover:border-oe/25 hover:-translate-y-0.5
                    transition-all duration-200 cursor-pointer">

            {{-- Card Header --}}
            <div class="flex items-start gap-3 mb-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-oe/90 to-oe text-white font-black text-sm flex-shrink-0 shadow-md shadow-oe/20">
                    {{ strtoupper(substr($vendor->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-900 dark:text-white truncate">{{ $vendor->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $vendor->code ?? '—' }} · {{ $vendor->phone ?? '—' }}</p>
                    @if($vendor->branch)
                    <p class="text-[10px] text-oe-hover dark:text-oe mt-0.5 font-semibold">{{ $vendor->branch->name }}</p>
                    @endif
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0 {{ $statusBadge }}">
                    {{ ucfirst($status) }}
                </span>
            </div>

            {{-- Metrics Grid --}}
            <div class="grid grid-cols-3 gap-2 mb-3">
                <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Stock</p>
                    <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">{{ $vendor->inventory_units_count }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Loans</p>
                    <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">{{ $vendor->loans_count }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Rate</p>
                    <p class="text-base font-black text-oe dark:text-oe mt-0.5">{{ $vendor->commission_rate ?? 0 }}%</p>
                </div>
            </div>

            {{-- Wallet Balance --}}
            @if($walletBal > 0 || $totalEarned > 0)
            <div class="bg-gradient-to-r from-oe-soft to-oe-soft dark:from-blue-900/20 dark:to-blue-900/20 rounded-xl p-3 border border-oe/20 dark:border-oe/20 mb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] text-oe-hover uppercase font-bold">Wallet Balance</p>
                        <p class="text-sm font-black text-oe-hover dark:text-oe">TZS {{ number_format($walletBal, 0) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Total Earned</p>
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-300">TZS {{ number_format($totalEarned, 0) }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Contact Info --}}
            <div class="space-y-1">
                @if($vendor->email)
                <p class="text-[10px] text-gray-400 flex items-center gap-1.5 truncate">
                    <flux:icon name="envelope" class="size-3 flex-shrink-0" />{{ $vendor->email }}
                </p>
                @endif
                @if($vendor->address)
                <p class="text-[10px] text-gray-400 flex items-center gap-1.5 truncate">
                    <flux:icon name="map-pin" class="size-3 flex-shrink-0" />{{ $vendor->address }}
                </p>
                @endif
            </div>

            {{-- Hover cta --}}
            <div class="mt-3 pt-3 border-t border-gray-50 dark:border-zinc-800 flex items-center justify-between">
                <span class="text-[10px] text-gray-300 dark:text-zinc-600 group-hover:text-oe transition-colors font-semibold">Click to view details</span>
                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-zinc-600 group-hover:text-oe-hover transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </div>
        @empty
        <div class="md:col-span-3 flex flex-col items-center justify-center py-16">
            <flux:icon name="building-storefront" class="size-16 text-gray-300 dark:text-zinc-600 mb-3" />
            <p class="font-semibold text-gray-500">No vendors found</p>
            <p class="text-xs text-gray-400 mt-1">
                @if($search || $statusFilter || $branchFilter)
                    Try clearing your filters
                @else
                    No vendors registered yet
                @endif
            </p>
        </div>
        @endforelse
    </div>

    @if($vendors->hasPages())
    <div>{{ $vendors->links() }}</div>
    @endif

    {{-- ══ VENDOR DETAIL SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative flex w-full max-w-2xl flex-col overflow-y-auto bg-white shadow-2xl dark:bg-zinc-900">

            @if($this->detailVendor)
            @php
                $dv = $this->detailVendor;
                $dvStatus = $dv->status ?? 'active';
                $dvBadge = match($dvStatus) {
                    'active' => 'bg-emerald-400/30 text-emerald-100',
                    'suspended' => 'bg-amber-400/30 text-amber-100',
                    'closed' => 'bg-zinc-300/20 text-zinc-100',
                    default => 'bg-red-400/30 text-red-100',
                };
                $dvWallet = $dv->wallet;
                $dvBalance = (float) ($dvWallet?->balance ?? 0);
                $dvEarned = (float) ($dvWallet?->total_earned ?? 0);
                $dvWithdrawn = (float) ($dvWallet?->total_withdrawn ?? 0);
                $dvLoanValue = (float) ($dv->loans_sum_principal_amount ?? 0);
                $dvCollected = (float) ($dv->loans_amount_paid_sum ?? 0);
                $dvOutstanding = (float) ($dv->loans_outstanding_balance_sum ?? 0);
                $dvTotalPayable = (float) ($dv->loans_total_payable_sum ?? 0);
                $dvPendingCommissions = (float) ($dv->pending_commissions_sum ?? 0);
                $dvPaidCommissions = (float) ($dv->paid_commissions_sum ?? 0);
                $dvRecordedCommissions = (float) ($dv->recorded_commissions_sum ?? 0);
                $dvAverageTicket = (int) round($dv->loans_count > 0 ? ($dvLoanValue / $dv->loans_count) : 0);
                $dvApprovalRate = $dv->customers_count > 0 ? round((($dv->approved_customers_count ?? 0) / $dv->customers_count) * 100) : 0;
                $dvCollectionRate = $dvTotalPayable > 0 ? round(($dvCollected / $dvTotalPayable) * 100) : 0;
                $dvOwnerRole = $dv->ownerUser?->roles?->pluck('name')->sort()->first();
            @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-oe to-oe-hover text-white">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white font-black text-lg flex-shrink-0">
                        {{ strtoupper(substr($dv->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $dvBadge }}">
                                {{ ucfirst($dvStatus) }}
                            </span>
                            @if($dv->code)
                            <span class="text-white/50 text-[10px] font-mono">{{ $dv->code }}</span>
                            @endif
                        </div>
                        <h2 class="text-xl font-black">{{ $dv->name }}</h2>
                        <p class="text-white/60 text-xs mt-0.5">{{ $dv->branch?->name ?? 'No branch' }}</p>
                    </div>
                </div>
                <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Wallet Strip --}}
            <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100 bg-gray-50 dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-800/60">
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Wallet Balance</p>
                    <p class="text-base font-black text-oe dark:text-oe mt-0.5">TZS {{ number_format($dvBalance, 0) }}</p>
                </div>
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Total Earned</p>
                    <p class="text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5">TZS {{ number_format($dvEarned, 0) }}</p>
                </div>
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Withdrawn</p>
                    <p class="text-base font-black text-amber-600 dark:text-amber-400 mt-0.5">TZS {{ number_format($dvWithdrawn, 0) }}</p>
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">
                {{-- Snapshot --}}
                <div>
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">Business Snapshot</h3>
                    <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-zinc-800">
                            <p class="text-[10px] font-bold uppercase text-gray-400">Customers Served</p>
                            <p class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($dv->customers_count) }}</p>
                            <p class="text-[10px] text-gray-400">{{ $dvApprovalRate }}% KYC approved</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-zinc-800">
                            <p class="text-[10px] font-bold uppercase text-gray-400">Active Loans</p>
                            <p class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($dv->active_loans_count) }}</p>
                            <p class="text-[10px] text-red-500">{{ number_format($dv->overdue_loans_count) }} overdue</p>
                        </div>
                        <div class="rounded-xl bg-oe-soft p-3 dark:bg-oe/10">
                            <p class="text-[10px] font-bold uppercase text-oe-hover">Financed Value</p>
                            <p class="mt-1 text-lg font-black text-oe-hover dark:text-oe">TZS {{ number_format($dvLoanValue, 0) }}</p>
                            <p class="text-[10px] text-gray-400">Avg ticket TZS {{ number_format($dvAverageTicket, 0) }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-900/20">
                            <p class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">Collections</p>
                            <p class="mt-1 text-lg font-black text-emerald-700 dark:text-emerald-300">TZS {{ number_format($dvCollected, 0) }}</p>
                            <p class="text-[10px] text-gray-400">{{ $dvCollectionRate }}% of total payable</p>
                        </div>
                    </div>
                </div>

                {{-- Contact & Identity --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Contact & Identity</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Phone</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dv->phone ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Email</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5 truncate">{{ $dv->email ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">TIN Number</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5 font-mono">{{ $dv->tin_number ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Commission Rate</p>
                            <p class="text-sm font-black text-oe dark:text-oe mt-0.5">{{ $dv->commission_rate ?? 0 }}%</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 col-span-2">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Address</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100 mt-0.5">{{ $dv->address ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Branch & Owner --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Branch & Management</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Branch</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dv->branch?->name ?? '—' }}</p>
                            @if($dv->branch?->region)
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $dv->branch->region }}</p>
                            @endif
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">Account Owner</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dv->ownerUser?->name ?? '—' }}</p>
                            @if($dvOwnerRole)
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ ucwords(str_replace(['-', '_'], ' ', $dvOwnerRole)) }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Portfolio & Earnings --}}
                <div>
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">Portfolio & Earnings</h3>
                    <div class="grid grid-cols-2 gap-2 lg:grid-cols-3">
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-zinc-800">
                            <p class="text-[10px] font-bold uppercase text-gray-400">Stock Units</p>
                            <p class="mt-1 text-xl font-black text-gray-900 dark:text-white">{{ number_format($dv->inventory_units_count) }}</p>
                            <p class="text-[10px] text-gray-400">current assigned inventory</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-zinc-800">
                            <p class="text-[10px] font-bold uppercase text-gray-400">Completed Loans</p>
                            <p class="mt-1 text-xl font-black text-gray-900 dark:text-white">{{ number_format($dv->completed_loans_count) }}</p>
                            <p class="text-[10px] text-gray-400">fully settled facilities</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-zinc-800">
                            <p class="text-[10px] font-bold uppercase text-gray-400">Outstanding</p>
                            <p class="mt-1 text-lg font-black text-gray-900 dark:text-white">TZS {{ number_format($dvOutstanding, 0) }}</p>
                            <p class="text-[10px] text-gray-400">remaining receivable</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 p-3 dark:bg-amber-900/20">
                            <p class="text-[10px] font-bold uppercase text-amber-600 dark:text-amber-400">Recorded Commissions</p>
                            <p class="mt-1 text-lg font-black text-amber-700 dark:text-amber-300">TZS {{ number_format($dvRecordedCommissions, 0) }}</p>
                            <p class="text-[10px] text-gray-400">all ledgered commission value</p>
                        </div>
                        <div class="rounded-xl bg-oe-soft p-3 dark:bg-oe/10">
                            <p class="text-[10px] font-bold uppercase text-oe-hover">Pending / Posted</p>
                            <p class="mt-1 text-lg font-black text-oe-hover dark:text-oe">TZS {{ number_format($dvPendingCommissions, 0) }}</p>
                            <p class="text-[10px] text-gray-400">awaiting payout clearance</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-900/20">
                            <p class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">Marked Paid</p>
                            <p class="mt-1 text-lg font-black text-emerald-700 dark:text-emerald-300">TZS {{ number_format($dvPaidCommissions, 0) }}</p>
                            <p class="text-[10px] text-gray-400">ledger entries with paid status</p>
                        </div>
                    </div>
                </div>

                {{-- Service Reach --}}
                @if($dv->customers->count())
                <div>
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">Recent Customers Served</h3>
                    <div class="grid gap-2 lg:grid-cols-2">
                        @foreach($dv->customers as $customer)
                        @php
                            $customerKycBadge = in_array($customer->kyc_status, ['approved', 'verified'], true)
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
                        @endphp
                        <div class="rounded-xl bg-gray-50 px-3 py-3 dark:bg-zinc-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ $customer->full_name ?: 'Unnamed customer' }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-400">{{ $customer->formattedPhone('phone') ?? $customer->phone ?? 'No phone' }}</p>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold {{ $customerKycBadge }}">
                                    {{ ucfirst($customer->kyc_status ?? 'pending') }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-[11px] text-gray-400">
                                <span>{{ $customer->loans_count }} {{ Str::plural('loan', $customer->loans_count) }}</span>
                                <span>{{ $customer->created_at?->format('d M Y') ?? '—' }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Recent Commission Ledger --}}
                @if($dv->commissionLedgers->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">
                        Recent Commissions
                        <span class="ml-1 px-1.5 py-0.5 rounded-full bg-oe-soft text-oe text-[9px] font-bold">{{ $dv->commissionLedgers->count() }}</span>
                    </h3>
                    <div class="space-y-1.5">
                        @foreach($dv->commissionLedgers as $ledger)
                        @php
                            $lBadge = match($ledger->status) {
                                'paid'    => 'bg-emerald-100 text-emerald-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                default   => 'bg-zinc-100 text-zinc-600',
                            };
                        @endphp
                        <div class="rounded-xl bg-gray-50 px-3 py-3 dark:bg-zinc-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $ledger->description ?? 'Commission' }}
                                </p>
                                <p class="mt-0.5 text-[10px] text-gray-400">{{ $ledger->posted_at?->format('d M Y') ?? '—' }}</p>
                                @if($ledger->loan?->customer)
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $ledger->loan->loan_number }} · {{ $ledger->loan->customer->full_name }}
                                </p>
                                @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-oe dark:text-oe">
                                        TZS {{ number_format($ledger->commission_amount, 0) }}
                                    </p>
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold {{ $lBadge }}">{{ ucfirst($ledger->status) }}</span>
                                </div>
                            </div>
                            @if($ledger->commission_rate)
                            <div class="mt-2 flex items-center justify-between text-[11px] text-gray-400">
                                <span>{{ $ledger->commission_rate }}% commission rate</span>
                                <span>{{ $ledger->loan?->status ? ucfirst($ledger->loan->status) . ' loan' : 'No linked loan' }}</span>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Recent Loans --}}
                @if($dv->loans->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Recent Loans</h3>
                    <div class="space-y-1.5">
                        @foreach($dv->loans as $loan)
                        @php
                            $loanBadge = match($loan->status) {
                                'active'    => 'bg-emerald-100 text-emerald-700',
                                'overdue'   => 'bg-red-100 text-red-700',
                                'completed' => 'bg-sky-100 text-sky-700',
                                'defaulted' => 'bg-rose-100 text-rose-700',
                                default     => 'bg-zinc-100 text-zinc-600',
                            };
                        @endphp
                        <div class="rounded-xl bg-gray-50 px-3 py-3 dark:bg-zinc-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                <p class="text-xs font-mono font-bold text-oe dark:text-oe">{{ $loan->loan_number }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $loan->customer?->full_name ?? '—' }}</p>
                                @if($loan->inventoryUnit?->phoneModel)
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $loan->inventoryUnit->phoneModel->brand?->name }} {{ $loan->inventoryUnit->phoneModel->name }}
                                </p>
                                @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-100">TZS {{ number_format($loan->principal_amount, 0) }}</p>
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold {{ $loanBadge }}">{{ ucfirst($loan->status) }}</span>
                                </div>
                            </div>
                            <div class="mt-2 grid grid-cols-3 gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                <div>
                                    <p class="font-semibold uppercase text-[9px] text-gray-400">Deposit</p>
                                    <p>TZS {{ number_format($loan->deposit_paid, 0) }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold uppercase text-[9px] text-gray-400">Paid</p>
                                    <p>TZS {{ number_format($loan->amount_paid, 0) }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold uppercase text-[9px] text-gray-400">Outstanding</p>
                                    <p>TZS {{ number_format($loan->outstanding_balance, 0) }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Timeline --}}
                <div>
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">Business Timeline</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-zinc-800">
                            <p class="text-[10px] font-semibold uppercase text-gray-400">Joined Directory</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $dv->created_at?->format('d M Y, H:i') ?? '—' }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-zinc-800">
                            <p class="text-[10px] font-semibold uppercase text-gray-400">Last Wallet Activity</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $dvWallet?->last_transaction_at?->format('d M Y, H:i') ?? 'No wallet activity yet' }}</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800">
                <button wire:click="closeDetail"
                        class="w-full py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

</div>
