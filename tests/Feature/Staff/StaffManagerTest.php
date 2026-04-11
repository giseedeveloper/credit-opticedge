<?php

use App\Livewire\Staff\StaffManager;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => true]);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['description' => 'Administrator']);
    $this->admin->assignRole('admin');

    foreach (['staff.view', 'staff.create', 'staff.edit'] as $perm) {
        $this->admin->givePermissionTo(
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web'])
        );
    }
});

test('guests are redirected from /staff', function () {
    $this->get(route('staff.index'))->assertRedirect(route('login'));
});

test('users without staff.view get 403 on /staff', function () {
    $user = User::factory()->create(['is_active' => true]);
    $this->actingAs($user)->get(route('staff.index'))->assertForbidden();
});

test('admin can view the staff management page', function () {
    $this->actingAs($this->admin)->get(route('staff.index'))->assertOk();
});

test('can open add staff modal without rendering errors', function () {
    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->call('openCreateModal')
        ->assertSet('showCreateModal', true);
});

test('can create a new staff member', function () {
    Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    $branch = Branch::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->set('newName', 'Jane Doe')
        ->set('newEmail', 'jane@example.com')
        ->set('newPassword', 'password123')
        ->set('newRole', 'manager')
        ->set('newBranchId', $branch->id)
        ->set('newJoinedAt', '2026-01-15')
        ->call('createStaff')
        ->assertDispatched('toast');

    $user = User::where('email', 'jane@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('manager'))->toBeTrue();
    expect($user->role)->toBe('manager');
    expect($user->branch_id)->toBe($branch->id);
    expect($user->joined_at?->toDateString())->toBe('2026-01-15');
    expect($user->employee_code)->not->toBeNull();
    expect($user->is_active)->toBeTrue();
});

test('create staff validates email uniqueness', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->set('newName', 'New User')
        ->set('newEmail', 'duplicate@example.com')
        ->set('newPassword', 'password123')
        ->set('newRole', 'admin')
        ->set('newJoinedAt', '2026-01-15')
        ->call('createStaff')
        ->assertHasErrors(['newEmail' => 'unique']);
});

test('can edit staff name and role', function () {
    Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['name' => 'Old Name', 'is_active' => true, 'joined_at' => '2025-01-01']);
    $staff->assignRole('admin');
    $staff->syncRoleColumn('admin');

    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->call('startEdit', $staff->id)
        ->set('editName', 'New Name')
        ->set('editRole', 'accountant')
        ->set('editBranchId', $branch->id)
        ->set('editJoinedAt', '2025-03-01')
        ->call('saveEdit')
        ->assertDispatched('toast');

    expect($staff->fresh()->name)->toBe('New Name');
    expect($staff->fresh()->hasRole('accountant'))->toBeTrue();
    expect($staff->fresh()->role)->toBe('accountant');
    expect($staff->fresh()->branch_id)->toBe($branch->id);
    expect($staff->fresh()->joined_at?->toDateString())->toBe('2025-03-01');
});

test('can deactivate an active staff member', function () {
    $staff = User::factory()->create(['is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->call('confirmToggleStatus', $staff->id)
        ->call('toggleStatus')
        ->assertDispatched('toast');

    expect($staff->fresh()->is_active)->toBeFalse();
});

test('can reactivate an inactive staff member', function () {
    $staff = User::factory()->create(['is_active' => false]);

    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->call('confirmToggleStatus', $staff->id)
        ->call('toggleStatus')
        ->assertDispatched('toast');

    expect($staff->fresh()->is_active)->toBeTrue();
});

test('admin cannot deactivate their own account', function () {
    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->call('confirmToggleStatus', $this->admin->id)
        ->call('toggleStatus')
        ->assertDispatched('toast');

    expect($this->admin->fresh()->is_active)->toBeTrue();
});

test('inactive user cannot access the staff page', function () {
    $inactive = User::factory()->create(['is_active' => false]);
    $inactive->assignRole('admin');

    $this->actingAs($inactive)
        ->get(route('staff.index'))
        ->assertRedirect(route('login'));
});
