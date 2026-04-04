<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    $this->agent = User::factory()->create();
    $this->agent->givePermissionTo('loans.create');
    $this->branch = Branch::factory()->create();
    Sanctum::actingAs($this->agent);
});

// ─── Step 1: Device ───────────────────────────────────────────────────────────

it('step1 creates a draft customer and returns customer_id', function () {
    $response = $this->postJson('/api/v1/kyc/application/step1', [
        'device_specs' => 'Tecno Camon 30 – 8GB/256GB',
        'imei_number' => '123456789012345',
        'cash_price' => 450000,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'weekly',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.step', 1)
        ->assertJsonStructure(['data' => ['customer_id', 'step']]);

    expect(Customer::where('imei_number', '123456789012345')->exists())->toBeTrue();
});

it('step1 rejects invalid IMEI', function () {
    $this->postJson('/api/v1/kyc/application/step1', [
        'device_specs' => 'Device',
        'imei_number' => 'NOTANUMBER',
        'cash_price' => 100,
        'deposit_amount' => 0,
        'preferred_repayment' => 'weekly',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['imei_number']);
});

// ─── Step 2: Identity ─────────────────────────────────────────────────────────

it('step2 saves customer identity details', function () {
    $draft = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'first_name' => '_draft_',
        'last_name' => '_draft_',
        'phone' => '_draft_'.uniqid(),
        'nida_number' => null,
    ]);

    $nida = str_pad((string) rand(1, 9), 20, '0', STR_PAD_LEFT);

    $response = $this->postJson("/api/v1/kyc/application/{$draft->id}/step2", [
        'first_name' => 'Amina',
        'last_name' => 'Juma',
        'gender' => 'female',
        'nida_number' => $nida,
        'id_type' => 'nida',
        'id_front_photo' => UploadedFile::fake()->image('id_front.jpg'),
        'id_back_photo' => UploadedFile::fake()->image('id_back.jpg'),
        'headshot_photo' => UploadedFile::fake()->image('selfie.jpg'),
    ]);

    $response->assertOk()->assertJsonPath('data.step', 2);
    expect($draft->fresh()->first_name)->toBe('Amina');
    expect($draft->fresh()->nida_number)->toBe($nida);
});

it('step2 rejects duplicate NIDA', function () {
    $existing = Customer::factory()->create(['nida_number' => '11111111111111111111']);
    $draft = Customer::factory()->create([
        'first_name' => '_draft_',
        'last_name' => '_draft_',
        'phone' => '_draft_'.uniqid(),
        'nida_number' => null,
    ]);

    $this->postJson("/api/v1/kyc/application/{$draft->id}/step2", [
        'first_name' => 'Test',
        'last_name' => 'User',
        'gender' => 'male',
        'nida_number' => '11111111111111111111',
        'id_type' => 'nida',
        'id_front_photo' => UploadedFile::fake()->image('id.jpg'),
        'id_back_photo' => UploadedFile::fake()->image('id.jpg'),
        'headshot_photo' => UploadedFile::fake()->image('selfie.jpg'),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['nida_number']);
});

// ─── Step 3: Contact ──────────────────────────────────────────────────────────

it('step3 saves contact and location', function () {
    $draft = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'phone' => '_draft_'.uniqid(),
    ]);

    $this->postJson("/api/v1/kyc/application/{$draft->id}/step3", [
        'phone' => '0712345678',
        'branch_id' => $this->branch->id,
        'region' => 'Dar es Salaam',
        'district' => 'Kinondoni',
    ])->assertOk()->assertJsonPath('data.step', 3);

    expect($draft->fresh()->phone)->toBe('0712345678');
    expect($draft->fresh()->region)->toBe('Dar es Salaam');
});

// ─── Step 5: NOK ─────────────────────────────────────────────────────────────

it('step5 saves next of kin details', function () {
    $customer = Customer::factory()->create(['registered_by' => $this->agent->id]);

    $this->postJson("/api/v1/kyc/application/{$customer->id}/step5", [
        'nok_name' => 'John Mwangi',
        'nok_phone' => '0754111222',
        'nok_relationship' => 'spouse',
    ])->assertOk()->assertJsonPath('data.step', 5);

    expect($customer->fresh()->nok_name)->toBe('John Mwangi');
});

// ─── Step 6: Consent ──────────────────────────────────────────────────────────

it('step6 records consent and timestamp', function () {
    $customer = Customer::factory()->create(['registered_by' => $this->agent->id]);

    $this->postJson("/api/v1/kyc/application/{$customer->id}/step6", [
        'terms_accepted' => true,
        'data_consent_accepted' => true,
        'call_consent_accepted' => true,
    ])->assertOk()->assertJsonPath('data.step', 6);

    $fresh = $customer->fresh();
    expect($fresh->terms_accepted)->toBeTrue();
    expect($fresh->data_consent_accepted)->toBeTrue();
    expect($fresh->call_consent_accepted)->toBeTrue();
    expect($fresh->consent_timestamp)->not->toBeNull();
});

it('step6 rejects missing consent', function () {
    $customer = Customer::factory()->create();

    $this->postJson("/api/v1/kyc/application/{$customer->id}/step6", [
        'terms_accepted' => false,
        'data_consent_accepted' => true,
        'call_consent_accepted' => true,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['terms_accepted']);
});

// ─── Step 7: Submit ───────────────────────────────────────────────────────────

it('step7 submits application and runs auto-checks', function () {
    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'branch_id' => $this->branch->id,
        'nok_name' => 'Jane Doe',
        'nok_phone' => '0712000000',
        'nok_relationship' => 'parent',
        'terms_accepted' => true,
        'data_consent_accepted' => true,
        'call_consent_accepted' => true,
        'consent_timestamp' => now(),
    ]);

    $response = $this->postJson("/api/v1/kyc/application/{$customer->id}/step7", [
        'fo_notes' => 'Customer seems genuine',
        'application_source' => 'walk_in',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['customer_id', 'verification_id', 'auto_check_status', 'auto_check_results']]);

    expect(Verification::where('customer_id', $customer->id)->exists())->toBeTrue();
});

it('step7 blocks submission when consent is missing', function () {
    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'nok_name' => 'Jane Doe',
        'nok_phone' => '0712000001',
        'nok_relationship' => 'parent',
        'terms_accepted' => false,
    ]);

    $this->postJson("/api/v1/kyc/application/{$customer->id}/step7", [])
        ->assertUnprocessable();
});

// ─── Status ───────────────────────────────────────────────────────────────────

it('applicationStatus returns kyc state', function () {
    $customer = Customer::factory()->create(['registered_by' => $this->agent->id]);

    $this->getJson("/api/v1/kyc/application/{$customer->id}/status")
        ->assertOk()
        ->assertJsonStructure(['data' => ['customer_id', 'kyc_status', 'kyc_stage']]);
});
