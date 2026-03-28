<?php

namespace App\Livewire\Staff;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class StaffManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterRole = 'all';

    public string $filterStatus = 'all';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeactivateConfirm = false;

    public bool    $showDetail   = false;

    public ?string $targetUserId  = null;

    public ?string $detailUserId  = null;

    public string $newName = '';

    public string $newEmail = '';

    public string $newPassword = '';

    public string $newRole = '';

    public string $newPhone = '';

    public string $editName = '';

    public string $editEmail = '';

    public string $editRole = '';

    public string $editPhone = '';

    public string $editPassword = '';

    public $roles;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('staff.view'), 403);
        $this->roles = Role::orderBy('name')->get();
    }

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedFilterRole(): void   { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function openDetail(string $id): void
    {
        $this->detailUserId = $id;
        $this->showDetail   = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail  = false;
        $this->detailUserId = null;
    }

    public function getDetailStaffProperty(): ?User
    {
        if (! $this->detailUserId) {
            return null;
        }

        return User::with(['roles', 'branch'])->find($this->detailUserId);
    }

    public function createStaff(): void
    {
        abort_unless(auth()->user()->canAccess('staff.create'), 403);

        $this->validate([
            'newName'     => 'required|string|min:2|max:100',
            'newEmail'    => 'required|email|unique:users,email',
            'newPassword' => 'required|string|min:8',
            'newRole'     => 'required|exists:roles,name',
            'newPhone'    => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name'      => $this->newName,
            'email'     => $this->newEmail,
            'password'  => Hash::make($this->newPassword),
            'phone'     => $this->newPhone,
            'is_active' => true,
        ]);

        $user->assignRole($this->newRole);

        activity('security')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Staff account created for [{$user->name}] with role [{$this->newRole}]");

        $this->reset(['newName', 'newEmail', 'newPassword', 'newRole', 'newPhone']);
        $this->showCreateModal = false;
        $this->dispatch('toast', message: "{$user->name} added successfully.", type: 'success');
    }

    public function startEdit(string $userId): void
    {
        abort_unless(auth()->user()->canAccess('staff.edit'), 403);

        $user = User::with('roles')->findOrFail($userId);
        $this->targetUserId   = $userId;
        $this->editName       = $user->name;
        $this->editEmail      = $user->email;
        $this->editRole       = $user->roles->first()?->name ?? '';
        $this->editPhone      = $user->phone ?? '';
        $this->editPassword   = '';
        $this->showEditModal  = true;
    }

    public function saveEdit(): void
    {
        abort_unless(auth()->user()->canAccess('staff.edit'), 403);

        $this->validate([
            'editName'     => 'required|string|min:2|max:100',
            'editEmail'    => 'required|email|unique:users,email,'.$this->targetUserId,
            'editRole'     => 'required|exists:roles,name',
            'editPhone'    => 'nullable|string|max:20',
            'editPassword' => 'nullable|string|min:8',
        ]);

        $user = User::findOrFail($this->targetUserId);
        $user->update([
            'name'  => $this->editName,
            'email' => $this->editEmail,
            'phone' => $this->editPhone,
        ]);

        if ($this->editPassword) {
            $user->update(['password' => Hash::make($this->editPassword)]);
        }

        $user->syncRoles([$this->editRole]);

        activity('security')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Staff profile updated for [{$user->name}]");

        $this->showEditModal = false;
        $this->dispatch('toast', message: "{$user->name} updated successfully.", type: 'success');
    }

    public function confirmToggleStatus(string $userId): void
    {
        abort_unless(auth()->user()->canAccess('staff.edit'), 403);
        $this->targetUserId       = $userId;
        $this->showDeactivateConfirm = true;
    }

    public function toggleStatus(): void
    {
        abort_unless(auth()->user()->canAccess('staff.edit'), 403);

        $user = User::findOrFail($this->targetUserId);

        if ($user->id === auth()->id()) {
            $this->dispatch('toast', message: 'You cannot deactivate your own account.', type: 'danger');
            $this->showDeactivateConfirm = false;

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        $action = $user->is_active ? 'activated' : 'deactivated';
        activity('security')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Staff account {$action}: [{$user->name}]");

        $this->showDeactivateConfirm = false;
        $this->dispatch('toast', message: "Account {$action} for {$user->name}.", type: $user->is_active ? 'success' : 'danger');
    }

    public function render()
    {
        $staff = User::with(['roles', 'branch'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('email', 'ilike', "%{$this->search}%")
                    ->orWhere('employee_code', 'ilike', "%{$this->search}%");
            }))
            ->when($this->filterRole !== 'all', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->filterRole)))
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('name')
            ->paginate(15);

        $base = User::query();

        $stats = [
            'total'    => (clone $base)->count(),
            'active'   => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];

        $roleCounts = Role::withCount(['users'])->orderByDesc('users_count')->get();

        return view('livewire.staff.staff-manager', compact('staff', 'stats', 'roleCounts'))
            ->layout('layouts.app', ['title' => 'Staff Management']);
    }
}
