<?php

namespace App\Livewire\Staff;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class StaffManager extends Component
{
    use WithPagination;

    private const GLOBAL_ROLES = ['admin', 'owner'];

    public string $search = '';

    public string $filterRole = 'all';

    public string $filterStatus = 'all';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeactivateConfirm = false;

    public bool $showDetail = false;

    public ?string $targetUserId = null;

    public ?string $detailUserId = null;

    public string $newName = '';

    public string $newEmail = '';

    public string $newPassword = '';

    public string $newRole = '';

    public string $newPhone = '';

    public string $newBranchId = '';

    public string $newJoinedAt = '';

    public string $editName = '';

    public string $editEmail = '';

    public string $editRole = '';

    public string $editPhone = '';

    public string $editPassword = '';

    public string $editBranchId = '';

    public string $editJoinedAt = '';

    public $roles;

    public $branches;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('staff.view'), 403);
        $this->roles = Role::orderBy('name')->get();
        $this->branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_headquarter')
            ->orderBy('name')
            ->get();
        $this->resetCreateForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRole(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedNewRole(): void
    {
        $this->resetValidation('newBranchId');
    }

    public function updatedEditRole(): void
    {
        $this->resetValidation('editBranchId');
    }

    public function roleRequiresBranch(?string $role): bool
    {
        return filled($role) && ! in_array($role, self::GLOBAL_ROLES, true);
    }

    public function roleScopeDescription(?string $role): string
    {
        if (blank($role)) {
            return 'Select a role to see whether branch assignment is mandatory.';
        }

        if ($this->roleRequiresBranch($role)) {
            return 'Branch is required so customer registration, loans, and operational reporting stay tied to the correct branch.';
        }

        return 'This is a global role. Branch can still be selected for reporting, but it is optional.';
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()->canAccess('staff.create'), 403);

        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function openDetail(string $id): void
    {
        $this->detailUserId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailUserId = null;
    }

    public function getDetailStaffProperty(): ?User
    {
        if (! $this->detailUserId) {
            return null;
        }

        return User::with(['roles', 'branch'])->find($this->detailUserId);
    }

    public function getTargetUserProperty(): ?User
    {
        if (! $this->targetUserId) {
            return null;
        }

        return User::find($this->targetUserId);
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetEditForm();
    }

    public function createStaff(): void
    {
        abort_unless(auth()->user()->canAccess('staff.create'), 403);

        $validated = $this->validate($this->createRules());
        $branchId = $validated['newBranchId'] ?: null;

        $user = User::create([
            'name' => $validated['newName'],
            'email' => $validated['newEmail'],
            'password' => Hash::make($validated['newPassword']),
            'phone' => $validated['newPhone'],
            'role' => $validated['newRole'],
            'branch_id' => $branchId,
            'joined_at' => $validated['newJoinedAt'],
            'employee_code' => $this->generateEmployeeCode($branchId),
            'is_active' => true,
        ]);

        $user->assignRole($this->newRole);
        $user->syncRoleColumn($this->newRole);

        activity('security')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Staff account created for [{$user->name}] with role [{$this->newRole}]");

        $this->closeCreateModal();
        $this->dispatch('toast', message: "{$user->name} added successfully.", type: 'success');
    }

    public function startEdit(string $userId): void
    {
        abort_unless(auth()->user()->canAccess('staff.edit'), 403);

        $user = User::with('roles')->findOrFail($userId);
        $this->targetUserId = $userId;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editRole = $user->roles->first()?->name ?? '';
        $this->editPhone = $user->phone ?? '';
        $this->editBranchId = $user->branch_id ?? '';
        $this->editJoinedAt = ($user->joined_at ?? $user->created_at)?->toDateString() ?? now()->toDateString();
        $this->editPassword = '';
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        abort_unless(auth()->user()->canAccess('staff.edit'), 403);

        $validated = $this->validate($this->editRules());
        $branchId = $validated['editBranchId'] ?: null;

        $user = User::findOrFail($this->targetUserId);
        $user->update([
            'name' => $validated['editName'],
            'email' => $validated['editEmail'],
            'phone' => $validated['editPhone'],
            'role' => $validated['editRole'],
            'branch_id' => $branchId,
            'joined_at' => $validated['editJoinedAt'],
            'employee_code' => $user->employee_code ?: $this->generateEmployeeCode($branchId),
        ]);

        if ($this->editPassword) {
            $user->update(['password' => Hash::make($this->editPassword)]);
        }

        $user->syncRoles([$this->editRole]);
        $user->syncRoleColumn($this->editRole);

        activity('security')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Staff profile updated for [{$user->name}]");

        $this->closeEditModal();
        $this->dispatch('toast', message: "{$user->name} updated successfully.", type: 'success');
    }

    public function confirmToggleStatus(string $userId): void
    {
        abort_unless(auth()->user()->canAccess('staff.edit'), 403);
        $this->targetUserId = $userId;
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

    protected function createRules(): array
    {
        return [
            'newName' => 'required|string|min:2|max:100',
            'newEmail' => 'required|email|unique:users,email',
            'newPassword' => 'required|string|min:8',
            'newRole' => 'required|exists:roles,name',
            'newPhone' => 'nullable|string|max:20',
            'newBranchId' => [
                Rule::requiredIf(fn (): bool => $this->roleRequiresBranch($this->newRole)),
                'nullable',
                'string',
                $this->activeBranchRule(),
            ],
            'newJoinedAt' => 'required|date|before_or_equal:today',
        ];
    }

    protected function editRules(): array
    {
        return [
            'editName' => 'required|string|min:2|max:100',
            'editEmail' => 'required|email|unique:users,email,'.$this->targetUserId,
            'editRole' => 'required|exists:roles,name',
            'editPhone' => 'nullable|string|max:20',
            'editPassword' => 'nullable|string|min:8',
            'editBranchId' => [
                Rule::requiredIf(fn (): bool => $this->roleRequiresBranch($this->editRole)),
                'nullable',
                'string',
                $this->activeBranchRule(),
            ],
            'editJoinedAt' => 'required|date|before_or_equal:today',
        ];
    }

    protected function activeBranchRule()
    {
        return Rule::exists('branches', 'id')
            ->where(fn ($query) => $query->where('is_active', true));
    }

    protected function generateEmployeeCode(?string $branchId = null): string
    {
        $branchCode = $this->branches?->firstWhere('id', $branchId)?->code;
        $normalizedBranchCode = filled($branchCode)
            ? '-'.Str::upper(Str::replace('-', '', $branchCode))
            : '';

        $prefix = "EMP{$normalizedBranchCode}";
        $sequence = User::where('employee_code', 'like', "{$prefix}-%")->count() + 1;

        do {
            $employeeCode = sprintf('%s-%04d', $prefix, $sequence);
            $sequence++;
        } while (User::where('employee_code', $employeeCode)->exists());

        return $employeeCode;
    }

    protected function resetCreateForm(): void
    {
        $this->resetValidation();

        $this->newName = '';
        $this->newEmail = '';
        $this->newPassword = '';
        $this->newRole = '';
        $this->newPhone = '';
        $this->newBranchId = '';
        $this->newJoinedAt = now()->toDateString();
    }

    protected function resetEditForm(): void
    {
        $this->resetValidation();

        $this->targetUserId = null;
        $this->editName = '';
        $this->editEmail = '';
        $this->editRole = '';
        $this->editPhone = '';
        $this->editPassword = '';
        $this->editBranchId = '';
        $this->editJoinedAt = '';
    }

    public function render()
    {
        $staff = User::with(['roles', 'branch'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereInsensitiveLike('name', "%{$this->search}%")
                    ->orWhereInsensitiveLike('email', "%{$this->search}%")
                    ->orWhereInsensitiveLike('employee_code', "%{$this->search}%");
            }))
            ->when($this->filterRole !== 'all', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->filterRole)))
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('name')
            ->paginate(15);

        $base = User::query();

        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];

        $roleCounts = Role::withCount(['users'])->orderByDesc('users_count')->get();

        return view('livewire.staff.staff-manager', compact('staff', 'stats', 'roleCounts'))
            ->layout('layouts.app', ['title' => 'Staff Management']);
    }
}
