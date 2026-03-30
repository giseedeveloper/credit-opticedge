<div>

    {{-- ── Toast ──────────────────────────────────────────────────── --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : type==='danger' ? 'bg-red-500' : 'bg-orange-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- ── Create Staff Modal ──────────────────────────────────────── --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showCreateModal',false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-700 w-full max-w-lg mx-4">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Add Staff Member</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Create a new system user and assign a role</p>
                </div>
                <button wire:click="$set('showCreateModal',false)" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Full Name</label>
                    <input wire:model="newName" type="text" placeholder="John Doe"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Email</label>
                    <input wire:model="newEmail" type="email" placeholder="john@opticedge.co"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    @error('newEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Phone</label>
                    <input wire:model="newPhone" type="text" placeholder="+255 7XX XXX XXX"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    @error('newPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Password</label>
                    <input wire:model="newPassword" type="password" placeholder="Min 8 characters"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    @error('newPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Role</label>
                    <select wire:model="newRole"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Select role…</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucwords(str_replace(['-','_'],' ',$role->name)) }}</option>
                        @endforeach
                    </select>
                    @error('newRole') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/60 rounded-b-2xl flex justify-end gap-3">
                <button wire:click="$set('showCreateModal',false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                <button wire:click="createStaff" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-semibold bg-orange-500 hover:bg-orange-600 disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm">Add Member</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Edit Staff Modal ────────────────────────────────────────── --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showEditModal',false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-700 w-full max-w-lg mx-4">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Edit Staff Member</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Update profile details and role</p>
                </div>
                <button wire:click="$set('showEditModal',false)" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Full Name</label>
                    <input wire:model="editName" type="text"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    @error('editName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Email</label>
                    <input wire:model="editEmail" type="email"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    @error('editEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Phone</label>
                    <input wire:model="editPhone" type="text"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Role</label>
                    <select wire:model="editRole"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Select role…</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucwords(str_replace(['-','_'],' ',$role->name)) }}</option>
                        @endforeach
                    </select>
                    @error('editRole') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">New Password <span class="font-normal text-gray-400">(leave blank to keep current)</span></label>
                    <input wire:model="editPassword" type="password" placeholder="Leave blank to keep current"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    @error('editPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/60 rounded-b-2xl flex justify-end gap-3">
                <button wire:click="$set('showEditModal',false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                <button wire:click="saveEdit" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-semibold bg-orange-500 hover:bg-orange-600 disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm">Save Changes</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Deactivate Confirm ───────────────────────────────────────── --}}
    @if($showDeactivateConfirm && $targetUserId)
    @php $targetUser = \App\Models\User::find($targetUserId); @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-sm mx-4 border border-gray-100 dark:border-zinc-700">
            <div class="px-6 py-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center mb-4
                            {{ $targetUser?->is_active ? 'bg-red-100 dark:bg-red-900/30' : 'bg-teal-100 dark:bg-teal-900/30' }}">
                    <svg class="w-6 h-6 {{ $targetUser?->is_active ? 'text-red-600' : 'text-teal-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $targetUser?->is_active ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">
                    {{ $targetUser?->is_active ? 'Deactivate Account?' : 'Reactivate Account?' }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if($targetUser?->is_active)
                        <strong>{{ $targetUser?->name }}</strong> will be logged out and blocked from signing in.
                    @else
                        <strong>{{ $targetUser?->name }}</strong> will regain access to the system.
                    @endif
                </p>
            </div>
            <div class="px-6 pb-5 flex gap-3">
                <button wire:click="$set('showDeactivateConfirm',false)" class="flex-1 px-4 py-2 text-sm border border-gray-200 dark:border-zinc-600 text-gray-600 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">Cancel</button>
                <button wire:click="toggleStatus"
                        class="flex-1 px-4 py-2 text-sm font-semibold text-white rounded-xl transition-colors shadow-sm
                               {{ $targetUser?->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-teal-600 hover:bg-teal-700' }}">
                    {{ $targetUser?->is_active ? 'Deactivate' : 'Reactivate' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Page Header ─────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Staff Management</h1>
            <p class="text-sm text-gray-400 mt-0.5">Manage system users, roles, and account status · {{ number_format($stats['total']) }} {{ Str::plural('member', $stats['total']) }}</p>
        </div>
        <button wire:click="$set('showCreateModal',true)"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold bg-orange-500 hover:bg-orange-600 text-white rounded-xl shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Staff
        </button>
    </div>

    {{-- ── Stats Bar ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-blue-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                </div>
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">Total Staff</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-white/60 mt-1">{{ $roleCounts->count() }} {{ Str::plural('role', $roleCounts->count()) }} assigned</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-teal-100 dark:bg-teal-900/30 text-teal-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['active']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Can sign in</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Inactive</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['inactive']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Blocked from access</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-orange-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Rate</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $stats['total'] > 0 ? round(($stats['active'] / $stats['total']) * 100) : 0 }}%</p>
            <p class="text-xs text-gray-400 mt-1">Of total headcount</p>
        </div>
    </div>

    {{-- ── Role Breakdown Pills ────────────────────────────────────── --}}
    @if($roleCounts->isNotEmpty())
    <div class="flex gap-2 flex-wrap mb-5">
        @foreach($roleCounts->take(6) as $rc)
        <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/30">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            <span class="text-xs font-bold text-orange-600 dark:text-blue-400">{{ ucwords(str_replace(['-','_'],' ',$rc->name)) }}</span>
            <span class="text-[10px] font-black text-blue-400 dark:text-blue-500">{{ $rc->users_count }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Filters ──────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 mb-5">
        <div class="px-5 py-4 flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-48">
                <input wire:model.live.debounce.300="search" type="text" placeholder="Search by name or email…"
                       class="w-full px-3.5 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
            </div>
            <select wire:model.live="filterRole"
                    class="px-3.5 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="all">All Roles</option>
                @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ ucwords(str_replace(['-','_'],' ',$role->name)) }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterStatus"
                    class="px-3.5 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    {{-- ── Staff Table ───────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-[#1a2035] text-white text-[11px] font-semibold uppercase tracking-widest">
                    <th class="px-6 py-3.5 text-left">Staff Member</th>
                    <th class="px-5 py-3.5 text-left">Role</th>
                    <th class="px-5 py-3.5 text-left hidden md:table-cell">Phone</th>
                    <th class="px-5 py-3.5 text-left hidden lg:table-cell">Branch · Joined</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                @forelse($staff as $member)
                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="size-9 rounded-full bg-gradient-to-br from-blue-400 to-orange-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                {{ $member->initials() }}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $member->name }}</div>
                                <div class="text-xs text-gray-400">{{ $member->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        @if($member->roles->isNotEmpty())
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-orange-600 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ ucwords(str_replace(['-','_'],' ',$member->roles->first()->name)) }}
                        </span>
                        @else
                        <span class="text-xs text-gray-400 italic">No role</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-gray-600 dark:text-gray-300 text-xs hidden md:table-cell">
                        {{ $member->phone ?? '—' }}
                    </td>
                    <td class="px-5 py-4 hidden lg:table-cell">
                        <p class="text-xs text-gray-700 dark:text-gray-300">{{ $member->branch?->name ?? '—' }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $member->created_at->format('d M Y') }}</p>
                    </td>
                    <td class="px-5 py-4 text-center">
                        @if($member->is_active)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>Active
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>Inactive
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button wire:click="openDetail('{{ $member->id }}')"
                                    class="px-2.5 py-1.5 text-xs font-semibold text-orange-500 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                    title="View Profile">View</button>
                            <button wire:click="startEdit('{{ $member->id }}')"
                                    class="p-1.5 text-gray-400 hover:text-orange-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                    title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                            </button>
                            <button wire:click="confirmToggleStatus('{{ $member->id }}')"
                                    class="p-1.5 rounded-lg transition-colors {{ $member->is_active ? 'text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20' : 'text-gray-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20' }}"
                                    title="{{ $member->is_active ? 'Deactivate' : 'Reactivate' }}">
                                @if($member->is_active)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200 dark:text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        <p class="text-sm font-medium">No staff members found</p>
                        <p class="text-xs mt-1">Try adjusting filters or add a new member.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($staff->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800">
            {{ $staff->links() }}
        </div>
        @endif
    </div>

    {{-- ══ STAFF DETAIL SLIDE-OVER ══ --}}
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

            @if($this->detailStaff)
            @php
                $ds             = $this->detailStaff;
                $dsRole         = $ds->roles->first();
                $dsInitials     = Str::of($ds->name)->explode(' ')->map(fn($p) => strtoupper(substr($p,0,1)))->take(2)->implode('');
                $loansCount     = $ds->disbursedLoans()->count();
                $customersCount = $ds->registeredCustomers()->count();
                $vendorsCount   = $ds->managedVendors()->count();
            @endphp

            {{-- Header --}}
            <div class="px-6 py-6 bg-gradient-to-r from-blue-700 to-blue-800 text-white">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-xl font-black flex-shrink-0">
                            {{ $dsInitials }}
                        </div>
                        <div>
                            <p class="text-lg font-black leading-tight">{{ $ds->name }}</p>
                            <p class="text-white/70 text-xs mt-0.5">{{ $ds->email }}</p>
                            @if($dsRole)
                            <span class="mt-1.5 inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white">
                                {{ ucwords(str_replace(['-','_'],' ',$dsRole->name)) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    @if($ds->is_active)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-500/20 text-teal-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-300"></span>Active
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-red-500/20 text-red-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>Inactive
                    </span>
                    @endif
                    @if($ds->email_verified_at)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-white/10 text-white/70">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        Email verified
                    </span>
                    @endif
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Contact & Account Info --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Contact & Account</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Phone</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $ds->phone ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Employee Code</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5 font-mono">{{ $ds->employee_code ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Branch</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $ds->branch?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Joined</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $ds->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $ds->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>

                {{-- Activity Summary --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Activity Summary</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 text-center border border-blue-100 dark:border-blue-900/30">
                            <p class="text-xl font-black text-orange-500 dark:text-blue-400">{{ number_format($loansCount) }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 font-semibold">Loans<br>Disbursed</p>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-3 text-center border border-emerald-100 dark:border-emerald-900/30">
                            <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($customersCount) }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 font-semibold">Customers<br>Registered</p>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 text-center border border-amber-100 dark:border-amber-900/30">
                            <p class="text-xl font-black text-amber-600 dark:text-amber-400">{{ number_format($vendorsCount) }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 font-semibold">Vendors<br>Managed</p>
                        </div>
                    </div>
                </div>

                {{-- Roles --}}
                @if($ds->roles->isNotEmpty())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Roles</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($ds->roles as $role)
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-100 text-orange-600 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ ucwords(str_replace(['-','_'],' ',$role->name)) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Account ID --}}
                <div class="pt-3 border-t border-gray-100 dark:border-zinc-800">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Account ID</p>
                    <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-0.5 break-all">{{ $ds->id }}</p>
                    @if($ds->email_verified_at)
                    <p class="text-[10px] text-gray-400 mt-1">Email verified {{ $ds->email_verified_at->diffForHumans() }}</p>
                    @endif
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex gap-3">
                <button wire:click="closeDetail"
                        class="flex-1 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
                <button wire:click="startEdit('{{ $ds->id }}'); $wire.closeDetail()"
                        class="flex-1 py-2.5 text-sm font-semibold rounded-xl bg-orange-500 hover:bg-orange-600 text-white transition-colors">
                    Edit Profile
                </button>
            </div>
            @endif
        </div>
    </div>

</div>
