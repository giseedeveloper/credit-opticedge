<?php

use App\Livewire\Credit\LendingPanel;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\User;
use App\Models\Verification;
use App\Services\RiskAssessmentService;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('loans.create');
    $this->user->givePermissionTo('loans.view');

});

it('treats approved and verified kyc statuses as lending-eligible customers', function () {
    actingAs($this->user);

    Customer::factory()->create([
        'first_name' => 'Amina',
        'last_name' => 'Approved',
        'phone' => '0712000101',
        'kyc_status' => 'approved',
    ]);

    Customer::factory()->create([
        'first_name' => 'Legacy',
        'last_name' => 'Verified',
        'phone' => '0712000102',
        'kyc_status' => 'verified',
    ]);

    Customer::factory()->create([
        'first_name' => 'Pending',
        'last_name' => 'Review',
        'phone' => '0712000103',
        'kyc_status' => 'pending',
    ]);

    Livewire::test(LendingPanel::class)
        ->call('openDisbursementModal')
        ->assertSee('Amina Approved')
        ->assertSee('Legacy Verified')
        ->assertDontSee('Pending Review');
});

it('accepts the verified status alias when listing kyc customers through the api', function () {
    Sanctum::actingAs($this->user);

    $approved = Customer::factory()->create([
        'registered_by' => $this->user->id,
        'first_name' => 'Amina',
        'last_name' => 'Approved',
        'phone' => '0712000201',
        'kyc_status' => 'approved',
    ]);

    $legacyVerified = Customer::factory()->create([
        'registered_by' => $this->user->id,
        'first_name' => 'Legacy',
        'last_name' => 'Verified',
        'phone' => '0712000202',
        'kyc_status' => 'verified',
    ]);

    Customer::factory()->create([
        'registered_by' => $this->user->id,
        'first_name' => 'Pending',
        'last_name' => 'Review',
        'phone' => '0712000203',
        'kyc_status' => 'pending',
    ]);

    Verification::factory()->approved()->create(['customer_id' => $approved->id]);
    Verification::factory()->approved()->create(['customer_id' => $legacyVerified->id]);

    $response = $this->getJson('/api/v1/kyc/customers?status=verified');

    $response->assertOk()
        ->assertJsonPath('data.total', 2)
        ->assertJsonCount(2, 'data.data');

    $returnedIds = collect($response->json('data.data'))->pluck('id')->all();

    expect($returnedIds)->toContain($approved->id, $legacyVerified->id);
});

it('gives the same kyc score uplift to approved and verified customers', function () {
    $service = app(RiskAssessmentService::class);

    $approved = Customer::factory()->create([
        'kyc_status' => 'approved',
        'nida_number' => '11111111111111111111',
        'monthly_income' => 600000,
    ]);

    $legacyVerified = Customer::factory()->create([
        'kyc_status' => 'verified',
        'nida_number' => '22222222222222222222',
        'monthly_income' => 600000,
    ]);

    expect($service->generateCreditScore($approved))->toBe(80)
        ->and($service->generateCreditScore($legacyVerified))->toBe(80);
});
