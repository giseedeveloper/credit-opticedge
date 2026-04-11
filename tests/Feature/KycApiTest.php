<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Permission;
use App\Models\PhoneModel;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    $this->branch = Branch::factory()->create();
    $this->agent = User::factory()->create(['branch_id' => $this->branch->id]);
    $this->agent->givePermissionTo('loans.create');
    $this->brand = Brand::factory()->create(['name' => 'Samsung']);
    $this->phoneModel = PhoneModel::factory()->create([
        'brand_id' => $this->brand->id,
        'name' => 'Galaxy A15',
        'retail_price' => 380000,
        'specifications' => ['ram' => '6GB', 'storage' => '128GB', 'color' => 'Blue'],
    ]);
    $this->inventoryUnit = InventoryUnit::factory()->create([
        'phone_model_id' => $this->phoneModel->id,
        'branch_id' => $this->branch->id,
        'status' => 'hq_stock',
        'imei_1' => '123456789012345',
        'serial_number' => 'SN-GALAXY-001',
    ]);
    Sanctum::actingAs($this->agent);
});

// ─── Step 1: Device ───────────────────────────────────────────────────────────

it('step1 creates a draft customer and returns customer_id', function () {
    $response = $this->postJson('/api/v1/kyc/application/step1', [
        'brand_id' => $this->brand->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'weekly',
        'accessories' => [
            ['code' => 'screen_protector', 'name' => 'Screen Protector', 'quantity' => 1, 'offer_type' => 'free'],
            ['name' => 'Phone Cover', 'quantity' => 1, 'offer_type' => 'charged', 'unit_price' => 15000],
        ],
        'store_offer_notes' => 'Weekend promo included a free protector.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.step', 1)
        ->assertJsonStructure(['data' => ['customer_id', 'step']]);

    $draft = Customer::where('imei_number', $this->inventoryUnit->imei_1)
        ->where('phone_model_id', $this->phoneModel->id)
        ->where('inventory_unit_id', $this->inventoryUnit->id)
        ->latest()
        ->first();

    expect($draft)->not->toBeNull()
        ->and($draft?->device_accessories)->toHaveCount(2)
        ->and($draft?->store_offer_notes)->toBe('Weekend promo included a free protector.');
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

it('step1 can derive identifiers from a scanned payload when inventory is not linked', function () {
    $response = $this->postJson('/api/v1/kyc/application/step1', [
        'device_specs' => 'Samsung - Galaxy A15 - 6GB/128GB/Blue',
        'cash_price' => 380000,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'weekly',
        'device_scan' => [
            'raw_text' => 'IMEI: 356789012345678 Serial Number: SN-SCAN-001',
            'detectors' => ['text'],
        ],
    ]);

    $response->assertOk()->assertJsonPath('data.step', 1);

    $draft = Customer::latest()->first();

    expect($draft?->imei_number)->toBe('356789012345678')
        ->and($draft?->serial_number)->toBe('SN-SCAN-001')
        ->and($draft?->device_scan_metadata['selected_imei'])->toBe('356789012345678');
});

it('step1 rejects a scan payload that conflicts with the selected stock unit', function () {
    $this->postJson('/api/v1/kyc/application/step1', [
        'brand_id' => $this->brand->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'weekly',
        'device_scan' => [
            'raw_text' => 'IMEI: 356789012345679 Serial Number: SN-WRONG-001',
            'detectors' => ['text'],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['device_scan']);
});

it('device catalog endpoints return scoped brands, models and units', function () {
    $this->getJson('/api/v1/kyc/application/phone-countries')
        ->assertOk()
        ->assertJsonFragment(['iso' => 'TZ', 'dial_code' => '+255']);

    $this->getJson('/api/v1/kyc/application/device/brands')
        ->assertOk()
        ->assertJsonFragment(['id' => $this->brand->id, 'name' => 'Samsung']);

    $this->getJson('/api/v1/kyc/application/device/models?brand_id='.$this->brand->id)
        ->assertOk()
        ->assertJsonFragment(['id' => $this->phoneModel->id, 'name' => 'Galaxy A15']);

    $this->getJson('/api/v1/kyc/application/device/inventory?phone_model_id='.$this->phoneModel->id)
        ->assertOk()
        ->assertJsonFragment(['id' => $this->inventoryUnit->id, 'imei_1' => $this->inventoryUnit->imei_1]);
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
        'registered_by' => $this->agent->id,
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
        'phone_country' => 'TZ',
        'branch_id' => $this->branch->id,
        'region' => 'Dar es Salaam',
        'district' => 'Kinondoni',
    ])->assertOk()->assertJsonPath('data.step', 3);

    expect($draft->fresh()->phone)->toBe('+255712345678');
    expect($draft->fresh()->phone_metadata['phone']['country_iso'])->toBe('TZ');
    expect($draft->fresh()->region)->toBe('Dar es Salaam');
});

// ─── Step 5: NOK ─────────────────────────────────────────────────────────────

it('step5 saves next of kin details', function () {
    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'phone' => '+255712345678',
    ]);

    $this->postJson("/api/v1/kyc/application/{$customer->id}/step5", [
        'nok_name' => 'John Mwangi',
        'nok_phone' => '0754111222',
        'nok_phone_country' => 'TZ',
        'nok_relationship' => 'spouse',
    ])->assertOk()->assertJsonPath('data.step', 5);

    expect($customer->fresh()->nok_name)->toBe('John Mwangi');
    expect($customer->fresh()->nok_phone)->toBe('+255754111222');
    expect($customer->fresh()->phone_metadata['nok_phone']['country_iso'])->toBe('TZ');
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
    $customer = Customer::factory()->create(['registered_by' => $this->agent->id]);

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
