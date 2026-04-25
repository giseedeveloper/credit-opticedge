<?php

namespace App\Livewire\Staff;

use App\Models\Dealer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

    public string $newJoinedAt = '';

    public string $newDealerId = '';

    public string $editName = '';

    public string $editEmail = '';

    public string $editRole = '';

    public string $editPhone = '';

    public string $editPassword = '';

    public string $editJoinedAt = '';

    public string $editDealerId = '';

    public $roles;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('staff.view'), 403);
        $this->roles = Role::orderBy('name')->get();
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

        return User::with(['roles', 'dealer'])->find($this->detailUserId);
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

        $dealerId = $this->resolveDealerIdForCreate($validated);

        $user = User::create([
            'name' => $validated['newName'],
            'email' => $validated['newEmail'],
            'password' => Hash::make($validated['newPassword']),
            'phone' => $validated['newPhone'],
            'role' => $validated['newRole'],
            'dealer_id' => $dealerId,
            'joined_at' => $validated['newJoinedAt'],
            'employee_code' => $this->generateEmployeeCode(),
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

        $this->editJoinedAt = ($user->joined_at ?? $user->created_at)?->toDateString() ?? now()->toDateString();
        $this->editDealerId = (string) ($user->dealer_id ?? '');
        $this->editPassword = '';
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        abort_unless(auth()->user()->canAccess('staff.edit'), 403);

        $validated = $this->validate($this->editRules());

        $user = User::findOrFail($this->targetUserId);
        $dealerId = $this->resolveDealerIdForEdit($validated, $user);

        $user->update([
            'name' => $validated['editName'],
            'email' => $validated['editEmail'],
            'phone' => $validated['editPhone'],
            'role' => $validated['editRole'],
            'dealer_id' => $dealerId,
            'joined_at' => $validated['editJoinedAt'],
            'employee_code' => $user->employee_code ?: $this->generateEmployeeCode(),
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
            'newDealerId' => [
                Rule::requiredIf(fn () => $this->roleRequiresDealer($this->newRole)),
                'nullable',
                'exists:dealers,id',
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
            'editDealerId' => [
                Rule::requiredIf(fn () => $this->roleRequiresDealer($this->editRole)),
                'nullable',
                'exists:dealers,id',
            ],

            'editJoinedAt' => 'required|date|before_or_equal:today',
        ];
    }

    protected function generateEmployeeCode(): string
    {
        $prefix = 'EMP';
        $sequence = User::where('employee_code', 'like', "{$prefix}-%")->count() + 1;

        do {
            $employeeCode = sprintf('%s-%04d', $prefix, $sequence);
            $sequence++;
        } while (User::where('employee_code', $employeeCode)->exists());

        return $employeeCode;
    }

    public function resetCreateForm(): void
    {
        $this->newName = '';
        $this->newEmail = '';
        $this->newPassword = '';
        $this->newRole = '';
        $this->newPhone = '';
        $this->newDealerId = '';
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
        $this->editDealerId = '';
        $this->editJoinedAt = '';
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveDealerIdForCreate(array $validated): ?string
    {
        if ($this->roleRequiresDealer($this->newRole)) {
            return $this->idOrNull($validated['newDealerId'] ?? '');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveDealerIdForEdit(array $validated, User $existing): ?string
    {
        $role = $validated['editRole'];

        if ($this->roleRequiresDealer($role)) {
            return $this->idOrNull($validated['editDealerId'] ?? '');
        }

        return null;
    }

    private function roleRequiresDealer(string $role): bool
    {
        return in_array($role, ['front-officer', 'back-officer'], true);
    }

    private function idOrNull(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    public function render()
    {
        $staff = User::with(['roles'])
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

        $dealers = Dealer::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('livewire.staff.staff-manager', compact('staff', 'stats', 'roleCounts', 'dealers'))
            ->layout('layouts.app', ['title' => 'Staff Management']);
    }
}
