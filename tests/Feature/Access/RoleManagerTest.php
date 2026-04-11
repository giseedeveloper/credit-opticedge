<?php

use App\Livewire\Access\RoleManager;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => true]);
    Role::create(['name' => 'admin', 'guard_name' => 'web', 'description' => 'Administrator']);
    $this->admin->assignRole('admin');
    $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'access.view', 'guard_name' => 'web']));
    $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'access.create', 'guard_name' => 'web']));
    $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'access.edit', 'guard_name' => 'web']));
    $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'access.delete', 'guard_name' => 'web']));
});

test('unauthenticated users are redirected from /access', function () {
    $this->get(route('access'))->assertRedirect(route('login'));
});

test('users without access.view permission get 403 on /access', function () {
    $user = User::factory()->create(['is_active' => true]);
    $this->actingAs($user)->get(route('access'))->assertForbidden();
});

test('admin can view the role manager page', function () {
    $this->actingAs($this->admin)->get(route('access'))->assertOk();
});

test('can create a new role', function () {
    Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->set('newRoleName', 'test-role')
        ->set('newRoleDescription', 'Test role description')
        ->call('createRole')
        ->assertDispatched('toast');

    expect(Role::where('name', 'test-role')->exists())->toBeTrue();
});

test('create role validates unique name', function () {
    Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->set('newRoleName', 'admin')
        ->call('createRole')
        ->assertHasErrors(['newRoleName' => 'unique']);
});

test('can select a role and load its permissions', function () {
    $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

    Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->call('selectRole', $role->id)
        ->assertSet('selectedRole.id', $role->id);
});

test('can enable all permissions for a module', function () {
    $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

    $component = Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->call('selectRole', $role->id)
        ->call('enableModuleAll', 'Dashboard');

    $perms = $component->get('rolePermissions');
    expect(in_array('dashboard.view', $perms))->toBeTrue();
    expect(in_array('dashboard.all', $perms))->toBeTrue();
});

test('can disable all permissions for a module', function () {
    $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

    $component = Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->call('selectRole', $role->id)
        ->call('enableModuleAll', 'Loans')
        ->call('disableModuleAll', 'Loans');

    $perms = $component->get('rolePermissions');
    expect(in_array('loans.view', $perms))->toBeFalse();
    expect(in_array('loans.all', $perms))->toBeFalse();
});

test('can edit a role name and description', function () {
    $role = Role::create(['name' => 'editor-role', 'guard_name' => 'web', 'description' => 'Old']);

    Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->call('selectRole', $role->id)
        ->call('startEditRole')
        ->set('editRoleName', 'updated-role')
        ->set('editRoleDescription', 'New description')
        ->call('saveRoleEdit')
        ->assertDispatched('toast');

    expect(Role::where('name', 'updated-role')->where('description', 'New description')->exists())->toBeTrue();
});

test('can delete a role', function () {
    $role = Role::create(['name' => 'deletable-role', 'guard_name' => 'web']);

    Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->call('selectRole', $role->id)
        ->call('confirmDeleteRole')
        ->call('deleteRole')
        ->assertDispatched('toast');

    expect(Role::where('name', 'deletable-role')->exists())->toBeFalse();
});

test('can assign a role to a user', function () {
    $role = Role::create(['name' => 'assignable', 'guard_name' => 'web']);
    $user = User::factory()->create(['is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->call('selectRole', $role->id)
        ->call('assignRole', $user->id)
        ->assertDispatched('toast');

    expect($user->fresh()->hasRole('assignable'))->toBeTrue();
    expect($user->fresh()->role)->toBe('assignable');
});

test('can revoke a role from a user', function () {
    $role = Role::create(['name' => 'revokable', 'guard_name' => 'web']);
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('revokable');

    Livewire::actingAs($this->admin)
        ->test(RoleManager::class)
        ->call('selectRole', $role->id)
        ->call('revokeRole', $user->id)
        ->assertDispatched('toast');

    expect($user->fresh()->hasRole('revokable'))->toBeFalse();
    expect($user->fresh()->role)->toBe('staff');
});

test('inactive user is blocked even with correct credentials', function () {
    $inactive = User::factory()->create(['is_active' => false]);
    $inactive->assignRole('admin');

    $this->actingAs($inactive)->get(route('dashboard'))->assertRedirect(route('login'));
});
