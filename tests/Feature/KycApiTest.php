<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\PhoneModel;
use App\Models\RepaymentSchedule;
use App\Models\SelcomPaymentRequest;
use App\Models\SystemDocument;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Verification;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Storage::fake('public');
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web']);
    $this->branch = Branch::factory()->create();
    $this->agent = User::factory()->create(['branch_id' => $this->branch->id]);
    $this->agent->givePermissionTo(['loans.create', 'loans.view']);
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
    Config::set('services.selcom.base_url', 'https://apigw.selcommobile.com/v1');
    Config::set('services.selcom.vendor', 'TILL61163918');
    Config::set('services.selcom.api_key', 'TILL61163918-cd74f96661ab40dc986bfc87a448acd8');
    Config::set('services.selcom.api_secret', 'cfc165-ca2991-472f84-edd218-1fafe1-65');
    Sanctum::actingAs($this->agent);
});

it('public media streams public storage files with cors-friendly headers', function () {
    Storage::disk('public')->put('kyc/headshot/test-headshot.jpg', 'fake-image-bytes');

    $this->get('/api/v1/public-media?path=kyc/headshot/test-headshot.jpg')
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', '*');
});

it('public media rejects invalid traversal paths', function () {
    $this->get('/api/v1/public-media?path=../.env')->assertNotFound();
});

it('returns the simplified three-stage kyc flow contract', function () {
    $this->getJson('/api/v1/kyc/application/stage-flow')
        ->assertOk()
        ->assertJsonPath('data.version', 'kyc_3_stage_v1')
        ->assertJsonPath('data.total_stages', 3)
        ->assertJsonPath('data.stages.0.label', 'Device & Offer')
        ->assertJsonPath('data.stages.1.label', 'Customer & Verification')
        ->assertJsonPath('data.stages.2.label', 'Payment, Agreement & Handover')
        ->assertJsonPath('data.stages.1.legacy_steps', [2, 3, 4, 5, 6]);
});

// ─── Step 1: Device ───────────────────────────────────────────────────────────

it('step1 creates a draft customer and returns customer_id', function () {
    $response = $this->postJson('/api/v1/kyc/application/step1', [
        'brand_id' => $this->brand->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'weekly',
        'loan_interest_rate' => 4.25,
        'loan_interest_type' => 'flat',
        'loan_duration_weeks' => 40,
        'loan_grace_period_days' => 5,
        'accessories' => [
            ['code' => 'screen_protector', 'name' => 'Screen Protector', 'quantity' => 1, 'offer_type' => 'free'],
            ['name' => 'Phone Cover', 'quantity' => 1, 'offer_type' => 'charged', 'unit_price' => 15000],
        ],
        'store_offer_notes' => 'Weekend promo included a free protector.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.step', 1)
        ->assertJsonPath('data.stage', 1)
        ->assertJsonPath('data.flow.total_stages', 3)
        ->assertJsonStructure(['data' => ['customer_id', 'step', 'stage', 'flow']]);

    $draft = Customer::where('imei_number', $this->inventoryUnit->imei_1)
        ->where('phone_model_id', $this->phoneModel->id)
        ->where('inventory_unit_id', $this->inventoryUnit->id)
        ->latest()
        ->first();

    expect($draft)->not->toBeNull()
        ->and($draft?->device_accessories)->toHaveCount(2)
        ->and($draft?->store_offer_notes)->toBe('Weekend promo included a free protector.')
        ->and((float) $draft?->loan_interest_rate)->toBe(4.25)
        ->and($draft?->loan_interest_type)->toBe('flat')
        ->and($draft?->loan_duration_weeks)->toBe(40)
        ->and($draft?->loan_grace_period_days)->toBe(5)
        ->and($draft?->metadata['loan_terms']['source'])->toBe('kyc_capture');
});

it('step1 falls back to default loan terms when omitted', function () {
    $this->postJson('/api/v1/kyc/application/step1', [
        'brand_id' => $this->brand->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'monthly',
    ])->assertOk();

    $draft = Customer::query()->latest()->first();

    expect($draft)->not->toBeNull()
        ->and($draft?->loan_interest_type)->not->toBeNull()
        ->and($draft?->loan_duration_weeks)->toBeGreaterThan(0)
        ->and($draft?->metadata['loan_terms']['source'] ?? null)->toBe('kyc_capture');
});

it('step1 auto-links vendor store context from the selected stock unit', function () {
    $vendor = Vendor::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Mlimani Dealer Store',
    ]);

    $vendorUnit = InventoryUnit::factory()->create([
        'phone_model_id' => $this->phoneModel->id,
        'branch_id' => $this->branch->id,
        'vendor_id' => $vendor->id,
        'status' => 'vendor_stock',
        'imei_1' => '223456789012345',
        'serial_number' => 'SN-VENDOR-001',
    ]);

    $this->postJson('/api/v1/kyc/application/step1', [
        'brand_id' => $this->brand->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $vendorUnit->id,
        'deposit_amount' => 65000,
        'preferred_repayment' => 'weekly',
        'loan_interest_rate' => 3.75,
        'loan_interest_type' => 'flat',
        'loan_duration_weeks' => 52,
        'loan_grace_period_days' => 3,
    ])->assertOk();

    $draft = Customer::query()->whereKey(
        Customer::query()->latest()->value('id')
    )->first();

    expect($draft)->not->toBeNull()
        ->and($draft?->vendor_id)->toBe($vendor->id)
        ->and($draft?->branch_id)->toBe($this->branch->id);
});

it('step1 rejects invalid IMEI', function () {
    $this->postJson('/api/v1/kyc/application/step1', [
        'device_specs' => 'Device',
        'imei_number' => 'NOTANUMBER',
        'cash_price' => 100,
        'deposit_amount' => 0,
        'preferred_repayment' => 'weekly',
        'loan_interest_rate' => 3.75,
        'loan_interest_type' => 'flat',
        'loan_duration_weeks' => 52,
        'loan_grace_period_days' => 3,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['imei_number']);
});

it('step1 can derive identifiers from a scanned payload when inventory is not linked', function () {
    $response = $this->postJson('/api/v1/kyc/application/step1', [
        'device_specs' => 'Samsung - Galaxy A15 - 6GB/128GB/Blue',
        'cash_price' => 380000,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'weekly',
        'loan_interest_rate' => 3.75,
        'loan_interest_type' => 'flat',
        'loan_duration_weeks' => 52,
        'loan_grace_period_days' => 3,
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
        'loan_interest_rate' => 3.75,
        'loan_interest_type' => 'flat',
        'loan_duration_weeks' => 52,
        'loan_grace_period_days' => 3,
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

it('step3 keeps the vendor branch context without forcing branch reselection', function () {
    $vendor = Vendor::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Kijitonyama Vendor',
    ]);

    $draft = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'vendor_id' => $vendor->id,
        'branch_id' => $this->branch->id,
        'phone' => '_draft_'.uniqid(),
    ]);

    $this->postJson("/api/v1/kyc/application/{$draft->id}/step3", [
        'phone' => '0712345678',
        'phone_country' => 'TZ',
        'region' => 'Dar es Salaam',
        'district' => 'Ilala',
    ])->assertOk()->assertJsonPath('data.step', 3);

    expect($draft->fresh()->branch_id)->toBe($this->branch->id)
        ->and($draft->fresh()->vendor_id)->toBe($vendor->id);
});

it('stage2 captures customer verification in one request and keeps vendor branch locked', function () {
    $vendor = Vendor::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Kariakoo Dealer',
    ]);

    $draft = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'branch_id' => $this->branch->id,
        'vendor_id' => $vendor->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'device_specs' => 'Samsung Galaxy A15 6GB/128GB Blue',
        'imei_number' => '353456789012345',
        'cash_price' => 380000,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'weekly',
        'loan_interest_rate' => 4.25,
        'loan_interest_type' => 'flat',
        'loan_duration_weeks' => 52,
        'loan_grace_period_days' => 5,
        'first_name' => '_draft_',
        'last_name' => '_draft_',
        'phone' => '_draft_'.uniqid(),
        'nida_number' => null,
    ]);

    $response = $this->postJson("/api/v1/kyc/application/{$draft->id}/stage2", [
        'first_name' => 'Asha',
        'middle_name' => 'Rehema',
        'last_name' => 'Moshi',
        'gender' => 'female',
        'date_of_birth' => '1994-05-12',
        'nida_number' => '20012345678901234567',
        'id_type' => 'nida',
        'id_front_photo' => UploadedFile::fake()->image('id-front.jpg'),
        'id_back_photo' => UploadedFile::fake()->image('id-back.jpg'),
        'headshot_photo' => UploadedFile::fake()->image('headshot.jpg'),
        'client_fo_photo' => UploadedFile::fake()->image('client-fo.jpg'),
        'phone' => '0712345678',
        'phone_country' => 'TZ',
        'alt_phone' => '0712345679',
        'alt_phone_country' => 'TZ',
        'email' => 'asha@example.test',
        'region' => 'Dar es Salaam',
        'district' => 'Ilala',
        'address' => 'Kariakoo Market',
        'landmark' => 'Clock tower',
        'occupation' => 'Trader',
        'employer' => 'Self employed',
        'work_location' => 'Kariakoo',
        'monthly_income' => 750000,
        'monthly_expenses' => 250000,
        'income_payment_cycle' => 'weekly',
        'duration_at_work' => '3 years',
        'business_photo' => UploadedFile::fake()->image('business.jpg'),
        'nok_name' => 'Rehema Moshi',
        'nok_phone' => '0754111222',
        'nok_phone_country' => 'TZ',
        'nok_relationship' => 'sister',
        'terms_accepted' => true,
        'data_consent_accepted' => true,
        'call_consent_accepted' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.stage', 2)
        ->assertJsonPath('data.next_stage', 3)
        ->assertJsonPath('data.flow.resume_stage', 3)
        ->assertJsonPath('data.flow.stages.1.status', 'completed');

    $fresh = $draft->fresh();

    expect($fresh->first_name)->toBe('Asha')
        ->and($fresh->branch_id)->toBe($this->branch->id)
        ->and($fresh->vendor_id)->toBe($vendor->id)
        ->and($fresh->phone)->toBe('+255712345678')
        ->and($fresh->alt_phone)->toBe('+255712345679')
        ->and($fresh->nok_phone)->toBe('+255754111222')
        ->and($fresh->monthly_income)->toBe('750000.00')
        ->and($fresh->terms_accepted)->toBeTrue()
        ->and($fresh->kyc_stage)->toBe(2)
        ->and($fresh->id_front_photo_path)->not->toBeNull()
        ->and($fresh->id_back_photo_path)->not->toBeNull()
        ->and($fresh->headshot_photo_path)->not->toBeNull()
        ->and($fresh->business_photo_path)->not->toBeNull();
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

it('payment request sends a selcom prompt for the application draft', function () {
    Http::fake([
        'https://apigw.selcommobile.com/v1/checkout/create-order-minimal' => Http::response([
            'result' => 'SUCCESS',
            'resultcode' => '000',
            'reference' => 'SEL-REF-API-001',
            'data' => [[
                'buyer_uuid' => 'buyer-001',
                'payment_token' => 'token-001',
                'payment_gateway_url' => 'https://selcom.test/pay/token-001',
            ]],
        ]),
        // Real Selcom often returns SUCCESS/000 for "push accepted" while payment_status stays PENDING until USSD completes.
        'https://apigw.selcommobile.com/v1/checkout/wallet-payment' => Http::response([
            'result' => 'SUCCESS',
            'resultcode' => '000',
            'payment_status' => 'PENDING',
            'reference' => 'SEL-REF-API-001',
            'channel' => 'MPESA',
        ]),
    ]);

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'application_draft_reference' => 'draft-api-001',
        'first_name' => 'Amina',
        'last_name' => 'Juma',
        'phone' => '+255712345678',
        'deposit_amount' => 50000,
    ]);

    $response = $this->postJson("/api/v1/kyc/application/{$customer->id}/payment/request", [
        'payment_phone' => '0712345678',
        'payment_phone_country' => 'TZ',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.payment.status', 'pending')
        ->assertJsonPath('data.payment.phone', '255712345678');

    expect($customer->fresh()->deposit_payment_status)->toBe('pending')
        ->and($customer->fresh()->deposit_payment_reference)->toBe('SEL-REF-API-001');

    Http::assertSent(function (Request $request): bool {
        if (! str_contains($request->url(), '/checkout/create-order-minimal')) {
            return false;
        }

        $payload = json_decode($request->body(), true);

        return is_array($payload)
            && ! array_key_exists('payment_methods', $payload)
            && ($payload['no_of_items'] ?? null) === 1;
    });
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/checkout/wallet-payment'));
});

it('payment request returns a clear configuration error when selcom credentials are missing', function () {
    Config::set('services.selcom.vendor', null);
    Config::set('services.selcom.api_key', null);
    Config::set('services.selcom.api_secret', null);

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'application_draft_reference' => 'draft-api-no-selcom',
        'first_name' => 'Amina',
        'last_name' => 'Juma',
        'phone' => '+255712345678',
        'deposit_amount' => 50000,
    ]);

    $this->postJson("/api/v1/kyc/application/{$customer->id}/payment/request", [
        'payment_phone' => '0712345678',
        'payment_phone_country' => 'TZ',
    ])->assertStatus(503)
        ->assertJsonPath('message', 'Selcom Checkout is not configured. Set SELCOM_VENDOR, SELCOM_API_KEY, and SELCOM_API_SECRET in the backend .env file first.');
});

// ─── Step 7: Submit ───────────────────────────────────────────────────────────

it('step7 submits application with payment, agreement and signatures', function () {
    $agreement = SystemDocument::factory()->create([
        'key' => 'kyc_customer_agreement',
        'disk' => 'public',
        'path' => 'agreements/mobile-agreement.pdf',
        'is_active' => true,
        'uploaded_by' => $this->agent->id,
    ]);

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'branch_id' => $this->branch->id,
        'application_draft_reference' => 'draft-api-002',
        'first_name' => 'Amina',
        'last_name' => 'Juma',
        'phone' => '+255712345678',
        'nida_number' => '11112222333344445555',
        'monthly_income' => 650000,
        'nok_name' => 'Jane Doe',
        'nok_phone' => '0712000000',
        'nok_relationship' => 'parent',
        'terms_accepted' => true,
        'data_consent_accepted' => true,
        'call_consent_accepted' => true,
        'consent_timestamp' => now(),
    ]);

    SelcomPaymentRequest::factory()->create([
        'customer_id' => $customer->id,
        'initiated_by' => $this->agent->id,
        'draft_reference' => 'draft-api-002',
        'status' => 'completed',
        'payment_status' => 'COMPLETED',
        'result' => 'SUCCESS',
        'resultcode' => '000',
        'selcom_reference' => 'SEL-PAID-002',
        'amount' => 50000,
        'paid_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/kyc/application/{$customer->id}/step7", [
        'fo_notes' => 'Customer seems genuine',
        'application_source' => 'walk_in',
        'agreement_decision' => 'yes',
        'customer_signature' => apiKycSignatureDataUrl(),
        'fo_signature' => apiKycSignatureDataUrl(),
        'etr_receipt_photo' => UploadedFile::fake()->image('etr.jpg', 900, 600),
        'asset_handover_list' => UploadedFile::fake()->create('handover.pdf', 80, 'application/pdf'),
        'asset_handover_notes' => 'Phone, charger and free accessories handed over.',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['customer_id', 'verification_id', 'auto_check_status', 'auto_check_results', 'payment', 'agreement', 'release']]);

    expect(Verification::where('customer_id', $customer->id)->exists())->toBeTrue()
        ->and($customer->fresh()->agreement_document_id)->toBe($agreement->id)
        ->and($customer->fresh()->agreement_accepted)->toBeTrue()
        ->and($customer->fresh()->deposit_payment_status)->toBe('completed')
        ->and($customer->fresh()->asset_release_status)->toBe('pending')
        ->and($customer->fresh()->customer_signature_path)->not->toBeNull()
        ->and($customer->fresh()->fo_signature_path)->not->toBeNull()
        ->and($customer->fresh()->etr_receipt_path)->not->toBeNull();
});

it('step7 blocks submission when consent is missing', function () {
    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'nok_name' => 'Jane Doe',
        'nok_phone' => '0712000001',
        'nok_relationship' => 'parent',
        'terms_accepted' => false,
    ]);

    $this->postJson("/api/v1/kyc/application/{$customer->id}/step7", [
        'agreement_decision' => 'yes',
        'customer_signature' => apiKycSignatureDataUrl(),
        'fo_signature' => apiKycSignatureDataUrl(),
        'asset_handover_list' => UploadedFile::fake()->create('handover.pdf', 80, 'application/pdf'),
    ])
        ->assertUnprocessable();
});

// ─── Status ───────────────────────────────────────────────────────────────────

it('applicationStatus returns payment, agreement and release context', function () {
    $agreement = SystemDocument::factory()->create([
        'key' => 'kyc_customer_agreement',
        'disk' => 'public',
        'path' => 'agreements/status-agreement.pdf',
        'is_active' => true,
        'uploaded_by' => $this->agent->id,
    ]);

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'application_draft_reference' => 'draft-api-003',
        'agreement_document_id' => $agreement->id,
        'agreement_accepted' => true,
        'deposit_payment_status' => 'completed',
        'asset_release_status' => 'pending',
    ]);

    SelcomPaymentRequest::factory()->create([
        'customer_id' => $customer->id,
        'initiated_by' => $this->agent->id,
        'draft_reference' => 'draft-api-003',
        'status' => 'completed',
        'payment_status' => 'COMPLETED',
        'selcom_reference' => 'SEL-STATUS-003',
        'amount' => 60000,
        'paid_at' => now(),
    ]);

    $this->getJson("/api/v1/kyc/application/{$customer->id}/status")
        ->assertOk()
        ->assertJsonPath('data.payment.status', 'completed')
        ->assertJsonPath('data.agreement.accepted', true)
        ->assertJsonPath('data.release.status', 'pending')
        ->assertJsonPath('data.flow.total_stages', 3);
});

it('customer detail includes payment, agreement and release data', function () {
    $agreement = SystemDocument::factory()->create([
        'key' => 'kyc_customer_agreement',
        'disk' => 'public',
        'path' => 'agreements/detail-agreement.pdf',
        'is_active' => true,
        'uploaded_by' => $this->agent->id,
    ]);

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'application_draft_reference' => 'draft-api-004',
        'agreement_document_id' => $agreement->id,
        'agreement_accepted' => true,
        'deposit_payment_status' => 'completed',
        'asset_release_status' => 'pending',
    ]);

    SelcomPaymentRequest::factory()->create([
        'customer_id' => $customer->id,
        'initiated_by' => $this->agent->id,
        'draft_reference' => 'draft-api-004',
        'status' => 'completed',
        'payment_status' => 'COMPLETED',
        'selcom_reference' => 'SEL-DETAIL-004',
        'amount' => 60000,
        'paid_at' => now(),
    ]);

    $this->getJson("/api/v1/kyc/customers/{$customer->id}")
        ->assertOk()
        ->assertJsonPath('data.payment.status', 'completed')
        ->assertJsonPath('data.agreement.accepted', true)
        ->assertJsonPath('data.release.status', 'pending')
        ->assertJsonPath('data.flow.total_stages', 3);
});

it('treats deposit as paid when Selcom request is completed but customer snapshot is still pending', function () {
    $draftRef = 'draft-stale-selcom-'.uniqid();

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'application_draft_reference' => $draftRef,
        'deposit_payment_status' => 'pending',
        'deposit_paid_at' => null,
        'deposit_payment_reference' => null,
    ]);

    SelcomPaymentRequest::factory()->create([
        'customer_id' => $customer->id,
        'initiated_by' => $this->agent->id,
        'draft_reference' => $draftRef,
        'status' => 'completed',
        'payment_status' => 'COMPLETED',
        'selcom_reference' => 'SEL-STALE-SNAP',
        'amount' => 2000,
        'paid_at' => now(),
    ]);

    expect($customer->fresh()->hasSuccessfulDepositPayment())->toBeTrue();
});

it('treats deposit as paid when Selcom completed row draft_reference differs from customer draft ref', function () {
    $customerDraft = 'customer-draft-'.uniqid();
    $paymentDraft = 'payment-draft-other-'.uniqid();

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'application_draft_reference' => $customerDraft,
        'deposit_payment_status' => 'pending',
        'deposit_paid_at' => null,
        'deposit_payment_reference' => null,
    ]);

    SelcomPaymentRequest::factory()->create([
        'customer_id' => $customer->id,
        'initiated_by' => $this->agent->id,
        'draft_reference' => $paymentDraft,
        'status' => 'completed',
        'payment_status' => 'COMPLETED',
        'selcom_reference' => 'SEL-DRAFT-MISMATCH',
        'amount' => 2000,
        'paid_at' => now(),
    ]);

    expect($customer->fresh()->hasSuccessfulDepositPayment())->toBeTrue();
});

it('customer detail exposes resume metadata and vendor context for draft editing', function () {
    $vendor = Vendor::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sinza Dealer',
    ]);

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'branch_id' => $this->branch->id,
        'vendor_id' => $vendor->id,
        'first_name' => 'Asha',
        'last_name' => 'Moshi',
        'nida_number' => '12345678901234567890',
        'phone' => '+255712345678',
        'monthly_income' => 420000,
    ]);

    $this->getJson("/api/v1/kyc/customers/{$customer->id}")
        ->assertOk()
        ->assertJsonPath('data.kyc_status', 'draft')
        ->assertJsonPath('data.can_resume_draft', true)
        ->assertJsonPath('data.resume_step', 5)
        ->assertJsonPath('data.resume_stage', 1)
        ->assertJsonPath('data.vendor.name', 'Sinza Dealer');
});

it('allows fo to upload handover checklist when the customer record is missing the file', function () {
    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'branch_id' => $this->branch->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'kyc_status' => 'approved',
        'asset_release_status' => 'pending',
        'asset_handover_list_path' => null,
    ]);

    $file = UploadedFile::fake()->create('handover.pdf', 200, 'application/pdf');

    $this->post("/api/v1/kyc/customers/{$customer->id}/handover-checklist", [
        'asset_handover_list' => $file,
    ])
        ->assertOk()
        ->assertJsonPath('data.release.status', 'pending');

    expect($customer->fresh()->asset_handover_list_path)->not->toBeNull()
        ->and(str_contains((string) $customer->fresh()->asset_handover_list_path, 'kyc/handover'))->toBeTrue();
});

it('release asset marks the stock unit as assigned', function () {
    $agreement = SystemDocument::factory()->create([
        'key' => 'kyc_customer_agreement',
        'disk' => 'public',
        'path' => 'agreements/release-agreement.pdf',
        'is_active' => true,
        'uploaded_by' => $this->agent->id,
    ]);

    Storage::disk('public')->put('kyc/customer-signatures/api-customer.png', base64_decode(apiKycRawSignature(), true));
    Storage::disk('public')->put('kyc/fo-signatures/api-fo.png', base64_decode(apiKycRawSignature(), true));
    Storage::disk('public')->put('kyc/handover/api-release.pdf', 'handover checklist');

    $customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'branch_id' => $this->branch->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'agreement_document_id' => $agreement->id,
        'agreement_accepted' => true,
        'customer_signature_path' => 'kyc/customer-signatures/api-customer.png',
        'fo_signature_path' => 'kyc/fo-signatures/api-fo.png',
        'asset_handover_list_path' => 'kyc/handover/api-release.pdf',
        'deposit_payment_status' => 'completed',
        'cash_price' => 380000,
        'deposit_amount' => 50000,
        'preferred_repayment' => 'weekly',
        'asset_release_status' => 'pending',
        'kyc_status' => 'approved',
    ]);

    $response = $this->postJson("/api/v1/kyc/customers/{$customer->id}/release-asset");

    $response->assertOk()
        ->assertJsonPath('data.release.status', 'released')
        ->assertJsonPath('data.loan.status', 'active');

    $loan = Loan::query()->where('customer_id', $customer->id)->first();

    expect($customer->fresh()->asset_release_status)->toBe('released')
        ->and($this->inventoryUnit->fresh()->status)->toBe('assigned')
        ->and($loan)->not->toBeNull()
        ->and($loan?->repayment_frequency)->toBe('weekly')
        ->and($loan?->status)->toBe('active')
        ->and(RepaymentSchedule::query()->where('loan_id', $loan?->id)->count())->toBeGreaterThan(0);
});

it('save-draft marks fo timestamp and draft tab lists only explicit drafts', function () {
    $withExplicit = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'first_name' => 'Explicit',
        'last_name' => 'Draft',
        'phone' => '0712000301',
        'kyc_fo_saved_as_draft_at' => now()->subHour(),
    ]);

    $inProgressOnly = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'first_name' => 'In',
        'last_name' => 'Progress',
        'phone' => '0712000302',
        'kyc_fo_saved_as_draft_at' => null,
    ]);

    $this->getJson('/api/v1/kyc/customers?status=draft')
        ->assertOk()
        ->assertJsonPath('data.total', 1);

    $ids = collect($this->getJson('/api/v1/kyc/customers?status=draft')->json('data.data'))
        ->pluck('id')
        ->all();

    expect($ids)->toContain($withExplicit->id)
        ->not->toContain($inProgressOnly->id);

    $this->postJson("/api/v1/kyc/application/{$inProgressOnly->id}/save-draft")
        ->assertOk()
        ->assertJsonPath('data.customer_id', $inProgressOnly->id);

    expect($inProgressOnly->fresh()->kyc_fo_saved_as_draft_at)->not->toBeNull();

    $this->getJson('/api/v1/kyc/dashboard')
        ->assertOk()
        ->assertJsonPath('data.drafts', 2);
});

it('save-draft rejects placeholder customers and submitted applications', function () {
    $placeholder = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'first_name' => '_draft_',
        'last_name' => '_draft_',
        'phone' => '_draft_'.uniqid(),
    ]);

    $this->postJson("/api/v1/kyc/application/{$placeholder->id}/save-draft")
        ->assertStatus(422);

    $submitted = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'first_name' => 'Done',
        'last_name' => 'Submit',
        'phone' => '0712000401',
    ]);

    Verification::factory()->create([
        'customer_id' => $submitted->id,
        'type' => 'kyc',
        'status' => 'pending',
    ]);

    $this->postJson("/api/v1/kyc/application/{$submitted->id}/save-draft")
        ->assertStatus(422);
});

function apiKycSignatureDataUrl(): string
{
    return 'data:image/png;base64,'.apiKycRawSignature();
}

function apiKycRawSignature(): string
{
    return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sWwaP8AAAAASUVORK5CYII=';
}
