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
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Brands &amp; Models</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Manage device catalog</p>
        </div>
        <div class="flex gap-2">
            @can('products.create')
            <flux:button wire:click="$set('showCreateBrand', true)" variant="ghost" size="sm" icon="plus">Brand</flux:button>
            <flux:button wire:click="$set('showCreateModel', true)" variant="primary" size="sm" icon="plus">Model</flux:button>
            @endcan
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Total Brands — gradient hero card --}}
        <div class="bg-gradient-to-br from-[#2563eb] to-[#2563eb] rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-blue-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-white/20">
                    <flux:icon name="building-storefront" class="size-5" />
                </div>
                <span class="text-xs font-semibold text-blue-200 uppercase tracking-wider">Total Brands</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['total_brands']) }}</p>
            <p class="text-xs text-blue-300 mt-1">Registered manufacturers</p>
        </div>

        {{-- Total Models --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="squares-2x2" class="size-5 text-orange-500 dark:text-blue-400" />
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Models</span>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['total_models']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Across all brands</p>
        </div>

        {{-- Active Models --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-teal-100 dark:bg-teal-900/30">
                    <flux:icon name="check-circle" class="size-5 text-teal-600 dark:text-teal-400" />
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Models</span>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['active_models']) }}</p>
            <p class="text-xs text-teal-500 mt-1 font-semibold">Available for lending</p>
        </div>

        {{-- Total Units --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-sky-100 dark:bg-sky-900/30">
                    <flux:icon name="device-phone-mobile" class="size-5 text-sky-600 dark:text-sky-400" />
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Units</span>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['total_units']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Physical inventory</p>
        </div>

    </div>

    {{-- Tab + Search row --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex gap-1 rounded-xl bg-zinc-100 dark:bg-zinc-800 p-1 w-fit">
            <button wire:click="$set('tab', 'brands')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                    {{ $tab === 'brands' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">
                Brands
            </button>
            <button wire:click="$set('tab', 'models')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                    {{ $tab === 'models' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">
                Models
            </button>
        </div>
        <div class="flex-1 max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search {{ $tab }}…" icon="magnifying-glass" />
        </div>
    </div>

    {{-- ── BRANDS TABLE ── --}}
    @if($tab === 'brands')
    <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/80 border-b border-zinc-100 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Brand</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Models</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden md:table-cell">Total Units</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden lg:table-cell">Created</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-50 dark:divide-zinc-700/50">
                @forelse($brands as $brand)
                <tr wire:key="brand-{{ $brand->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white font-black text-sm flex-shrink-0">
                                {{ strtoupper(substr($brand->name, 0, 2)) }}
                            </div>
                            <span class="font-semibold text-zinc-900 dark:text-white">{{ $brand->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <flux:badge color="purple" size="sm">{{ $brand->phone_models_count }} {{ Str::plural('model', $brand->phone_models_count) }}</flux:badge>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <flux:badge color="blue" size="sm">{{ number_format($brand->stock_count) }} units</flux:badge>
                    </td>
                    <td class="px-4 py-3 text-zinc-400 text-xs hidden lg:table-cell">{{ $brand->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button wire:click="openBrandDetail('{{ $brand->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-50 text-orange-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Details
                            </button>
                            @can('products.edit')
                            <button wire:click="openEditBrand('{{ $brand->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-16 text-center">
                        <flux:icon name="building-storefront" class="size-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                        <p class="text-zinc-500 font-medium">No brands yet</p>
                        <p class="text-zinc-400 text-xs mt-1">Add your first brand above</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($brands->hasPages())
            <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">{{ $brands->links() }}</div>
        @endif
    </div>

    {{-- ── MODELS TABLE ── --}}
    @else
    <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/80 border-b border-zinc-100 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden md:table-cell">Brand</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Retail / Cost</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden lg:table-cell">Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-50 dark:divide-zinc-700/50">
                @forelse($models as $model)
                @php
                    $margin = ($model->retail_price > 0 && $model->cost_price > 0)
                        ? round(((float)$model->retail_price - (float)$model->cost_price) / (float)$model->retail_price * 100, 1)
                        : null;
                @endphp
                <tr wire:key="model-{{ $model->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $model->name }}</div>
                        @php $specs = $model->specifications ?? []; @endphp
                        @if(!empty($specs))
                        <div class="text-[10px] text-zinc-400 mt-0.5">
                            {{ collect($specs)->map(fn($v,$k) => $v)->implode(' · ') }}
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300 hidden md:table-cell">{{ $model->brand?->name }}</td>
                    <td class="px-4 py-3">
                        <div class="text-xs font-semibold text-zinc-800 dark:text-zinc-100">
                            TZS {{ number_format((float)$model->retail_price) }}
                        </div>
                        <div class="text-[10px] text-zinc-400">
                            Cost: TZS {{ number_format((float)$model->cost_price) }}
                            @if($margin !== null)
                            · <span class="text-teal-600">{{ $margin }}%</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="px-1.5 py-0.5 rounded-md bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-300 font-semibold">{{ $model->stock_available }} avail</span>
                            <span class="px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 font-semibold">{{ $model->stock_sold }} sold</span>
                            <span class="text-zinc-400">/ {{ $model->stock_total }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <flux:badge :color="$model->is_active ? 'green' : 'zinc'" size="sm">
                            {{ $model->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button wire:click="openModelDetail('{{ $model->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-50 text-orange-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Details
                            </button>
                            @can('products.edit')
                            <button wire:click="openEditModel('{{ $model->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </button>
                            <button wire:click="toggleModelActive('{{ $model->id }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg {{ $model->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400' : 'bg-teal-50 text-teal-700 hover:bg-teal-100 dark:bg-teal-900/20 dark:text-teal-400' }} transition-colors">
                                {{ $model->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-16 text-center">
                        <flux:icon name="device-phone-mobile" class="size-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                        <p class="text-zinc-500 font-medium">No models yet</p>
                        <p class="text-zinc-400 text-xs mt-1">Add your first model above</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($models->hasPages())
            <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">{{ $models->links() }}</div>
        @endif
    </div>
    @endif

    {{-- ══ BRAND DETAIL SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showBrandDetail') }"
         x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeBrandDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-lg bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">
            @if($detailBrand)
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-blue-700 to-orange-600 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white font-black text-lg">
                        {{ strtoupper(substr($detailBrand->name, 0, 2)) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">{{ $detailBrand->name }}</h2>
                        <p class="text-xs text-white/70 mt-0.5">{{ $detailBrand->phone_models_count }} {{ Str::plural('model', $detailBrand->phone_models_count) }} · Added {{ $detailBrand->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <button wire:click="closeBrandDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 px-6 py-5 space-y-5">
                <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Models Under This Brand</h3>
                @forelse($detailBrand->phoneModels as $pm)
                @php
                    $pm_margin = ($pm->retail_price > 0 && $pm->cost_price > 0)
                        ? round(((float)$pm->retail_price - (float)$pm->cost_price) / (float)$pm->retail_price * 100, 1) : null;
                @endphp
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-zinc-900 dark:text-white text-sm">{{ $pm->name }}</p>
                            <p class="text-xs text-zinc-400 mt-0.5">
                                Retail: TZS {{ number_format((float)$pm->retail_price) }}
                                @if($pm_margin !== null)· <span class="text-teal-600">{{ $pm_margin }}% margin</span>@endif
                            </p>
                        </div>
                        <flux:badge :color="$pm->is_active ? 'green' : 'zinc'" size="sm">{{ $pm->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="bg-white dark:bg-zinc-700 rounded-lg py-2">
                            <p class="font-bold text-teal-600">{{ $pm->stock_available }}</p>
                            <p class="text-zinc-400 text-[10px]">Available</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-700 rounded-lg py-2">
                            <p class="font-bold text-emerald-600">{{ $pm->stock_sold }}</p>
                            <p class="text-zinc-400 text-[10px]">Sold</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-700 rounded-lg py-2">
                            <p class="font-bold text-zinc-700 dark:text-zinc-200">{{ $pm->stock_total }}</p>
                            <p class="text-zinc-400 text-[10px]">Total</p>
                        </div>
                    </div>
                    <button wire:click="openModelDetail('{{ $pm->id }}')"
                            class="w-full text-xs font-semibold text-orange-500 dark:text-blue-400 hover:underline text-left">
                        View full details →
                    </button>
                </div>
                @empty
                <p class="text-zinc-400 text-sm text-center py-6">No models for this brand yet.</p>
                @endforelse
            </div>
            <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800 flex gap-2">
                @can('products.edit')
                <button wire:click="openEditBrand('{{ $detailBrand->id }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-orange-500 hover:bg-orange-600 text-white transition-colors">
                    Edit Brand
                </button>
                @endcan
                <button wire:click="closeBrandDetail"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- ══ MODEL DETAIL SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showModelDetail') }"
         x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeModelDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-lg bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">
            @if($detailModel)
            @php
                $dm     = $detailModel;
                $dspecs = $dm->specifications ?? [];
                $dm_margin = ($dm->retail_price > 0 && $dm->cost_price > 0)
                    ? round(((float)$dm->retail_price - (float)$dm->cost_price) / (float)$dm->retail_price * 100, 1) : null;
            @endphp
            <div class="flex items-start justify-between px-6 py-5 bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                <div>
                    <p class="text-xs font-semibold text-white/70 uppercase tracking-wider">{{ $dm->brand?->name }}</p>
                    <h2 class="text-xl font-bold mt-0.5">{{ $dm->name }}</h2>
                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $dm->is_active ? 'bg-green-100 text-green-700' : 'bg-zinc-100 text-zinc-600' }}">
                            {{ $dm->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <button wire:click="closeModelDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 px-6 py-5 space-y-6">

                {{-- Pricing --}}
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Pricing</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Retail Price</p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mt-0.5">TZS {{ number_format((float)$dm->retail_price) }}</p>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Cost Price</p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mt-0.5">TZS {{ number_format((float)$dm->cost_price) }}</p>
                        </div>
                        <div class="bg-{{ $dm_margin > 0 ? 'teal' : 'zinc' }}-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Margin</p>
                            <p class="text-sm font-bold {{ $dm_margin > 0 ? 'text-teal-600' : 'text-zinc-500' }} mt-0.5">
                                {{ $dm_margin !== null ? $dm_margin.'%' : '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Specifications --}}
                @if(!empty($dspecs))
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Specifications</h3>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($dspecs as $specK => $specV)
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-blue-400 uppercase tracking-wider">{{ ucfirst($specK) }}</p>
                            <p class="text-sm font-bold text-orange-600 dark:text-blue-300 mt-0.5">{{ $specV }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Stock Breakdown --}}
                <div>
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Stock Breakdown</h3>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach([
                            ['Available (HQ)', $dm->stock_available,  'teal'],
                            ['Vendor Stock',   $dm->stock_vendor,     'blue'],
                            ['In Transit',     $dm->stock_in_transit, 'amber'],
                            ['Sold',           $dm->stock_sold,       'green'],
                            ['Returned',       $dm->stock_returned,   'red'],
                            ['Total',          $dm->stock_total,      'zinc'],
                        ] as [$label, $count, $col])
                        <div class="bg-{{ $col }}-50 dark:bg-{{ $col }}-900/20 rounded-xl p-3 text-center">
                            <p class="text-[10px] text-{{ $col }}-500 uppercase tracking-wider">{{ $label }}</p>
                            <p class="text-xl font-black text-{{ $col }}-700 dark:text-{{ $col }}-300 mt-0.5">{{ $count }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800 flex gap-2">
                @can('products.edit')
                <button wire:click="openEditModel('{{ $dm->id }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl bg-orange-500 hover:bg-orange-600 text-white transition-colors">
                    Edit Model
                </button>
                <button wire:click="toggleModelActive('{{ $dm->id }}')"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl {{ $dm->is_active ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-teal-50 text-teal-700 border border-teal-200' }} transition-colors">
                    {{ $dm->is_active ? 'Deactivate' : 'Activate' }}
                </button>
                @endcan
                <button wire:click="closeModelDetail"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- ══ CREATE BRAND MODAL ══ --}}
    <flux:modal wire:model="showCreateBrand" name="create-brand">
        <flux:heading size="lg">Add New Brand</flux:heading>
        <flux:separator class="my-4" />
        <flux:field>
            <flux:label>Brand Name</flux:label>
            <flux:input wire:model="brandName" placeholder="e.g. Samsung, Apple…" autofocus />
            <flux:error name="brandName" />
        </flux:field>
        <div class="flex justify-end gap-2 mt-4">
            <flux:button wire:click="$set('showCreateBrand', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="createBrand" variant="primary">
                Save Brand
                <flux:icon wire:loading wire:target="createBrand" name="arrow-path" class="size-4 animate-spin ml-1" />
            </flux:button>
        </div>
    </flux:modal>

    {{-- ══ EDIT BRAND MODAL ══ --}}
    <flux:modal wire:model="showEditBrand" name="edit-brand">
        <flux:heading size="lg">Edit Brand</flux:heading>
        <flux:separator class="my-4" />
        <flux:field>
            <flux:label>Brand Name</flux:label>
            <flux:input wire:model="editBrandName" placeholder="Brand name…" />
            <flux:error name="editBrandName" />
        </flux:field>
        <div class="flex justify-end gap-2 mt-4">
            <flux:button wire:click="$set('showEditBrand', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="updateBrand" variant="primary">
                Update Brand
                <flux:icon wire:loading wire:target="updateBrand" name="arrow-path" class="size-4 animate-spin ml-1" />
            </flux:button>
        </div>
    </flux:modal>

    {{-- ══ CREATE MODEL MODAL ══ --}}
    <flux:modal wire:model="showCreateModel" name="create-model">
        <flux:heading size="lg">Add New Model</flux:heading>
        <flux:separator class="my-4" />
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Brand</flux:label>
                    <flux:select wire:model="selectedBrandId">
                        <flux:select.option value="">Select Brand…</flux:select.option>
                        @foreach($allBrands as $b)
                            <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="selectedBrandId" />
                </flux:field>
                <flux:field>
                    <flux:label>Model Name</flux:label>
                    <flux:input wire:model="modelName" placeholder="e.g. Galaxy A55…" />
                    <flux:error name="modelName" />
                </flux:field>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Retail Price (TZS)</flux:label>
                    <flux:input wire:model="retailPrice" type="number" placeholder="0" min="0" />
                    <flux:error name="retailPrice" />
                </flux:field>
                <flux:field>
                    <flux:label>Cost Price (TZS)</flux:label>
                    <flux:input wire:model="costPrice" type="number" placeholder="0" min="0" />
                    <flux:error name="costPrice" />
                </flux:field>
            </div>
            <p class="text-xs font-bold text-zinc-500 uppercase tracking-wider pt-2">Specifications <span class="text-zinc-400 normal-case font-normal">(optional)</span></p>
            <div class="grid grid-cols-3 gap-3">
                <flux:field><flux:label>RAM</flux:label><flux:input wire:model="specRam" placeholder="e.g. 6GB" /></flux:field>
                <flux:field><flux:label>Storage</flux:label><flux:input wire:model="specStorage" placeholder="e.g. 128GB" /></flux:field>
                <flux:field><flux:label>Color</flux:label><flux:input wire:model="specColor" placeholder="e.g. Black" /></flux:field>
                <flux:field><flux:label>Display</flux:label><flux:input wire:model="specDisplay" placeholder="e.g. 6.5 inch" /></flux:field>
                <flux:field><flux:label>Battery</flux:label><flux:input wire:model="specBattery" placeholder="e.g. 5000mAh" /></flux:field>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <flux:button wire:click="$set('showCreateModel', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="createModel" variant="primary">
                Save Model
                <flux:icon wire:loading wire:target="createModel" name="arrow-path" class="size-4 animate-spin ml-1" />
            </flux:button>
        </div>
    </flux:modal>

    {{-- ══ EDIT MODEL MODAL ══ --}}
    <flux:modal wire:model="showEditModel" name="edit-model">
        <flux:heading size="lg">Edit Model</flux:heading>
        <flux:separator class="my-4" />
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Brand</flux:label>
                    <flux:select wire:model="editSelectedBrandId">
                        <flux:select.option value="">Select Brand…</flux:select.option>
                        @foreach($allBrands as $b)
                            <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="editSelectedBrandId" />
                </flux:field>
                <flux:field>
                    <flux:label>Model Name</flux:label>
                    <flux:input wire:model="editModelName" placeholder="Model name…" />
                    <flux:error name="editModelName" />
                </flux:field>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Retail Price (TZS)</flux:label>
                    <flux:input wire:model="editRetailPrice" type="number" placeholder="0" min="0" />
                    <flux:error name="editRetailPrice" />
                </flux:field>
                <flux:field>
                    <flux:label>Cost Price (TZS)</flux:label>
                    <flux:input wire:model="editCostPrice" type="number" placeholder="0" min="0" />
                    <flux:error name="editCostPrice" />
                </flux:field>
            </div>
            <p class="text-xs font-bold text-zinc-500 uppercase tracking-wider pt-2">Specifications <span class="text-zinc-400 normal-case font-normal">(optional)</span></p>
            <div class="grid grid-cols-3 gap-3">
                <flux:field><flux:label>RAM</flux:label><flux:input wire:model="editSpecRam" placeholder="e.g. 6GB" /></flux:field>
                <flux:field><flux:label>Storage</flux:label><flux:input wire:model="editSpecStorage" placeholder="e.g. 128GB" /></flux:field>
                <flux:field><flux:label>Color</flux:label><flux:input wire:model="editSpecColor" placeholder="e.g. Black" /></flux:field>
                <flux:field><flux:label>Display</flux:label><flux:input wire:model="editSpecDisplay" placeholder="e.g. 6.5 inch" /></flux:field>
                <flux:field><flux:label>Battery</flux:label><flux:input wire:model="editSpecBattery" placeholder="e.g. 5000mAh" /></flux:field>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <flux:button wire:click="$set('showEditModel', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="updateModel" variant="primary">
                Update Model
                <flux:icon wire:loading wire:target="updateModel" name="arrow-path" class="size-4 animate-spin ml-1" />
            </flux:button>
        </div>
    </flux:modal>

</div>
