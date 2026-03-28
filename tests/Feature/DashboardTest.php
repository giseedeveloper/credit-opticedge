<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users with dashboard.view permission can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(
        \App\Models\Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web'])
    );
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users without dashboard.view permission get 403', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
});