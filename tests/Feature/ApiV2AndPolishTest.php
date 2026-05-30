<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Verification;
use App\Services\FaceMatchCoordinator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

it('exposes api v2 meta and staff metrics with version header', function () {
    $this->getJson('/api/v2/meta')
        ->assertOk()
        ->assertJsonPath('data.version', 2)
        ->assertHeader('X-Api-Version', '2');

    Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('staff.view');
    Sanctum::actingAs($user);

    $this->getJson('/api/v2/staff/metrics')
        ->assertOk()
        ->assertHeader('X-Api-Version', '2');
});

it('marks v1 responses as deprecated', function () {
    $this->postJson('/api/v1/login', [])
        ->assertHeader('Deprecation', 'true')
        ->assertHeader('X-Api-Version', '1');
});

it('seeds default permissions onto front-officer role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::where('name', 'front-officer')->firstOrFail();

    expect($role->hasPermissionTo('loans.create'))->toBeTrue()
        ->and($role->hasPermissionTo('devices.view'))->toBeTrue()
        ->and($role->hasPermissionTo('staff.view'))->toBeTrue();
});

it('returns expanded permission flags on me endpoint', function () {
    Permission::firstOrCreate(['name' => 'devices.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo(['devices.view', 'staff.view']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.permissions.can_view_stock', true)
        ->assertJsonPath('data.permissions.can_view_staff_metrics', true)
        ->assertJsonPath('data.api_permissions.0', 'devices.view')
        ->assertJsonPath('data.api_permissions.1', 'staff.view');
});

it('skips async face match when sync result is recent', function () {
    $verification = Verification::factory()->create([
        'face_match_status' => 'passed',
        'face_match_ran_at' => now()->subMinute(),
    ]);

    expect(app(FaceMatchCoordinator::class)->shouldSkipAsyncRun($verification))->toBeTrue();
});
