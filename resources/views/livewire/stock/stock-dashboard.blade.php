<div class="flex flex-col gap-6">

    {{-- Toast --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : 'bg-red-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="archive-box" size="lg" palette="sky" />
            <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Stock Manager</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Real-time inventory across HQ and all vendors</p>
            </div>
        </div>
        <div class="flex gap-2">
            <flux:button wire:click="$set('showImportModal', true)" variant="ghost" size="sm" icon="arrow-up-tray">
                Import Excel
            </flux:button>
            <flux:button :href="route('stock.brands')" variant="primary" size="sm" wire:navigate icon="plus">
                Add Stock
            </flux:button>
        </div>
    </div>

    {{-- Summary Cards (real counts from DB) --}}
    @php
        $cards = [
            ['label' => 'Available',    'key' => 'available',    'color' => 'from-teal-600 to-emerald-700',  'icon' => 'check-circle'],
            ['label' => 'HQ Stock',     'key' => 'hq_stock',     'color' => 'from-blue-700 to-blue-800', 'icon' => 'building-office'],
            ['label' => 'Vendor Stock', 'key' => 'vendor_stock', 'color' => 'from-sky-600 to-blue-800',      'icon' => 'building-storefront'],
            ['label' => 'In Transit',   'key' => 'in_transit',   'color' => 'from-amber-500 to-orange-600',  'icon' => 'truck'],
            ['label' => 'Sold',         'key' => 'sold',         'color' => 'from-emerald-700 to-teal-800',  'icon' => 'check-badge'],
            ['label' => 'Returned',     'key' => 'returned',     'color' => 'from-rose-600 to-red-700',      'icon' => 'arrow-uturn-left'],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
        @foreach($cards as $card)
            <button wire:click="$set('statusFilter', '{{ $card['key'] }}')"
                    class="rounded-2xl bg-gradient-to-br {{ $card['color'] }} p-4 text-white shadow-md hover:shadow-lg hover:scale-[1.02] transition-all text-left">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-semibold text-white/70 uppercase tracking-wider">{{ $card['label'] }}</span>
                    <x-fluent-icon :name="$card['icon']" size="xs" />
                </div>
                <div class="text-2xl font-bold">{{ number_format($summary[$card['key']] ?? 0) }}</div>
                <div class="mt-0.5 text-[10px] text-white/60">units</div>
            </button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2">
            <flux:input wire:model.live.debounce.300ms="search"
                        placeholder="Search IMEI, serial, brand or model…"
                        icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter">
            <flux:select.option value="">All Statuses</flux:select.option>
            @foreach(['available','hq_stock','vendor_stock','in_transit','sold','returned','lost'] as $s)
                <flux:select.option value="{{ $s }}">{{ str_replace('_',' ',ucfirst($s)) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="brandFilter">
            <flux:select.option value="">All Brands</flux:select.option>
            @foreach($brands as $brand)
                <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 -mt-3">
        <div class="md:col-span-2">
            <flux:select wire:model.live="branchFilter">
                <flux:select.option value="">All Branches</flux:select.option>
                @foreach($branches as $branch)
                    <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if($search || $statusFilter || $brandFilter || $branchFilter)
            <div class="flex items-center">
                <button wire:click="$set('search',''); $set('statusFilter',''); $set('brandFilter',''); $set('branchFilter','')"
                        class="text-xs text-zinc-500 hover:text-red-500 flex items-center gap-1 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/80">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Device</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">IMEI 1</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden md:table-cell">IMEI 2</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden lg:table-cell">Branch / Vendor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden lg:table-cell">Purchase</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden lg:table-cell">Received</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-50 dark:divide-zinc-700/50">
                @forelse($units as $unit)
                    @php
                        $sc = match($unit->status) {
                            'hq_stock'     => 'purple',
                            'vendor_stock' => 'blue',
                            'in_transit'   => 'yellow',
                            'sold'         => 'green',
                            'available'    => 'teal',
                            'returned'     => 'red',
                            'lost'         => 'zinc',
                            default        => 'zinc',
                        };
                    @endphp
                    <tr wire:key="unit-{{ $unit->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-zinc-900 dark:text-white text-sm">
                                {{ $unit->phoneModel?->brand?->name }} {{ $unit->phoneModel?->name }}
                            </div>
                            <div class="text-xs text-zinc-400 mt-0.5">SN: {{ $unit->serial_number ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $unit->imei_1 ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-400 hidden md:table-cell">{{ $unit->imei_2 ?? '—' }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $unit->branch?->name ?? '—' }}</div>
                            <div class="text-xs text-zinc-400">{{ $unit->vendor?->name ?? 'HQ Direct' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$sc" size="sm">{{ str_replace('_', ' ', ucfirst($unit->status)) }}</flux:badge>
                            @if($unit->loan)
                                <div class="text-[10px] text-zinc-400 mt-1">{{ $unit->loan->loan_number }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400 hidden lg:table-cell">
                            TZS {{ number_format($unit->purchase_price ?? 0) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-400 hidden lg:table-cell">
                            {{ $unit->received_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="openDetail('{{ $unit->id }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-50 text-orange-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Details
                                </button>
                                @can('devices.edit')
                                <button wire:click="openStatusModal('{{ $unit->id }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Status
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <flux:icon name="archive-box" class="size-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-zinc-500 font-medium">No inventory units found</p>
                            <p class="text-zinc-400 text-xs mt-1">Try adjusting your filters</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($units->hasPages())
            <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">
                {{ $units->links() }}
            </div>
        @endif
    </div>

    {{-- ── DETAIL SLIDE-OVER ────────────────────────────────────────── --}}
    <div x-data="{ open: @entangle('showDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end"
         style="display:none">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>

        {{-- Panel --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-xl bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">

            @if($detailUnit)
            @php
                $du     = $detailUnit;
                $model  = $du->phoneModel;
                $brand  = $model?->brand;
                $specs  = $model?->specifications ?? [];
                $margin = $model && $model->retail_price && $du->purchase_price
                            ? round(((float)$model->retail_price - (float)$du->purchase_price) / (float)$model->retail_price * 100, 1)
                            : null;
                $statusColor = match($du->status) {
                    'available'    => 'bg-teal-100 text-teal-700',
                    'hq_stock'     => 'bg-blue-100 text-orange-600',
                    'vendor_stock' => 'bg-blue-100 text-orange-600',
                    'in_transit'   => 'bg-amber-100 text-amber-700',
                    'sold'         => 'bg-green-100 text-green-700',
                    'returned'     => 'bg-red-100 text-red-700',
                    default        => 'bg-zinc-100 text-zinc-600',
                };
            @endphp

            {{-- Slide header --}}
            <div class="flex items-start justify-between px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                <div>
                    <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">{{ $brand?->name }}</p>
                    <h2 class="text-xl font-bold mt-0.5">{{ $model?->name ?? 'Unknown Device' }}</h2>
                    <p class="text-xs text-white/60 mt-1">SN: {{ $du->serial_number ?? '—' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $statusColor }}">
                        {{ str_replace('_', ' ', ucfirst($du->status)) }}
                    </span>
                    <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-6">

                {{-- Identity & IMEI --}}
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Identity</h3>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach([
                            ['IMEI 1',         $du->imei_1 ?? '—'],
                            ['IMEI 2',         $du->imei_2 ?? '—'],
                            ['Serial Number',  $du->serial_number ?? '—'],
                            ['Received',       $du->received_at?->format('d M Y') ?? '—'],
                        ] as [$k,$v])
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">{{ $k }}</p>
                            <p class="text-sm font-mono font-semibold text-zinc-800 dark:text-zinc-100 mt-0.5 break-all">{{ $v }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Pricing --}}
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Pricing</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Purchase</p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mt-0.5">TZS {{ number_format((float)$du->purchase_price) }}</p>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Retail</p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mt-0.5">TZS {{ number_format((float)($model?->retail_price ?? 0)) }}</p>
                        </div>
                        <div class="bg-{{ $margin !== null && $margin > 0 ? 'teal' : 'zinc' }}-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Margin</p>
                            <p class="text-sm font-bold {{ $margin !== null && $margin > 0 ? 'text-teal-600' : 'text-zinc-500' }} mt-0.5">
                                {{ $margin !== null ? $margin.'%' : '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Specifications --}}
                @if(!empty($specs))
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Specifications</h3>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($specs as $specKey => $specVal)
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-blue-400 uppercase tracking-wider">{{ ucfirst($specKey) }}</p>
                            <p class="text-sm font-bold text-orange-600 dark:text-blue-300 mt-0.5">{{ $specVal }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Location --}}
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Location</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Branch</p>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mt-0.5">{{ $du->branch?->name ?? 'Not Assigned' }}</p>
                            <p class="text-[10px] text-zinc-400">{{ $du->branch?->region ?? '' }}</p>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Vendor</p>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mt-0.5">{{ $du->vendor?->name ?? 'HQ Direct' }}</p>
                            <p class="text-[10px] text-zinc-400">{{ $du->vendor?->code ?? '' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Linked Loan --}}
                @if($du->loan)
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Linked Loan</h3>
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ $du->loan->loan_number }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold
                                {{ match($du->loan->status) {
                                    'active'    => 'bg-green-100 text-green-700',
                                    'completed' => 'bg-teal-100 text-teal-700',
                                    'overdue'   => 'bg-red-100 text-red-600',
                                    default     => 'bg-zinc-100 text-zinc-600' } }}">
                                {{ ucfirst($du->loan->status) }}
                            </span>
                        </div>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="text-zinc-400 text-xs">Customer:</span>
                            {{ $du->loan->customer?->first_name }} {{ $du->loan->customer?->last_name }}
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-zinc-400">Principal:</span>
                                <span class="font-semibold ml-1">TZS {{ number_format((float)$du->loan->principal_amount) }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-400">Outstanding:</span>
                                <span class="font-semibold ml-1 {{ (float)$du->loan->outstanding_balance > 0 ? 'text-red-600' : 'text-teal-600' }}">
                                    TZS {{ number_format((float)$du->loan->outstanding_balance) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Transfer History --}}
                @if($du->stockTransfers && $du->stockTransfers->count())
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Transfer History</h3>
                    <div class="space-y-2">
                        @foreach($du->stockTransfers->sortByDesc('created_at')->take(5) as $transfer)
                        <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-800 rounded-lg px-3 py-2 text-xs">
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $transfer->created_at?->format('d M Y') }}</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $transfer->reference ?? '—' }}</span>
                            <span class="text-zinc-400">{{ ucfirst($transfer->status ?? '—') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Slide footer --}}
            <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800 flex gap-2">
                @can('devices.edit')
                <button wire:click="openStatusModal('{{ $du->id }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-orange-500 hover:bg-orange-600 text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                    Change Status
                </button>
                @endcan
                <button wire:click="closeDetail"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- ── STATUS UPDATE MODAL ──────────────────────────────────────── --}}
    <flux:modal wire:model="showStatusModal" name="update-status">
        <flux:heading size="lg">Update Device Status</flux:heading>
        <flux:text size="sm" class="mb-4">Select the new status and optionally add a note for the audit trail.</flux:text>
        <div class="space-y-4">
            <flux:field>
                <flux:label>New Status</flux:label>
                <flux:select wire:model="newStatus">
                    <flux:select.option value="">— Select —</flux:select.option>
                    @foreach(['available','hq_stock','vendor_stock','in_transit','sold','returned','lost'] as $s)
                        <flux:select.option value="{{ $s }}">{{ str_replace('_',' ',ucfirst($s)) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="newStatus" />
            </flux:field>
            <flux:field>
                <flux:label>Note <span class="text-zinc-400">(optional)</span></flux:label>
                <flux:input wire:model="statusNote" placeholder="Reason for status change…" />
            </flux:field>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <flux:button wire:click="$set('showStatusModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="updateStatus" variant="primary">
                Save Status
                <flux:icon wire:loading wire:target="updateStatus" name="arrow-path" class="size-4 animate-spin ml-1" />
            </flux:button>
        </div>
    </flux:modal>

    {{-- ── IMPORT MODAL ─────────────────────────────────────────────── --}}
    <flux:modal wire:model="showImportModal" name="import-inventory">
        <flux:heading size="lg">Import Inventory from Excel</flux:heading>
        <flux:text size="sm" class="mb-4">
            Upload an Excel file with columns:
            <code class="bg-zinc-100 dark:bg-zinc-800 px-1 rounded text-xs">brand, model, imei_1, imei_2, serial_number, purchase_price</code>
        </flux:text>
        <form wire:submit="importInventory">
            <flux:field>
                <flux:label>Excel / CSV File</flux:label>
                <flux:input type="file" wire:model="importFile" accept=".xlsx,.csv,.xls" />
                <flux:error name="importFile" />
            </flux:field>
            <div class="flex justify-end gap-2 mt-6">
                <flux:button wire:click="$set('showImportModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="arrow-up-tray">
                    Import
                    <flux:icon wire:loading wire:target="importInventory" name="arrow-path" class="size-4 animate-spin ml-1" />
                </flux:button>
            </div>
        </form>
    </flux:modal>

</div>
