<?php

use App\Models\Role;
use App\Models\User;

test('isDealer is true when the user has the dealer Spatie role', function () {
    Role::firstOrCreate(
        ['name' => 'dealer', 'guard_name' => 'web'],
        ['description' => 'Dealer privilege']
    );

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('dealer');

    expect($user->isDealer())->toBeTrue()
        ->and($user->isVendor())->toBeTrue();
});

test('isDealer is false without the dealer role', function () {
    Role::firstOrCreate(
        ['name' => 'front-officer', 'guard_name' => 'web'],
        ['description' => 'Front Officer']
    );

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('front-officer');

    expect($user->isDealer())->toBeFalse();
});
