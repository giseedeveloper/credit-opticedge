<div>
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : type==='danger' ? 'bg-red-500' : 'bg-oe'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="closeCreateModal">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-700 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-900 z-10">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Add dealer</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Register a dealer counter for your network</p>
                </div>
                <button type="button" wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Dealer name *</label>
                    <input type="text" wire:model="formName" placeholder="e.g. Sinza Smart Devices"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Code *</label>
                    <input type="text" wire:model="formCode"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe font-mono" />
                    @error('formCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Status *</label>
                    <select wire:model="formStatus" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    @error('formStatus') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Phone</label>
                    <input type="text" wire:model="formPhone" placeholder="+255 …"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Email</label>
                    <input type="email" wire:model="formEmail"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Address</label>
                    <input type="text" wire:model="formAddress"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formAddress') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">TIN</label>
                    <input type="text" wire:model="formTin"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formTin') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Commission % *</label>
                    <input type="text" inputmode="decimal" wire:model="formCommission"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formCommission') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Owner account <span class="font-normal normal-case text-gray-400">(optional — user with the &quot;owner&quot; or &quot;dealer&quot; role)</span></label>
                    <select wire:model="formOwnerUserId" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe">
                        <option value="">No owner linked</option>
                        @foreach($ownerCandidates as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    @error('formOwnerUserId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/60 rounded-b-2xl flex justify-end gap-3">
                <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                <button type="button" wire:click="saveDealer" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-semibold bg-oe hover:bg-oe-hover disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm">Save dealer</button>
            </div>
        </div>
    </div>
    @endif

    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="closeEditModal">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-700 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-900 z-10">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Edit dealer</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Update counter details and owner mapping</p>
                </div>
                <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Dealer name *</label>
                    <input type="text" wire:model="formName" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Code *</label>
                    <input type="text" wire:model="formCode" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe font-mono" />
                    @error('formCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Status *</label>
                    <select wire:model="formStatus" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    @error('formStatus') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Phone</label>
                    <input type="text" wire:model="formPhone" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Email</label>
                    <input type="email" wire:model="formEmail" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Address</label>
                    <input type="text" wire:model="formAddress" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formAddress') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">TIN</label>
                    <input type="text" wire:model="formTin" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formTin') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Commission % *</label>
                    <input type="text" inputmode="decimal" wire:model="formCommission" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('formCommission') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Owner account <span class="font-normal normal-case text-gray-400">(users with &quot;owner&quot; or &quot;dealer&quot; role)</span></label>
                    <select wire:model="formOwnerUserId" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe">
                        <option value="">No owner linked</option>
                        @foreach($ownerCandidates as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    @error('formOwnerUserId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/60 rounded-b-2xl flex justify-end gap-3">
                <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                <button type="button" wire:click="updateDealer" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-semibold bg-oe hover:bg-oe-hover disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm">Update dealer</button>
            </div>
        </div>
    </div>
    @endif

    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="closeDeleteModal">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-700 w-full max-w-lg mx-4">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Delete dealer</h3>
                    <p class="text-xs text-gray-400 mt-0.5">This action will remove the dealer counter from the system.</p>
                </div>
                <button type="button" wire:click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-3">
                <div class="rounded-xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-900/10 p-4 text-sm text-red-700 dark:text-red-200">
                    Make sure this dealer has no linked staff, stock, customers, loans, or commission entries.
                </div>
                @error('deleteDealer') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/60 rounded-b-2xl flex justify-end gap-3">
                <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                <button type="button" wire:click="deleteDealer" wire:loading.attr="disabled"
                        class="px-5 py-2 text-sm font-semibold bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm">
                    Delete
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="building-office-2" size="lg" palette="sky" />
            <div>
                <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Dealers</h1>
                <p class="text-sm text-gray-400 mt-0.5">Dealer counters, commission rates, and staff assignment</p>
            </div>
        </div>
        @if(auth()->user()->canAccess('dealers.create'))
        <button type="button" wire:click="openCreateModal" class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold bg-oe hover:bg-oe-hover text-white rounded-xl shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add dealer
        </button>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 mb-5">
        <div class="rounded-2xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total dealers</p>
            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Active</p>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['active']) }}</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, code, phone…"
               class="flex-1 min-w-[200px] px-3.5 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
        <select wire:model.live="statusFilter" class="px-3.5 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe">
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div class="rounded-2xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-zinc-800/80 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">
                <tr>
                    <th class="px-6 py-3">Dealer</th>
                    <th class="px-6 py-3">Code</th>
                    <th class="px-6 py-3">Location</th>
                    <th class="px-6 py-3">Staff</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                @forelse($dealers as $d)
                <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/40">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $d->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $d->phone ?? '—' }} · {{ $d->email ?? '—' }}</p>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $d->code }}</td>
                    <td class="px-6 py-4 text-gray-700 dark:text-gray-200 max-w-[14rem] truncate" title="{{ $d->address ?? '' }}">@if($d->address){{ \Illuminate\Support\Str::limit($d->address, 42) }}@else{{ '—' }}@endif</td>
                    <td class="px-6 py-4">{{ number_format($d->staff_count) }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $d->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-gray-300' }}">
                            {{ ucfirst($d->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if(auth()->user()->canAccess('dealers.edit'))
                        <button type="button" wire:click="openEditModal('{{ $d->id }}')" class="text-oe hover:underline text-xs font-semibold">Edit</button>
                        @endif
                        @if(auth()->user()->canAccess('dealers.delete'))
                        <button type="button" wire:click="openDeleteModal('{{ $d->id }}')" class="ml-3 text-red-600 hover:underline text-xs font-semibold">Delete</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No dealers match your filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($dealers->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800">{{ $dealers->links() }}</div>
        @endif
    </div>
</div>
