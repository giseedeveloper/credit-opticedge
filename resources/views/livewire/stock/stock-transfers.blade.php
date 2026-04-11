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
            <x-fluent-icon name="arrows-right-left" size="lg" palette="amber" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Stock Transfers</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Track device movements between HQ, vendors and branches</p>
            </div>
        </div>
        @can('devices.create')
        <flux:button variant="primary" wire:click="openCreateModal" icon="plus">New Transfer</flux:button>
        @endcan
    </div>

    {{-- Stats Bar --}}
    @php
    $statDefs = [
        ['key' => 'pending',    'label' => 'Pending',    'icon' => 'clock',             'grad' => 'from-amber-500 to-orange-500'],
        ['key' => 'in_transit', 'label' => 'In Transit', 'icon' => 'truck',             'grad' => 'from-orange-500 to-orange-500'],
        ['key' => 'delivered',  'label' => 'Delivered',  'icon' => 'check-circle',      'grad' => 'from-emerald-500 to-teal-600'],
        ['key' => 'cancelled',  'label' => 'Cancelled',  'icon' => 'x-circle',          'grad' => 'from-rose-500 to-red-600'],
    ];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($statDefs as $sd)
        @php $isFirst = $sd['key'] === 'in_transit'; @endphp
        @if($isFirst)
        <div class="bg-gradient-to-br {{ $sd['grad'] }} rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-blue-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon :name="$sd['icon']" size="sm" />
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">{{ $sd['label'] }}</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($statCounts[$sd['key']] ?? 0) }}</p>
            <p class="text-xs text-white/60 mt-1">Currently moving</p>
        </div>
        @else
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon :name="$sd['icon']" size="sm" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $sd['label'] }}</span>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($statCounts[$sd['key']] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Total records</p>
        </div>
        @endif
        @endforeach
    </div>

    {{-- Filters Row --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search ref, IMEI, model…" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-44">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="in_transit">In Transit</flux:select.option>
            <flux:select.option value="delivered">Delivered</flux:select.option>
            <flux:select.option value="cancelled">Cancelled</flux:select.option>
        </flux:select>
    </div>

    {{-- Transfers Table --}}
    <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Device</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Route</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Shipped</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">By</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                @forelse($transfers as $transfer)
                @php
                    $statusBadge = match($transfer->status) {
                        'pending'    => ['color' => 'bg-amber-100 text-amber-700',   'dot' => 'bg-amber-400'],
                        'in_transit' => ['color' => 'bg-blue-100 text-orange-600',     'dot' => 'bg-blue-400'],
                        'delivered'  => ['color' => 'bg-emerald-100 text-emerald-700','dot'=> 'bg-emerald-400'],
                        'cancelled'  => ['color' => 'bg-red-100 text-red-700',       'dot' => 'bg-red-400'],
                        default      => ['color' => 'bg-zinc-100 text-zinc-600',     'dot' => 'bg-zinc-400'],
                    };
                    $fromType = class_basename($transfer->from_type ?? '');
                    $toType   = class_basename($transfer->to_type ?? '');
                @endphp
                <tr wire:key="transfer-{{ $transfer->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs font-bold text-orange-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-lg">
                            {{ $transfer->reference }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-900 dark:text-white text-xs">
                            {{ $transfer->inventoryUnit?->phoneModel?->brand?->name }}
                            {{ $transfer->inventoryUnit?->phoneModel?->name }}
                        </div>
                        <div class="font-mono text-[10px] text-gray-400 mt-0.5">{{ $transfer->inventoryUnit?->imei_1 ?? '—' }}</div>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                            <span class="max-w-[90px] truncate">{{ $transfer->from?->name ?? 'HQ' }}</span>
                            <span class="text-[10px] text-gray-400 px-1 py-0.5 bg-gray-100 dark:bg-zinc-800 rounded">{{ $fromType }}</span>
                            <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <span class="max-w-[90px] truncate">{{ $transfer->to?->name ?? 'HQ' }}</span>
                            <span class="text-[10px] text-gray-400 px-1 py-0.5 bg-gray-100 dark:bg-zinc-800 rounded">{{ $toType }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusBadge['color'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                            {{ str_replace('_', ' ', ucfirst($transfer->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 hidden lg:table-cell">
                        {{ $transfer->shipped_at?->format('d M Y') ?? $transfer->created_at->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                        {{ $transfer->transferredBy?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button wire:click="openDetail('{{ $transfer->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-50 text-orange-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Details
                            </button>
                            @can('devices.edit')
                            @if($transfer->status === 'in_transit')
                            <button wire:click="markDelivered('{{ $transfer->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Delivered
                            </button>
                            <button wire:click="cancelTransfer('{{ $transfer->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 transition-colors">
                                Cancel
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-16 text-center">
                        <flux:icon name="arrows-right-left" class="size-12 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                        <p class="text-gray-500 font-medium">No transfers found</p>
                        <p class="text-gray-400 text-xs mt-1">
                            @if($search || $statusFilter)
                                Try clearing your filters
                            @else
                                Initiate your first stock transfer above
                            @endif
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($transfers->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $transfers->links() }}</div>
        @endif
    </div>

    {{-- ══ TRANSFER DETAIL SLIDE-OVER ══ --}}
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
            @if($detailTransfer)
            @php
                $dt = $detailTransfer;
                $dtStatus = match($dt->status) {
                    'pending'    => ['bg' => 'bg-amber-100 text-amber-700',   'dot' => 'bg-amber-400'],
                    'in_transit' => ['bg' => 'bg-blue-100 text-orange-600',     'dot' => 'bg-blue-400'],
                    'delivered'  => ['bg' => 'bg-emerald-100 text-emerald-700','dot'=> 'bg-emerald-400'],
                    'cancelled'  => ['bg' => 'bg-red-100 text-red-700',       'dot' => 'bg-red-400'],
                    default      => ['bg' => 'bg-zinc-100 text-zinc-600',     'dot' => 'bg-zinc-400'],
                };
                $dtFromType = class_basename($dt->from_type ?? '');
                $dtToType   = class_basename($dt->to_type ?? '');
            @endphp

            {{-- Gradient Header --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                <div>
                    <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">Transfer Record</p>
                    <h2 class="text-xl font-black font-mono mt-0.5">{{ $dt->reference }}</h2>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="w-2 h-2 rounded-full {{ $dtStatus['dot'] }}"></span>
                        <span class="text-xs font-semibold text-white/90">{{ str_replace('_', ' ', ucfirst($dt->status)) }}</span>
                    </div>
                </div>
                <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Device Info --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-orange-500 flex items-center justify-center flex-shrink-0">
                            <flux:icon name="device-phone-mobile" class="size-5 text-white" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $dt->inventoryUnit?->phoneModel?->brand?->name }}
                                {{ $dt->inventoryUnit?->phoneModel?->name ?? 'Unknown Device' }}
                            </p>
                            <p class="font-mono text-xs text-gray-400 mt-0.5">IMEI: {{ $dt->inventoryUnit?->imei_1 ?? '—' }}</p>
                            @if($dt->inventoryUnit?->imei_2)
                            <p class="font-mono text-xs text-gray-400">IMEI 2: {{ $dt->inventoryUnit->imei_2 }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Route --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Transfer Route</h3>
                    <div class="grid grid-cols-2 gap-3 items-center">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold mb-1">From</p>
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $dt->from?->name ?? 'HQ' }}</p>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-orange-500 font-semibold">{{ $dtFromType }}</span>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
                            <p class="text-[10px] text-blue-400 uppercase tracking-wider font-semibold mb-1">To</p>
                            <p class="font-semibold text-blue-900 dark:text-blue-100 text-sm">{{ $dt->to?->name ?? 'HQ' }}</p>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-white text-orange-500 font-semibold">{{ $dtToType }}</span>
                        </div>
                    </div>
                </div>

                {{-- Dates --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Timeline</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Initiated</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $dt->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Shipped At</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $dt->shipped_at?->format('d M Y, H:i') ?? '—' }}</p>
                        </div>
                        <div class="bg-{{ $dt->received_at ? 'emerald' : 'gray' }}-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Received At</p>
                            <p class="text-sm font-semibold {{ $dt->received_at ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-400' }} mt-1">
                                {{ $dt->received_at?->format('d M Y, H:i') ?? 'Pending' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Initiated By</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $dt->transferredBy?->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                @if($dt->notes)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Notes</h3>
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-xl p-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $dt->notes }}</p>
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex gap-2">
                @can('devices.edit')
                @if($dt->status === 'in_transit')
                <button wire:click="markDelivered('{{ $dt->id }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Mark Delivered
                </button>
                <button wire:click="cancelTransfer('{{ $dt->id }}')"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-colors">
                    Cancel
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

    {{-- ══ CREATE TRANSFER MODAL ══ --}}
    <flux:modal wire:model="showCreateModal" name="create-transfer">
        <flux:heading size="lg">New Stock Transfer</flux:heading>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Move a device unit to a vendor or branch.</p>
        <flux:separator class="my-4" />

        <div class="space-y-4">
            <flux:field>
                <flux:label>Search Device (IMEI or Model)</flux:label>
                <flux:input wire:model.live.debounce.300ms="transferUnitSearch" icon="magnifying-glass" placeholder="IMEI or model name…" />
                @if($availableUnits->isNotEmpty())
                <div class="mt-1 border border-gray-200 dark:border-zinc-700 rounded-xl max-h-44 overflow-y-auto bg-white dark:bg-zinc-900 shadow-sm">
                    @foreach($availableUnits as $u)
                    <button type="button" wire:click="$set('transferUnitId', '{{ $u->id }}')"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800 transition-colors border-b border-gray-50 dark:border-zinc-800 last:border-0 {{ $transferUnitId === $u->id ? 'bg-blue-50 font-semibold text-orange-600' : '' }}">
                        <span class="font-semibold">{{ $u->phoneModel?->brand?->name }} {{ $u->phoneModel?->name }}</span>
                        <span class="font-mono text-xs text-gray-400 ml-2">{{ $u->imei_1 }}</span>
                        <span class="text-[10px] ml-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded">{{ str_replace('_', ' ', $u->status) }}</span>
                    </button>
                    @endforeach
                </div>
                @endif
                @if($transferUnitId)
                @php $selUnit = $availableUnits->firstWhere('id', $transferUnitId); @endphp
                <div class="mt-1 flex items-center gap-2 text-sm text-orange-500 dark:text-blue-400 font-semibold">
                    <flux:icon name="check-circle" class="size-4" />
                    {{ $selUnit?->phoneModel?->name }} — {{ $selUnit?->imei_1 }}
                </div>
                @endif
                <flux:error name="transferUnitId" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Transfer To</flux:label>
                    <flux:select wire:model.live="transferToType">
                        <flux:select.option value="vendor">Vendor</flux:select.option>
                        <flux:select.option value="branch">Branch</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>{{ $transferToType === 'vendor' ? 'Select Vendor' : 'Select Branch' }}</flux:label>
                    <flux:select wire:model="transferToId">
                        <flux:select.option value="">— Select —</flux:select.option>
                        @if($transferToType === 'vendor')
                            @foreach($vendors as $v)
                                <flux:select.option :value="$v->id">{{ $v->name }}</flux:select.option>
                            @endforeach
                        @else
                            @foreach($branches as $b)
                                <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                            @endforeach
                        @endif
                    </flux:select>
                    <flux:error name="transferToId" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Notes <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                <flux:textarea wire:model="transferNotes" rows="2" placeholder="Remarks…" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-3 mt-5">
            <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="createTransfer" wire:loading.attr="disabled">
                <flux:icon wire:loading wire:target="createTransfer" name="arrow-path" class="size-4 animate-spin mr-1" />
                <span wire:loading.remove wire:target="createTransfer">Initiate Transfer</span>
                <span wire:loading wire:target="createTransfer">Processing…</span>
            </flux:button>
        </div>
    </flux:modal>

</div>
