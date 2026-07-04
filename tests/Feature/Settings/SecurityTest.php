<?php

use App\Livewire\Settings\Security;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Livewire\Livewire;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('security settings page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Security center')
        ->assertSee('Two-factor methods')
        ->assertSee('Enable');
});

test('security settings page opens without password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Confirm security changes with your password');
});

test('security settings page renders without two factor when feature is disabled', function () {
    config(['fortify.features' => []]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Update password')
        ->assertDontSee('Two-factor methods');
});

test('two factor authentication disabled when confirmation abandoned between requests', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    $component = Livewire::test(Security::class);

    $component->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ]);
});

test('password can be updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Security::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Security::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});

test('email otp backup can be enabled from security settings with password confirmation', function () {
    $user = User::factory()->withTwoFactor()->create([
        'password' => Hash::make('password'),
        'email_otp_enabled' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('email_otp_password', 'password')
        ->call('enableEmailOtp')
        ->assertHasNoErrors();

    expect($user->fresh()->email_otp_enabled)->toBeTrue();
});

test('email otp backup cannot be changed with the wrong password', function () {
    $user = User::factory()->withTwoFactor()->create([
        'password' => Hash::make('password'),
        'email_otp_enabled' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('email_otp_password', 'wrong-password')
        ->call('enableEmailOtp')
        ->assertHasErrors(['email_otp_password']);

    expect($user->fresh()->email_otp_enabled)->toBeFalse();
});

test('authenticator setup requires password confirmation from security settings', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->call('enable')
        ->assertHasErrors(['email_otp_password']);

    expect($user->fresh()->two_factor_secret)->toBeNull();

    Livewire::test(Security::class)
        ->set('email_otp_password', 'password')
        ->call('enable')
        ->assertHasNoErrors()
        ->assertSet('showModal', true);

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});