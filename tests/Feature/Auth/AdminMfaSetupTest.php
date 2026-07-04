<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\AdminMfaSetup;
use App\Livewire\Settings\Security;
use App\Models\Role;
use App\Models\User;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('admin without two factor is redirected to mfa setup after password login', function () {
    $admin = adminUser();

    Livewire::test(Login::class)
        ->set('method', 'email')
        ->set('login_identifier', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.mfa.setup'));

    $this->assertAuthenticatedAs($admin);
    expect((bool) session()->get('admin_mfa_setup_required'))->toBeTrue();
});

test('fortify password login also redirects admin without two factor to mfa setup', function () {
    $admin = adminUser();

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.mfa.setup'));

    $this->assertAuthenticatedAs($admin);
    expect((bool) session()->get('admin_mfa_setup_required'))->toBeTrue();
});

test('admin with two factor is redirected to the challenge screen after password login', function () {
    $admin = adminUser(withTwoFactor: true);

    Livewire::test(Login::class)
        ->set('method', 'email')
        ->set('login_identifier', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
    expect(session('login.id'))->toBe($admin->getKey());
});

test('admin in mandatory mfa setup flow cannot browse other console pages', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->withSession(['admin_mfa_setup_required' => true])
        ->get(route('profile.edit'))
        ->assertRedirect(route('admin.mfa.setup'));
});

test('admin mfa setup page renders without the console shell', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->withSession([
            'admin_mfa_setup_required' => true,
            'auth.password_confirmed_at' => time(),
        ]);

    $this->get(route('admin.mfa.setup'))
        ->assertOk()
        ->assertSee('Admin security')
        ->assertSee('Set up authenticator')
        ->assertSee('Scan once. Use the rotating 6-digit code')
        ->assertSee("Can't scan the QR code?")
        ->assertDontSee('Business Insights')
        ->assertDontSee('Dashboard');

    expect($admin->fresh()->two_factor_secret)->not->toBeNull();
});

test('admin mfa setup confirmation shows recovery codes before console access', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->withSession([
            'admin_mfa_setup_required' => true,
            'auth.password_confirmed_at' => time(),
        ]);

    Livewire::test(AdminMfaSetup::class)
        ->call('continueToVerification')
        ->assertSet('showVerificationStep', true);
});

test('admin cannot disable mandatory two factor authentication', function () {
    $admin = adminUser(withTwoFactor: true);

    $this->actingAs($admin);

    Livewire::test(Security::class)
        ->call('disable')
        ->assertHasErrors(['twoFactor']);

    expect($admin->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

function adminUser(bool $withTwoFactor = false): User
{
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $factory = User::factory();

    if ($withTwoFactor) {
        $factory = $factory->withTwoFactor();
    }

    $user = $factory->create(['is_active' => true]);

    $user->assignRole($role);
    $user->syncRoleColumn('admin');

    return $user;
}
