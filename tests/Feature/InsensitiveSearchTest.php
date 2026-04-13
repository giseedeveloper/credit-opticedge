<?php

use App\Models\User;

it('supports case-insensitive search macros across database drivers', function () {
    User::factory()->create(['name' => 'Amina Khalid', 'email' => 'amina.khalid@opticedge.co.tz']);
    User::factory()->create(['name' => 'JUMA OMARI', 'email' => 'juma.omari@opticedge.co.tz']);

    $matches = User::query()
        ->whereInsensitiveLike('name', '%juma%')
        ->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->first()?->email)->toBe('juma.omari@opticedge.co.tz');

    $multiMatches = User::query()
        ->whereInsensitiveLike('name', '%amina%')
        ->orWhereInsensitiveLike('email', '%opticedge%')
        ->get();

    expect($multiMatches)->toHaveCount(2);
});
