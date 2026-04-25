<?php

use App\Livewire\Staff\StaffManager;
use App\Models\Dealer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => true, 'role' => 'admin']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['description' => 'Administrator']);
    Role::firstOrCreate(['name' => 'front-officer', 'guard_name' => 'web'], ['description' => 'Front Officer']);
    Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web'], ['description' => 'Owner']);

    $this->admin->assignRole('admin');
    $this->admin->syncRoleColumn('admin');

    foreach (['staff.view', 'staff.create', 'staff.edit'] as $permission) {
        $this->admin->givePermissionTo(
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'])
        );
    }
});

test('front-office roles require a dealer during staff creation', function () {
    Dealer::factory()->create(['status' => 'active']);

    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->set('newName', 'Front Officer')
        ->set('newEmail', 'fo@example.com')
        ->set('newPassword', 'password123')
        ->set('newRole', 'front-officer')
        ->set('newJoinedAt', '2026-01-10')
        ->call('createStaff')
        ->assertHasErrors(['newDealerId' => 'required']);
});

test('global roles can be created without dealer assignment', function () {
    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->set('newName', 'Global Owner')
        ->set('newEmail', 'owner@example.com')
        ->set('newPassword', 'password123')
        ->set('newRole', 'owner')
        ->set('newJoinedAt', '2026-02-02')
        ->call('createStaff')
        ->assertDispatched('toast');

    $user = User::where('email', 'owner@example.com')->firstOrFail();

    expect($user->dealer_id)->toBeNull();
    expect($user->role)->toBe('owner');
    expect($user->hasRole('owner'))->toBeTrue();
});

test('editing a front-officer can move them to another dealer counter', function () {
    $dealer1 = Dealer::factory()->create(['status' => 'active', 'code' => 'DLR-A']);
    $dealer2 = Dealer::factory()->create(['status' => 'active', 'code' => 'DLR-B']);

    $staff = User::factory()->create([
        'dealer_id' => $dealer1->id,
        'joined_at' => '2025-05-01',
        'role' => 'front-officer',
    ]);
    $staff->assignRole('front-officer');
    $staff->syncRoleColumn('front-officer');

    Livewire::actingAs($this->admin)
        ->test(StaffManager::class)
        ->call('startEdit', $staff->id)
        ->set('editRole', 'front-officer')
        ->set('editDealerId', $dealer2->id)
        ->set('editJoinedAt', '2025-06-15')
        ->call('saveEdit')
        ->assertDispatched('toast');

    expect($staff->fresh()->dealer_id)->toBe($dealer2->id);
    expect($staff->fresh()->joined_at?->toDateString())->toBe('2025-06-15');
    expect($staff->fresh()->role)->toBe('front-officer');
});
