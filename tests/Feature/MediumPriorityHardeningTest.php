<?php

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\InventoryUnit;
use App\Models\Permission;
use App\Models\PhoneModel;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TraFiscalReceiptService;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['devices.view', 'loans.create'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

it('exposes refurbishment API for recovered units', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('devices.view');
    Sanctum::actingAs($user);

    $brand = Brand::factory()->create();
    $model = PhoneModel::factory()->create(['brand_id' => $brand->id]);
    $dealer = Dealer::factory()->create();
    $unit = InventoryUnit::factory()->create([
        'phone_model_id' => $model->id,
        'dealer_id' => $dealer->id,
        'status' => 'recovered',
        'repair_cost' => 1_000,
    ]);

    $this->postJson("/api/v1/refurbishment/units/{$unit->id}/refurbish", [
        'part_cost' => 2_500,
        'grading' => 'Grade B',
        'notes' => 'Screen replacement',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'available');

    expect((float) $unit->fresh()->repair_cost)->toBe(3_500.0)
        ->and(Transaction::where('type', 'operational_cost')->count())->toBe(1);
});

it('returns deprecated empty branches payload for legacy clients', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('loans.create');
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/kyc/branches')
        ->assertOk()
        ->assertJsonPath('data.items', [])
        ->assertJsonPath('data.deprecated', true)
        ->assertJsonPath('data.replacement', 'dealer');
});

it('validates step1 device through form request rules', function () {
    $agent = User::factory()->create();
    $agent->givePermissionTo('loans.create');
    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/kyc/application/step1', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['deposit_amount', 'preferred_repayment']);
});

it('validates step2 identity through form request rules', function () {
    $agent = User::factory()->create();
    $agent->givePermissionTo('loans.create');

    $draft = Customer::factory()->create([
        'registered_by' => $agent->id,
        'first_name' => '_draft_',
        'last_name' => '_draft_',
        'phone' => '_draft_'.uniqid(),
        'nida_number' => null,
    ]);

    Sanctum::actingAs($agent);

    $this->postJson("/api/v1/kyc/application/{$draft->id}/step2", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name', 'gender', 'nida_number', 'id_type']);
});

it('prepares TRA fiscal receipt payload with vat fields', function () {
    $transaction = Transaction::factory()->create([
        'type' => 'repayment',
        'entry_type' => 'credit',
        'amount' => 50_000,
        'channel' => 'mpesa',
    ]);

    $result = app(TraFiscalReceiptService::class)->issueForTransaction($transaction);

    expect($result['status'])->toBe('payload_ready')
        ->and($result['payload'])->toHaveKeys([
            'receipt_number',
            'amount',
            'vat',
            'currency',
            'fiscal_status',
        ])
        ->and($result['payload']['currency'])->toBe('TZS');
});

it('validates payment request through form request rules', function () {
    $agent = User::factory()->create();
    $agent->givePermissionTo('loans.create');
    Sanctum::actingAs($agent);

    $draft = Customer::factory()->create([
        'registered_by' => $agent->id,
        'deposit_amount' => 50_000,
    ]);

    $this->postJson("/api/v1/kyc/application/{$draft->id}/payment/request", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_phone', 'payment_phone_country']);
});
