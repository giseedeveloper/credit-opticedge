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
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Master Inventory</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full hardware registry — all units across HQ, vendors and branches.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('stock.imei') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="magnifying-glass" class="size-4" />
                IMEI Lookup
            </a>
            @can('devices.create')
            <flux:button variant="primary" wire:click="openReceiveModal" icon="plus">Receive Stock</flux:button>
            @endcan
        </div>
    </div>

    {{-- Stats Bar --}}
    @php
    $statDefs = [
        ['key'=>'hq_stock',     'label'=>'HQ Stock',     'grad'=>'from-[#4b0082] to-[#7c3aed]', 'hero'=>true],
        ['key'=>'vendor_stock', 'label'=>'Vendor Stock', 'icon_color'=>'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'],
        ['key'=>'in_transit',   'label'=>'In Transit',   'icon_color'=>'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'],
        ['key'=>'sold',         'label'=>'Sold/Loaned',  'icon_color'=>'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'],
        ['key'=>'returned',     'label'=>'Returned',     'icon_color'=>'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400'],
    ];
    $total = array_sum($statCounts);
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach($statDefs as $sd)
        @if(!empty($sd['hero']))
        <div class="bg-gradient-to-br {{ $sd['grad'] }} rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-purple-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-white/20">
                    <flux:icon name="building-office" class="size-5" />
                </div>
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">{{ $sd['label'] }}</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($statCounts[$sd['key']] ?? 0) }}</p>
            <p class="text-xs text-white/60 mt-1">of {{ number_format($total) }} total</p>
        </div>
        @else
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg {{ $sd['icon_color'] }}">
                    <flux:icon name="device-phone-mobile" class="size-5" />
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $sd['label'] }}</span>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($statCounts[$sd['key']] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">units</p>
        </div>
        @endif
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Scan or type IMEI, serial, model…" />
        </div>
        <flux:select wire:model.live="brandFilter" class="w-44">
            <flux:select.option value="">All Brands</flux:select.option>
            @foreach($brands as $b)
            <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" class="w-44">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="hq_stock">HQ Stock</flux:select.option>
            <flux:select.option value="vendor_stock">Vendor Stock</flux:select.option>
            <flux:select.option value="available">Available</flux:select.option>
            <flux:select.option value="sold">Sold / On Loan</flux:select.option>
            <flux:select.option value="in_transit">In Transit</flux:select.option>
            <flux:select.option value="returned">Returned</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identity</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status / Location</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Pricing</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Received</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                    @forelse($units as $unit)
                    @php
                        $retail = (float)($unit->phoneModel?->retail_price ?? 0);
                        $cost   = (float)($unit->purchase_price ?? 0);
                        $margin = ($retail > 0 && $cost > 0) ? round(($retail - $cost) / $retail * 100, 1) : null;
                        $statusBadge = match($unit->status) {
                            'hq_stock'     => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                            'vendor_stock' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                            'available'    => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
                            'sold'         => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                            'in_transit'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                            'returned'     => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                            default        => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                        };
                        $location = match($unit->status) {
                            'vendor_stock' => $unit->vendor?->name ?? 'Vendor',
                            'hq_stock'     => 'HQ',
                            'sold'         => $unit->loan?->customer ? trim(($unit->loan->customer->first_name ?? '').' '.($unit->loan->customer->last_name ?? '')) : 'On Loan',
                            default        => $unit->branch?->name ?? '—',
                        };
                    @endphp
                    <tr wire:key="unit-{{ $unit->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0 text-white text-xs font-black">
                                    {{ strtoupper(substr($unit->phoneModel?->brand?->name ?? 'U', 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-xs">
                                        {{ $unit->phoneModel?->brand?->name }} {{ $unit->phoneModel?->name ?? 'Unknown' }}
                                    </p>
                                    @php $specs = $unit->phoneModel?->specifications ?? []; @endphp
                                    @if(!empty($specs))
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        {{ implode(' · ', array_filter([$specs['ram'] ?? null, $specs['storage'] ?? null, $specs['color'] ?? null])) }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-100">{{ $unit->imei_1 }}</p>
                            @if($unit->imei_2)
                            <p class="font-mono text-[10px] text-gray-400">{{ $unit->imei_2 }}</p>
                            @endif
                            @if($unit->serial_number)
                            <p class="text-[10px] text-gray-400">SN: {{ $unit->serial_number }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusBadge }}">
                                {{ str_replace('_', ' ', ucwords($unit->status)) }}
                            </span>
                            <p class="text-[10px] text-gray-400 mt-1 truncate max-w-[120px]">{{ $location }}</p>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            <p class="text-xs font-bold text-gray-800 dark:text-gray-100">TZS {{ number_format($cost) }}</p>
                            @if($retail > 0)
                            <p class="text-[10px] text-gray-400">Retail: {{ number_format($retail) }}</p>
                            @endif
                            @if($margin !== null)
                            <p class="text-[10px] font-semibold {{ $margin > 0 ? 'text-teal-600' : 'text-gray-400' }}">{{ $margin }}% margin</p>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-xs text-gray-400 hidden lg:table-cell">
                            {{ $unit->received_at?->format('d M Y') ?? $unit->created_at->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="openDetail('{{ $unit->id }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-300 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Details
                                </button>
                                @can('devices.edit')
                                <button wire:click="openEditModal('{{ $unit->id }}')"
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 dark:bg-zinc-800 dark:text-gray-300 transition-colors">
                                    <flux:icon name="pencil-square" class="size-3.5" />
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <flux:icon name="cube-transparent" class="size-12 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                            <p class="text-gray-500 font-medium">No inventory found</p>
                            <p class="text-gray-400 text-xs mt-1">
                                @if($search || $statusFilter || $brandFilter)
                                    Try clearing your filters
                                @else
                                    Receive your first stock unit above
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 dark:border-zinc-800">
            {{ $units->links() }}
        </div>
    </div>

    {{-- ══ UNIT DETAIL SLIDE-OVER ══ --}}
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
            @if($detailUnit)
            @php
                $du = $detailUnit;
                $duSpecs  = $du->phoneModel?->specifications ?? [];
                $duRetail = (float)($du->phoneModel?->retail_price ?? 0);
                $duCost   = (float)($du->purchase_price ?? 0);
                $duMargin = ($duRetail > 0 && $duCost > 0) ? round(($duRetail - $duCost) / $duRetail * 100, 1) : null;
                $duSc = match($du->status) {
                    'hq_stock'     => 'bg-purple-100 text-purple-700',
                    'vendor_stock' => 'bg-blue-100 text-blue-700',
                    'available'    => 'bg-teal-100 text-teal-700',
                    'sold'         => 'bg-emerald-100 text-emerald-700',
                    'in_transit'   => 'bg-amber-100 text-amber-700',
                    'returned'     => 'bg-rose-100 text-rose-700',
                    default        => 'bg-zinc-100 text-zinc-600',
                };
            @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-700 text-white">
                <div>
                    <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">{{ $du->phoneModel?->brand?->name }}</p>
                    <h2 class="text-xl font-black mt-0.5">{{ $du->phoneModel?->name ?? 'Unknown Device' }}</h2>
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold mt-2 {{ $duSc }}">
                        {{ str_replace('_', ' ', ucwords($du->status)) }}
                    </span>
                </div>
                <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Identity --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Device Identity</h3>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach([['IMEI 1', $du->imei_1], ['IMEI 2', $du->imei_2], ['Serial', $du->serial_number]] as [$lbl, $val])
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $lbl }}</p>
                            <p class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-100 mt-1 truncate">{{ $val ?? '—' }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Pricing --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Pricing</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Cost</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100 mt-1">{{ number_format($duCost) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Retail</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100 mt-1">{{ $duRetail > 0 ? number_format($duRetail) : '—' }}</p>
                        </div>
                        <div class="bg-{{ $duMargin > 0 ? 'teal' : 'gray' }}-50 dark:bg-zinc-800 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Margin</p>
                            <p class="text-sm font-bold {{ $duMargin > 0 ? 'text-teal-600' : 'text-gray-400' }} mt-1">{{ $duMargin !== null ? $duMargin.'%' : '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Specs --}}
                @if(!empty($duSpecs))
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Specifications</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($duSpecs as $sk => $sv)
                        <span class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold">
                            {{ ucfirst($sk) }}: {{ $sv }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Location --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Location & Assignment</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Branch</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $du->branch?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Vendor</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $du->vendor?->name ?? 'HQ Direct' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Received</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $du->received_at?->format('d M Y') ?? $du->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Added By</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $du->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Loan card --}}
                @if($du->loan)
                @php
                    $dl = $du->loan;
                    $dlc = match($dl->status) {
                        'active'    => 'bg-emerald-100 text-emerald-700',
                        'completed' => 'bg-sky-100 text-sky-700',
                        'defaulted' => 'bg-red-100 text-red-700',
                        'overdue'   => 'bg-amber-100 text-amber-700',
                        default     => 'bg-zinc-100 text-zinc-600',
                    };
                @endphp
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Linked Loan</h3>
                    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-900/40 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-mono font-bold text-indigo-800 dark:text-indigo-300 text-sm">{{ $dl->loan_number }}</p>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $dlc }}">{{ ucfirst($dl->status) }}</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ trim(($dl->customer?->first_name ?? '').' '.($dl->customer?->last_name ?? '')) ?: '—' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Principal: TZS {{ number_format($dl->principal_amount) }}</p>
                        <p class="text-xs text-red-600 font-semibold">Balance: TZS {{ number_format($dl->outstanding_balance ?? 0) }}</p>
                    </div>
                </div>
                @endif

                {{-- Transfer history --}}
                @if($du->stockTransfers?->count())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Transfer History</h3>
                    <div class="space-y-1.5">
                        @foreach($du->stockTransfers as $tr)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800 rounded-xl px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0"></div>
                                <span class="text-xs font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $tr->reference }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white dark:bg-zinc-700 text-gray-600 dark:text-gray-300">{{ ucfirst($tr->status) }}</span>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $tr->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex gap-2">
                @can('devices.edit')
                <button wire:click="openEditModal('{{ $du->id }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                    <flux:icon name="pencil-square" class="size-4" />
                    Edit Unit
                </button>
                @endcan
                <a href="{{ route('stock.imei') }}?query={{ $du->imei_1 }}" wire:navigate
                   class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    IMEI Lookup
                </a>
                <button wire:click="closeDetail"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- ══ EDIT UNIT MODAL ══ --}}
    <flux:modal wire:model="showEditModal" name="edit-unit">
        <flux:heading size="lg">Edit Unit</flux:heading>
        <flux:separator class="my-4" />
        <div class="space-y-4">
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="editStatus">
                    @foreach(['available','hq_stock','vendor_stock','in_transit','sold','returned','lost'] as $st)
                    <flux:select.option :value="$st">{{ str_replace('_', ' ', ucwords($st)) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="editStatus" />
            </flux:field>
            <flux:field>
                <flux:label>Purchase Price (TZS)</flux:label>
                <flux:input wire:model="editPurchasePrice" type="number" min="0" />
                <flux:error name="editPurchasePrice" />
            </flux:field>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <flux:button wire:click="$set('showEditModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="updateUnit" variant="primary">
                <flux:icon wire:loading wire:target="updateUnit" name="arrow-path" class="size-4 animate-spin mr-1" />
                Save Changes
            </flux:button>
        </div>
    </flux:modal>

    {{-- ══ RECEIVE STOCK MODAL ══ --}}
    <flux:modal wire:model="showReceiveModal" name="receive-stock">
        <flux:heading size="lg">Receive New Stock</flux:heading>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Register an incoming device unit.</p>
        <flux:separator class="my-4" />

        <div class="space-y-4">
            <flux:field>
                <flux:label>Device Model</flux:label>
                <flux:select wire:model="newPhoneModelId">
                    <flux:select.option value="">— Select model —</flux:select.option>
                    @foreach($phoneModels as $pm)
                    <flux:select.option :value="$pm->id">{{ $pm->brand?->name }} {{ $pm->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="newPhoneModelId" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>IMEI 1</flux:label>
                    <flux:input wire:model="newImei1" placeholder="15-digit IMEI" maxlength="20" class="font-mono" />
                    <flux:error name="newImei1" />
                </flux:field>
                <flux:field>
                    <flux:label>IMEI 2 <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                    <flux:input wire:model="newImei2" placeholder="Dual SIM IMEI" maxlength="20" class="font-mono" />
                    <flux:error name="newImei2" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Serial Number <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                    <flux:input wire:model="newSerial" placeholder="S/N" class="font-mono" />
                </flux:field>
                <flux:field>
                    <flux:label>Purchase Price (TZS)</flux:label>
                    <flux:input wire:model="newPurchasePrice" type="number" min="0" placeholder="e.g. 300000" />
                    <flux:error name="newPurchasePrice" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Vendor <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                    <flux:select wire:model="newVendorId">
                        <flux:select.option value="">HQ / Internal</flux:select.option>
                        @foreach($vendors as $v)
                        <flux:select.option :value="$v->id">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Initial Status</flux:label>
                    <flux:select wire:model="newStatus">
                        <flux:select.option value="hq_stock">HQ Stock</flux:select.option>
                        <flux:select.option value="vendor_stock">Vendor Stock</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-5">
            <flux:button variant="ghost" wire:click="$set('showReceiveModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="receiveStock" wire:loading.attr="disabled">
                <flux:icon wire:loading wire:target="receiveStock" name="arrow-path" class="size-4 animate-spin mr-1" />
                <span wire:loading.remove wire:target="receiveStock">Save Unit</span>
                <span wire:loading wire:target="receiveStock">Saving…</span>
            </flux:button>
        </div>
    </flux:modal>

</div>
