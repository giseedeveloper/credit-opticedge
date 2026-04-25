<?php

use App\Models\Permission;
use App\Models\User;

test('users with loans.view can load the defaulters page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web'])
    );

    $this->actingAs($user)
        ->get(route('credit.defaulters'))
        ->assertSuccessful()
        ->assertSee('Defaulters', escape: false);
});

test('users without loans.view cannot load the defaulters page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('credit.defaulters'))
        ->assertForbidden();
});
