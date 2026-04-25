<?php

use App\Livewire\Auth\Login;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach ([
        'access.view',
        'accounting.view',
        'calculator.view',
        'dashboard.view',
        'devices.view',
        'loans.all',
        'loans.create',
        'loans.view',
        'reports.view',
        'settings.view',
        'sms_campaign.view',
        'staff.view',
        'dealers.view',
    ] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

it('hides sidebar links that the user role has not been granted', function () {
    $user = userWithRolePermissions('front-officer', ['loans.view', 'loans.create']);

    $response = $this->actingAs($user)->get(route('credit.panel'));

    $response->assertOk()
        ->assertSee('Active Loans')
        ->assertSee('New KYC Wizard')
        ->assertDontSee('Dashboard')
        ->assertDontSee('Stock Overview')
        ->assertDontSee('Staff Management')
        ->assertDontSee('Roles & Permissions')
        ->assertDontSee('Collections');
});

it('blocks direct page access when the role has not been granted that permission', function () {
    $user = userWithRolePermissions('front-officer', ['loans.view']);

    $this->actingAs($user)->get(route('credit.panel'))->assertOk();
    $this->actingAs($user)->get(route('kyc.wizard'))->assertForbidden();
    $this->actingAs($user)->get(route('staff.index'))->assertForbidden();
    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

it('lets module all permissions unlock matching route gates and sidebar links', function () {
    $user = userWithRolePermissions('loan-admin', ['loans.all']);

    $response = $this->actingAs($user)->get(route('kyc.wizard'));

    $response->assertOk()
        ->assertSee('New KYC Wizard')
        ->assertSee('Active Loans')
        ->assertDontSee('Staff Management');
});

it('redirects web console login to the first page the user can access', function () {
    $user = userWithRolePermissions('front-officer', ['loans.view']);

    Livewire::test(Login::class)
        ->set('method', 'email')
        ->set('login_identifier', $user->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('credit.panel'));
});

it('keeps access management links visible only for users granted access view', function () {
    $user = userWithRolePermissions('access-admin', ['access.view']);

    $response = $this->actingAs($user)->get(route('access'));

    $response->assertOk()
        ->assertSee('Roles & Permissions')
        ->assertDontSee('Active Loans');
});

it('renders the colored icon shell on allowed console pages', function () {
    $markup = view('components.fluent-icon', ['name' => 'home'])->render();

    expect($markup)
        ->toContain('data-fluent-icon')
        ->toContain('data-flux-icon');
});

function userWithRolePermissions(string $roleName, array $permissions): User
{
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $role->syncPermissions($permissions);

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    return $user;
}
