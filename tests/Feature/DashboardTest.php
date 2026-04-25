<?php

use App\Livewire\ExecutiveDashboard;
use App\Models\Permission;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users with dashboard.view permission can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web'])
    );
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('executive dashboard loadData renders active devices KPI from total active loans', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web'])
    );

    Livewire::actingAs($user)
        ->test(ExecutiveDashboard::class)
        ->call('loadData')
        ->assertSet('readyToLoad', true)
        ->assertSee('Active Devices')
        ->assertSee('Dealer performance');
});

test('authenticated users without dashboard.view permission get 403', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
});
