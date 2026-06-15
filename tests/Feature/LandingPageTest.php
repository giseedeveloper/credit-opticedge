<?php

use App\Models\User;

it('shows the marketing landing page for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('KYC hadi mkopo')
        ->assertSee('mfumo mmoja')
        ->assertSee('HQ Login')
        ->assertSee('Ingia HQ Console')
        ->assertSee('Field Officer app')
        ->assertSee('Face match 75%')
        ->assertSee('Action inbox')
        ->assertSee('Mkopo wangu')
        ->assertSee('Fast. Secure. Verified.');
});

it('redirects authenticated users from home to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
