<?php

use App\Livewire\Dealers\DealerManager;
use App\Models\Dealer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->manager = User::factory()->create(['is_active' => true]);
    Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web'], ['description' => 'Manager']);
    $this->manager->assignRole('manager');
    $this->manager->syncRoleColumn('manager');

    foreach (['dealers.view', 'dealers.create', 'dealers.edit', 'dealers.delete'] as $permission) {
        $this->manager->givePermissionTo(
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'])
        );
    }
});

test('guests cannot open dealers page', function () {
    $this->get(route('dealers.index'))->assertRedirect(route('login'));
});

test('authorized user can create a dealer', function () {
    Livewire::actingAs($this->manager)
        ->test(DealerManager::class)
        ->call('openCreateModal')
        ->set('formName', 'Test Dealer Counter')
        ->set('formCode', 'DLR-TST-001')
        ->set('formPhone', '+255700111222')
        ->set('formCommission', '3.5')
        ->set('formStatus', 'active')
        ->call('saveDealer')
        ->assertDispatched('toast');

    $dealer = Dealer::query()->where('code', 'DLR-TST-001')->firstOrFail();

    expect($dealer->name)->toBe('Test Dealer Counter')
        ->and((float) $dealer->commission_rate)->toBe(3.5);
});

test('authorized user can update a dealer', function () {
    $dealer = Dealer::factory()->create([
        'name' => 'Old Name',
        'code' => 'DLR-OLD-01',
        'status' => 'active',
    ]);

    Livewire::actingAs($this->manager)
        ->test(DealerManager::class)
        ->call('openEditModal', $dealer->id)
        ->set('formName', 'New Counter Name')
        ->call('updateDealer')
        ->assertDispatched('toast');

    expect($dealer->fresh()->name)->toBe('New Counter Name');
});

test('authorized user can delete a dealer', function () {
    $dealer = Dealer::factory()->create([
        'name' => 'Delete Me',
        'code' => 'DLR-DEL-01',
        'status' => 'active',
    ]);

    Livewire::actingAs($this->manager)
        ->test(DealerManager::class)
        ->call('openDeleteModal', $dealer->id)
        ->call('deleteDealer')
        ->assertDispatched('toast');

    expect(Dealer::query()->whereKey($dealer->id)->exists())->toBeFalse();
    expect(Dealer::withTrashed()->whereKey($dealer->id)->exists())->toBeTrue();
});

test('dealer owner dropdown lists users with owner role', function () {
    Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web'], ['description' => 'Owner']);
    $owner = User::factory()->create([
        'name' => 'Shop Owner Candidate',
        'email' => 'owner.candidate@example.test',
        'is_active' => true,
    ]);
    $owner->assignRole('owner');
    $owner->syncRoleColumn('owner');

    Livewire::actingAs($this->manager)
        ->test(DealerManager::class)
        ->call('openCreateModal')
        ->assertSee('owner.candidate@example.test');
});
