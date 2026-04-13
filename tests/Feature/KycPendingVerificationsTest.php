<?php

use App\Livewire\Kyc\PendingVerifications;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['loans.create', 'loans.view']);
});

it('prevents approving when no KYC verification exists', function () {
    actingAs($this->user);

    $customer = Customer::factory()->create();

    Livewire::test(PendingVerifications::class)
        ->set('actionCustomerId', $customer->id)
        ->set('actionStage', 1)
        ->call('approveStage')
        ->assertHasErrors(['verification']);
});
