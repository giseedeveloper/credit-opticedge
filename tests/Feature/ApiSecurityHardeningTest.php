<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\PhoneModel;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['devices.view', 'loans.create', 'loans.view', 'reports.view', 'staff.view'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

it('blocks inactive users from receiving api tokens', function () {
    $user = User::factory()->create([
        'email' => 'inactive@example.test',
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'login_identifier' => $user->email,
        'password' => 'password',
    ]);

    $response->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Your account has been deactivated. Contact your administrator.');

    expect($user->tokens()->count())->toBe(0);
});

it('blocks inactive sanctum users from protected api routes', function () {
    $user = User::factory()->create(['is_active' => false]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Your account has been deactivated. Contact your administrator.');
});

it('requires report permission before analytics data is exposed', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/analytics/roi-analysis')->assertForbidden();

    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/analytics/roi-analysis')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('prevents one agent from editing another agent kyc draft', function () {
    $owner = User::factory()->create();
    $otherAgent = User::factory()->create();
    $otherAgent->givePermissionTo('loans.create');

    $draft = Customer::factory()->create([
        'registered_by' => $owner->id,
        'first_name' => '_draft_',
        'last_name' => '_draft_',
        'phone' => '_draft_'.uniqid(),
        'nida_number' => null,
    ]);

    Sanctum::actingAs($otherAgent);

    $this->postJson("/api/v1/kyc/application/{$draft->id}/step2", [])
        ->assertNotFound();
});

it('requires loan view permission before settlement quote exposure', function () {
    $loan = Loan::factory()->create();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/finance/settlement-quote/{$loan->id}")
        ->assertForbidden();

    $user = User::factory()->create();
    $user->givePermissionTo('loans.view');
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/finance/settlement-quote/{$loan->id}")
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('builds staff metrics from existing customer and loan ownership fields', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('staff.view');

    Customer::factory()->create(['registered_by' => $user->id]);
    Loan::factory()->create([
        'disbursed_by' => $user->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/staff/metrics')
        ->assertOk()
        ->assertJsonPath('data.total_customers_acquired', 1)
        ->assertJsonPath('data.active_loans_managed', 1);
});

it('resolves vendor stock through the user branch vendor mapping', function () {
    $branch = Branch::factory()->create();
    $brand = Brand::factory()->create();
    $model = PhoneModel::factory()->create(['brand_id' => $brand->id]);
    $vendor = Vendor::factory()->create(['branch_id' => $branch->id]);
    $unit = InventoryUnit::factory()->create([
        'phone_model_id' => $model->id,
        'vendor_id' => $vendor->id,
        'status' => 'vendor_stock',
    ]);
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->givePermissionTo('devices.view');

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/stock/vendor-list');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.data.0.id', $unit->id);
});
