<?php

namespace App\Livewire\Access;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\PermissionRegistrar;

class RoleManager extends Component
{
    use WithPagination;

    public $roles;

    public $selectedRole = null;

    public $rolePermissions = [];

    public string $activeTab = 'permissions';

    public bool $showCreateModal = false;

    public string $newRoleName = '';

    public string $newRoleDescription = '';

    public bool $editingRole = false;

    public string $editRoleName = '';

    public string $editRoleDescription = '';

    public bool $showDeleteConfirm = false;

    public bool $savingPermissions = false;

    public string $userSearch = '';

    public bool $showUserDetail = false;

    public ?string $detailUserId = null;

    public $modules = [
        'Dashboard', 'Accounting', 'SMS Campaign', 'Payment analytics', 'Loans',
        'Products', 'Calculator', 'Devices', 'Returned devices', 'Financial plans',
        'Staff', 'Dealers', 'Expenses', 'Reports', 'Sales',
        'Reconciliation', 'Access', 'Account', 'Settings',
    ];

    public $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('access.view'), 403);
        $this->loadRoles();
    }

    public function updatedUserSearch(): void
    {
        $this->resetPage();
    }

    public function openUserDetail(string $id): void
    {
        $this->detailUserId = $id;
        $this->showUserDetail = true;
    }

    public function closeUserDetail(): void
    {
        $this->showUserDetail = false;
        $this->detailUserId = null;
    }

    public function getDetailUserProperty(): ?User
    {
        if (! $this->detailUserId) {
            return null;
        }

        return User::with(['roles', 'dealer'])->find($this->detailUserId);
    }

    public function loadRoles(): void
    {
        $this->roles = Role::withCount('users')->orderBy('name')->get();
    }

    public function selectRole(string $roleId): void
    {
        $this->selectedRole = Role::with('permissions')->find($roleId);
        $this->rolePermissions = $this->selectedRole->permissions->pluck('name')->toArray();
        $this->editingRole = false;
        $this->showDeleteConfirm = false;
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function createRole(): void
    {
        abort_unless(auth()->user()->canAccess('access.create'), 403);

        $this->validate([
            'newRoleName' => 'required|string|min:2|max:64|unique:roles,name',
            'newRoleDescription' => 'nullable|string|max:128',
        ]);

        try {
            $role = Role::create([
                'name' => strtolower(trim($this->newRoleName)),
                'guard_name' => 'web',
                'description' => trim($this->newRoleDescription),
            ]);
        } catch (QueryException $e) {
            Log::error('Role create failed', [
                'actor' => auth()->id(),
                'name' => $this->newRoleName,
                'exception' => $e->getMessage(),
            ]);

            $this->dispatch('toast', message: 'Could not create role. Please retry.', type: 'danger');

            return;
        }

        $this->newRoleName = '';
        $this->newRoleDescription = '';
        $this->showCreateModal = false;

        $this->loadRoles();
        $this->selectRole($role->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('security')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->log('Role created: '.ucfirst($role->name));

        $this->dispatch('toast', message: "Role '{$role->name}' created.", type: 'success');
    }

    public function startEditRole(): void
    {
        abort_unless(auth()->user()->canAccess('access.edit'), 403);
        $this->editRoleName = $this->selectedRole->name;
        $this->editRoleDescription = $this->selectedRole->description ?? '';
        $this->editingRole = true;
    }

    public function saveRoleEdit(): void
    {
        abort_unless(auth()->user()->canAccess('access.edit'), 403);

        $this->validate([
            'editRoleName' => 'required|string|min:2|max:64|unique:roles,name,'.$this->selectedRole->id,
            'editRoleDescription' => 'nullable|string|max:128',
        ]);

        try {
            $this->selectedRole->update([
                'name' => strtolower(trim($this->editRoleName)),
                'description' => trim($this->editRoleDescription),
            ]);
        } catch (QueryException $e) {
            Log::error('Role update failed', [
                'actor' => auth()->id(),
                'role_id' => $this->selectedRole?->id,
                'exception' => $e->getMessage(),
            ]);

            $this->dispatch('toast', message: 'Could not update role. Please retry.', type: 'danger');

            return;
        }

        $this->editingRole = false;
        $this->loadRoles();
        $this->selectRole($this->selectedRole->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('security')
            ->performedOn($this->selectedRole)
            ->causedBy(auth()->user())
            ->log('Role updated: '.ucfirst($this->selectedRole->name));

        $this->dispatch('toast', message: 'Role updated.', type: 'success');
    }

    public function confirmDeleteRole(): void
    {
        abort_unless(auth()->user()->canAccess('access.delete'), 403);
        $this->showDeleteConfirm = true;
    }

    public function deleteRole(): void
    {
        abort_unless(auth()->user()->canAccess('access.delete'), 403);

        if (! $this->selectedRole) {
            return;
        }

        $roleName = ucfirst($this->selectedRole->name);
        try {
            $this->selectedRole->users()->detach();
            $this->selectedRole->delete();
        } catch (QueryException $e) {
            Log::error('Role delete failed', [
                'actor' => auth()->id(),
                'role_id' => $this->selectedRole?->id,
                'exception' => $e->getMessage(),
            ]);

            $this->dispatch('toast', message: 'Could not delete role. Please retry.', type: 'danger');

            return;
        }

        activity('security')
            ->causedBy(auth()->user())
            ->log("Role deleted: {$roleName}");

        $this->selectedRole = null;
        $this->rolePermissions = [];
        $this->showDeleteConfirm = false;
        $this->loadRoles();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->dispatch('toast', message: "Role '{$roleName}' deleted.", type: 'danger');
    }

    public function togglePermission(string $permissionName): void
    {
        if (in_array($permissionName, $this->rolePermissions)) {
            $this->rolePermissions = array_values(array_diff($this->rolePermissions, [$permissionName]));
        } else {
            $this->rolePermissions[] = $permissionName;
        }
    }

    public function enableModuleAll(string $module): void
    {
        $slug = strtolower(str_replace(' ', '_', $module));

        $this->rolePermissions[] = "{$slug}.all";
        foreach ($this->actions as $action) {
            $sub = "{$slug}.{$action}";
            if (! in_array($sub, $this->rolePermissions)) {
                $this->rolePermissions[] = $sub;
            }
        }
        $this->rolePermissions = array_values(array_unique($this->rolePermissions));
    }

    public function disableModuleAll(string $module): void
    {
        $slug = strtolower(str_replace(' ', '_', $module));

        $remove = ["{$slug}.all"];
        foreach ($this->actions as $action) {
            $remove[] = "{$slug}.{$action}";
        }
        $this->rolePermissions = array_values(array_diff($this->rolePermissions, $remove));
    }

    public function savePermissions(): void
    {
        abort_unless(auth()->user()->canAccess('access.edit'), 403);

        if (! $this->selectedRole) {
            return;
        }

        $this->savingPermissions = true;

        $validNames = Permission::pluck('name')->toArray();
        $sanitized = array_values(array_intersect($this->rolePermissions, $validNames));

        $oldPermissions = $this->selectedRole->permissions->pluck('name')->toArray();
        try {
            $this->selectedRole->syncPermissions($sanitized);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (QueryException $e) {
            Log::error('Sync role permissions failed', [
                'actor' => auth()->id(),
                'role_id' => $this->selectedRole->id,
                'exception' => $e->getMessage(),
            ]);

            $this->savingPermissions = false;
            $this->dispatch('toast', message: 'Could not save permissions. Please retry.', type: 'danger');

            return;
        }

        $added = array_diff($sanitized, $oldPermissions);
        $removed = array_diff($oldPermissions, $sanitized);
        $actor = auth()->user()->name;
        $roleName = ucfirst($this->selectedRole->name);

        foreach ($added as $perm) {
            activity('security')
                ->performedOn($this->selectedRole)
                ->causedBy(auth()->user())
                ->log("{$actor} granted [{$perm}] to role [{$roleName}]");
        }

        foreach ($removed as $perm) {
            activity('security')
                ->performedOn($this->selectedRole)
                ->causedBy(auth()->user())
                ->log("{$actor} revoked [{$perm}] from role [{$roleName}]");
        }

        $this->savingPermissions = false;
        $this->dispatch('toast', message: 'Permissions saved successfully.', type: 'success');
    }

    public function assignRole(string $userId): void
    {
        abort_unless(auth()->user()->canAccess('access.edit'), 403);

        $user = User::findOrFail($userId);
        $user->syncRoles([$this->selectedRole->name]);
        $user->syncRoleColumn($this->selectedRole->name);

        activity('security')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Role [{$this->selectedRole->name}] assigned to user [{$user->name}]");

        $this->loadRoles();
        $this->dispatch('toast', message: "{$user->name} assigned to {$this->selectedRole->name}.", type: 'success');
    }

    public function revokeRole(string $userId): void
    {
        abort_unless(auth()->user()->canAccess('access.edit'), 403);

        $user = User::findOrFail($userId);
        $user->removeRole($this->selectedRole->name);
        $user->syncRoleColumn();

        activity('security')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Role [{$this->selectedRole->name}] revoked from user [{$user->name}]");

        $this->loadRoles();
        $this->dispatch('toast', message: "Role revoked from {$user->name}.", type: 'danger');
    }

    public function render()
    {
        $users = collect();
        $roleUsers = collect();

        if ($this->selectedRole && $this->activeTab === 'users') {
            $users = User::query()
                ->when($this->userSearch, fn ($q) => $q->where(function ($q) {
                    $q->whereInsensitiveLike('name', "%{$this->userSearch}%")
                        ->orWhereInsensitiveLike('email', "%{$this->userSearch}%");
                }))
                ->where('is_active', true)
                ->orderBy('name')
                ->paginate(15);

            $roleUsers = $this->selectedRole->users()->pluck('id')->toArray();
        }

        $stats = [
            'roles' => Role::count(),
            'permissions' => Permission::count(),
            'users' => User::whereHas('roles')->count(),
        ];

        return view('livewire.access.role-manager', compact('users', 'roleUsers', 'stats'))
            ->layout('layouts.app', ['title' => 'Roles & Permissions']);
    }
}
