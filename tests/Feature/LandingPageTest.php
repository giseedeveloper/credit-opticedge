<?php

use App\Models\User;

it('shows the marketing landing page for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Mobile Financing')
        ->assertSee('Made Easy')
        ->assertSee('GET STARTED')
        ->assertSee('LOGIN')
        ->assertSee('User analysis')
        ->assertSee('Muda uliobaki kulipia');
});

it('redirects authenticated users from home to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
