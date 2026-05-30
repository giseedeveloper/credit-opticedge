<?php

use App\Livewire\Settings\IntegrationsHub;
use App\Models\Permission;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'settings.view', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('settings.view');
    $this->actingAs($this->user);
});

it('renders integration hub with sms mdm and face match cards', function () {
    Livewire::test(IntegrationsHub::class)
        ->assertOk()
        ->assertSee('Integration Hub')
        ->assertSee('SMS Gateway')
        ->assertSee('MDM / Device Lock');
});
