<div>

    {{-- ── Toast notification ────────────────────────────────────── --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : type==='danger' ? 'bg-red-500' : 'bg-oe'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- ── Create Role Modal ─────────────────────────────────────── --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showCreateModal',false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-700 w-full max-w-md mx-4">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Create New Role</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Define a custom role with granular permissions</p>
                </div>
                <button wire:click="$set('showCreateModal',false)" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Role Name</label>
                    <input wire:model="newRoleName" type="text" placeholder="e.g. branch-manager"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('newRoleName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Description</label>
                    <input wire:model="newRoleDescription" type="text" placeholder="e.g. Branch Manager privileges"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                    @error('newRoleDescription') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/60 rounded-b-2xl flex justify-end gap-3">
                <button wire:click="$set('showCreateModal',false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                <button wire:click="createRole" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-semibold bg-oe hover:bg-oe-hover disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm">Create Role</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Delete Confirm Modal ─────────────────────────────────────── --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-red-100 dark:border-red-900/40 w-full max-w-sm mx-4">
            <div class="px-6 py-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Delete Role?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Deleting <strong>{{ ucwords(str_replace(['-','_'],' ',$selectedRole->name)) }}</strong> will revoke it from all users. This cannot be undone.
                </p>
            </div>
            <div class="px-6 pb-5 flex gap-3">
                <button wire:click="$set('showDeleteConfirm',false)" class="flex-1 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-zinc-600 rounded-xl hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">Cancel</button>
                <button wire:click="deleteRole" class="flex-1 px-4 py-2 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors shadow-sm">Yes, Delete</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Page Header & Stats ────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="key" size="lg" palette="violet" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Roles & Permissions</h1>
            <p class="text-sm text-gray-400 mt-0.5">Define roles, configure module-level access, and assign users</p>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-3 gap-4 mb-5">
        <div class="bg-gradient-to-br from-oe to-oe-hover rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-oe/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="shield-check" size="sm" />
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">Roles</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['roles']) }}</p>
            <p class="text-xs text-white/60 mt-1">Configured in system</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-teal-100 dark:bg-teal-900/30 text-teal-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Permissions</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['permissions']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Granular access controls</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-1.5 rounded-lg bg-oe-soft dark:bg-oe/10 text-oe">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Assigned Users</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['users']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Have at least one role</p>
        </div>
    </div>

    <div class="flex flex-row gap-5 min-h-full">

        {{-- ── LEFT PANEL: Role List ─────────────────────────────────── --}}
        <div class="w-72 shrink-0">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="px-5 pt-5 pb-4 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Roles</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $roles->count() }} roles configured</p>
                    </div>
                    <button wire:click="$set('showCreateModal',true)"
                            class="shrink-0 flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-oe hover:bg-oe-hover text-white rounded-full transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        New
                    </button>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-zinc-800/60 max-h-[calc(100vh-280px)] overflow-y-auto" style="scrollbar-width:none">
                    @foreach($roles as $role)
                    @php $isSelected = $selectedRole && $selectedRole->id === $role->id; @endphp
                    <button wire:click="selectRole('{{ $role->id }}')"
                            class="w-full text-left px-5 py-3.5 transition-all
                                   {{ $isSelected ? 'bg-oe-soft dark:bg-blue-950/40 border-l-2 border-l-blue-500' : 'hover:bg-gray-50 dark:hover:bg-zinc-800/50 border-l-2 border-l-transparent' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-bold {{ $isSelected ? 'text-oe-hover dark:text-oe' : 'text-gray-800 dark:text-gray-100' }}">
                                {{ ucwords(str_replace(['-','_'], ' ', $role->name)) }}
                            </span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $isSelected ? 'bg-oe-soft text-oe dark:bg-oe/15 dark:text-oe' : 'bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                                {{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}
                            </span>
                        </div>
                        @if($role->description)
                        <div class="text-xs mt-0.5 truncate {{ $isSelected ? 'text-oe' : 'text-gray-400' }}">{{ $role->description }}</div>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── RIGHT PANEL ───────────────────────────────────────────── --}}
        <div class="flex-1 min-w-0">
            @if($selectedRole)
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">

                {{-- Role header + actions --}}
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800">
                    @if($editingRole)
                    <div class="flex items-start gap-4">
                        <div class="flex-1 grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Role Name</label>
                                <input wire:model="editRoleName" type="text"
                                       class="w-full px-3 py-2 text-sm border border-blue-300 dark:border-oe/30 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                                @error('editRoleName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Description</label>
                                <input wire:model="editRoleDescription" type="text"
                                       class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-5">
                            <button wire:click="saveRoleEdit" class="px-4 py-2 text-sm font-semibold bg-oe hover:bg-oe-hover text-white rounded-xl transition-colors">Save</button>
                            <button wire:click="$set('editingRole',false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                        </div>
                    </div>
                    @else
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ucwords(str_replace(['-','_'], ' ', $selectedRole->name)) }}
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe">
                                    {{ count($rolePermissions) }} {{ Str::plural('permission', count($rolePermissions)) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
                                    {{ $selectedRole->users_count }} {{ Str::plural('user', $selectedRole->users_count) }}
                                </span>
                            </div>
                            @if($selectedRole->description)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $selectedRole->description }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="startEditRole"
                                    class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold border border-gray-200 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 rounded-xl transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                Edit Role
                            </button>
                            <button wire:click="confirmDeleteRole"
                                    class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold border border-red-200 dark:border-red-900/50 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                Delete
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Tabs --}}
                    <div class="flex gap-1 mt-4 p-1 bg-gray-100 dark:bg-zinc-800 rounded-xl w-fit">
                        <button wire:click="setTab('permissions')"
                                class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-colors
                                       {{ $activeTab==='permissions' ? 'bg-white dark:bg-zinc-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                            Permissions
                        </button>
                        <button wire:click="setTab('users')"
                                class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-colors
                                       {{ $activeTab==='users' ? 'bg-white dark:bg-zinc-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                            Users
                        </button>
                    </div>
                </div>

                {{-- ── TAB: Permissions Matrix ─────────────────────── --}}
                @if($activeTab === 'permissions')
                <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                    <p class="text-xs text-gray-400">Toggle module-level access then click Save Changes.</p>
                    <button wire:click="savePermissions"
                            wire:loading.attr="disabled"
                            wire:target="savePermissions"
                            :disabled="$wire.savingPermissions"
                            class="flex items-center gap-2 px-5 py-2 text-sm font-semibold bg-oe hover:bg-oe-hover disabled:opacity-60 text-white rounded-xl shadow-sm transition-colors">
                        <span wire:loading.remove wire:target="savePermissions">Save Changes</span>
                        <span wire:loading wire:target="savePermissions" class="flex items-center gap-1.5">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            Saving…
                        </span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead>
                            <tr class="bg-[#1a2035] text-white text-[11px] font-semibold uppercase tracking-widest">
                                <th class="px-6 py-3.5 text-left text-gray-300">Module</th>
                                @foreach($actions as $action)
                                <th class="px-4 py-3.5 text-center">{{ strtoupper($action) }}</th>
                                @endforeach
                                <th class="px-6 py-3.5 text-center">ALL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach($modules as $module)
                            @php
                                $slug    = strtolower(str_replace(' ', '_', $module));
                                $allOn   = collect($actions)->every(fn($a) => in_array("{$slug}.{$a}", $rolePermissions));
                                $enabledCount = collect($actions)->filter(fn($a) => in_array("{$slug}.{$a}", $rolePermissions))->count();
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-6 py-3">
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $module }}</span>
                                    @if($enabledCount > 0)
                                    <span class="ml-2 text-[9px] font-bold text-teal-600 dark:text-teal-400">{{ $enabledCount }}/{{ count($actions) }}</span>
                                    @endif
                                </td>
                                @foreach($actions as $action)
                                @php $on = in_array("{$slug}.{$action}", $rolePermissions); @endphp
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="togglePermission('{{ $slug }}.{{ $action }}')"
                                            class="relative inline-flex h-[24px] w-[44px] items-center rounded-full transition-colors duration-200 focus:outline-none
                                                   {{ $on ? 'bg-teal-500' : 'bg-gray-200 dark:bg-zinc-600' }}">
                                        <span class="inline-block h-[18px] w-[18px] transform rounded-full bg-white shadow-sm transition-transform duration-200
                                                     {{ $on ? 'translate-x-[22px]' : 'translate-x-[3px]' }}"></span>
                                    </button>
                                </td>
                                @endforeach
                                {{-- Separate On / Off buttons --}}
                                <td class="px-6 py-3 text-center">
                                    <div class="inline-flex items-center rounded-lg border border-gray-200 dark:border-zinc-600 overflow-hidden text-xs font-semibold">
                                        <button wire:click="enableModuleAll('{{ $module }}')"
                                                class="px-3 py-1.5 transition-colors
                                                       {{ $allOn ? 'bg-teal-500 text-white' : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                                            On
                                        </button>
                                        <div class="w-px h-4 bg-gray-200 dark:bg-zinc-600"></div>
                                        <button wire:click="disableModuleAll('{{ $module }}')"
                                                class="px-3 py-1.5 transition-colors
                                                       {{ !$allOn ? 'bg-red-500 text-white' : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                                            Off
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- ── TAB: Users ──────────────────────────────────── --}}
                @if($activeTab === 'users')
                <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-800">
                    <input wire:model.live.debounce.300="userSearch" type="text" placeholder="Search users by name or email…"
                           class="w-full max-w-sm px-3.5 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-oe" />
                </div>
                <div class="divide-y divide-gray-100 dark:divide-zinc-800">
                    @forelse($users as $user)
                    @php $hasRole = in_array($user->id, $roleUsers); @endphp
                    <div class="flex items-center gap-4 px-6 py-3.5 hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div class="size-9 rounded-full bg-gradient-to-br from-blue-400 to-oe flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ $user->initials() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $user->name }}</span>
                                @if($hasRole)
                                <span class="text-[10px] font-bold px-1.5 py-0.5 bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe rounded-full">
                                    {{ ucwords(str_replace(['-','_'], ' ', $selectedRole->name)) }}
                                </span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 truncate">{{ $user->email }}</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button wire:click="openUserDetail('{{ $user->id }}')"
                                    class="px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:text-oe hover:bg-oe-soft dark:hover:bg-oe/10 rounded-lg transition-colors">
                                View
                            </button>
                            @if($hasRole)
                            <button wire:click="revokeRole('{{ $user->id }}')"
                                    class="px-3 py-1.5 text-xs font-semibold text-red-500 border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                Revoke
                            </button>
                            @else
                            <button wire:click="assignRole('{{ $user->id }}')"
                                    class="px-3 py-1.5 text-xs font-semibold text-oe border border-oe/25 dark:border-oe/30 hover:bg-oe-soft dark:hover:bg-oe/10 rounded-lg transition-colors">
                                Assign
                            </button>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                        <svg class="w-10 h-10 mb-3 text-gray-200 dark:text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        <p class="text-sm font-medium">No active users found</p>
                    </div>
                    @endforelse
                </div>
                @if(isset($users) && method_exists($users,'links'))
                <div class="px-6 py-3 border-t border-gray-100 dark:border-zinc-800">
                    {{ $users->links() }}
                </div>
                @endif
                @endif

            </div>
            @else
            <div class="h-80 flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                <svg class="w-14 h-14 mb-4 text-gray-200 dark:text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
                <p class="text-base font-semibold text-gray-500 dark:text-gray-400">Select a role to manage it</p>
                <p class="text-sm text-gray-400 mt-1">Pick any role from the left panel to configure permissions or assign users.</p>
            </div>
            @endif
        </div>

    </div>

    {{-- ══ USER PROFILE SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showUserDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeUserDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-md bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">

            @if($this->detailUser)
            @php
                $du         = $this->detailUser;
                $duRole     = $du->roles->first();
                $duInitials = Str::of($du->name)->explode(' ')->map(fn($p) => strtoupper(substr($p,0,1)))->take(2)->implode('');
                $loansCount     = $du->disbursedLoans()->count();
                $customersCount = $du->registeredCustomers()->count();
            @endphp

            {{-- Header --}}
            <div class="px-6 py-6 bg-gradient-to-r from-blue-700 to-blue-800 text-white">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white text-lg font-black flex-shrink-0">
                            {{ $duInitials }}
                        </div>
                        <div>
                            <p class="text-base font-black">{{ $du->name }}</p>
                            <p class="text-white/70 text-xs">{{ $du->email }}</p>
                            @if($duRole)
                            <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white">
                                {{ ucwords(str_replace(['-','_'],' ',$duRole->name)) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="closeUserDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    @if($du->is_active)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-teal-500/20 text-teal-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-300"></span>Active
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-500/20 text-red-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>Inactive
                    </span>
                    @endif
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Contact & Account</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Phone</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $du->phone ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Employee Code</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5 font-mono">{{ $du->employee_code ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Branch</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $du->branch?->name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Joined</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $du->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $du->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Activity</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-oe-soft dark:bg-oe/10 rounded-xl p-3 text-center border border-oe/20 dark:border-oe/20">
                            <p class="text-xl font-black text-oe dark:text-oe">{{ number_format($loansCount) }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 font-semibold">Loans Disbursed</p>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-3 text-center border border-emerald-100 dark:border-emerald-900/30">
                            <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($customersCount) }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 font-semibold">Customers Registered</p>
                        </div>
                    </div>
                </div>

                @if($du->roles->isNotEmpty())
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Assigned Roles</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($du->roles as $role)
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe">
                            {{ ucwords(str_replace(['-','_'],' ',$role->name)) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="pt-3 border-t border-gray-100 dark:border-zinc-800">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Account ID</p>
                    <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-0.5 break-all">{{ $du->id }}</p>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800">
                <button wire:click="closeUserDetail"
                        class="w-full py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

</div>
