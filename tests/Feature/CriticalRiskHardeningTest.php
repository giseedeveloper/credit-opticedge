<?php

use App\Jobs\SendSmsJob;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Permission;
use App\Models\User;
use App\Models\Verification;
use App\Services\CustomerLoanProvisioningService;
use App\Services\FaceMatchService;
use App\Services\Integrations\IntegrationGatewayManager;
use App\Services\LoanProvisioningGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web']);

    $this->dealer = Dealer::factory()->create();
    $this->reviewer = User::factory()->create(['dealer_id' => $this->dealer->id]);
    $this->reviewer->givePermissionTo(['loans.create', 'loans.view']);
});

test('face match returns failed when required but url is missing', function () {
    Config::set('services.face_match.required', true);
    Config::set('services.face_match.url', null);

    $result = app(FaceMatchService::class)->match(
        UploadedFile::fake()->image('id.jpg'),
        UploadedFile::fake()->image('face.jpg'),
    );

    expect($result['status'])->toBe('failed')
        ->and($result['reason'])->toContain('not configured');
});

test('sms job does not throw when driver is not implemented', function () {
    Config::set('services.sms.driver', 'vendor_xyz');

    $job = new SendSmsJob('255700000001', 'Test message');
    $job->handle(app(IntegrationGatewayManager::class));

    expect(true)->toBeTrue();
});

test('http sms driver sends when url is configured', function () {
    Http::fake([
        'https://sms.test/*' => Http::response(['ok' => true], 200),
    ]);

    Config::set('services.sms.driver', 'http');
    Config::set('services.sms.http.url', 'https://sms.test/send');

    $gateway = app(IntegrationGatewayManager::class)->sms('http');

    expect($gateway->send('255700000001', 'Hello'))->toBeTrue();
});

test('loan provisioning guard blocks manual disbursement by default', function () {
    $customer = Customer::factory()->create([
        'dealer_id' => $this->dealer->id,
        'kyc_status' => 'approved',
        'asset_release_status' => 'released',
        'deposit_payment_status' => 'completed',
        'cash_price' => 500000,
        'preferred_repayment' => 'monthly',
    ]);

    Config::set('credit.allow_manual_disbursement', false);

    expect(app(LoanProvisioningGuard::class)->canProvision($customer, LoanProvisioningGuard::CHANNEL_MANUAL_WEB))
        ->toBeFalse();
});

test('hq can approve kyc stage 1 via rest api', function () {
    Sanctum::actingAs($this->reviewer);

    $customer = Customer::factory()->create([
        'dealer_id' => $this->dealer->id,
        'kyc_status' => 'pending',
        'kyc_stage' => 1,
    ]);

    Verification::factory()->create([
        'customer_id' => $customer->id,
        'stage' => 1,
        'status' => 'pending',
        'stage1_status' => 'pending',
    ]);

    $this->postJson("/api/v1/kyc/approvals/customers/{$customer->id}/stages/1/approve", [
        'notes' => 'Docs verified',
    ])
        ->assertOk()
        ->assertJsonPath('data.customer.kyc_stage', 2);

    $customer->refresh();
    expect($customer->kyc_stage)->toBe(2);
});

test('customer can track kyc progress before asset release', function () {
    $customer = Customer::factory()->create([
        'dealer_id' => $this->dealer->id,
        'phone' => '255712345678',
        'kyc_status' => 'pending',
        'kyc_stage' => 2,
        'asset_release_status' => 'pending',
    ]);

    Verification::factory()->create([
        'customer_id' => $customer->id,
        'stage' => 2,
        'status' => 'pending',
    ]);

    $this->postJson('/api/v1/customer/kyc-status', ['phone' => '0712345678'])
        ->assertOk()
        ->assertJsonPath('data.eligibility', 'kyc_in_progress')
        ->assertJsonPath('data.kyc_stage', 2)
        ->assertJsonStructure(['data' => ['portal_message', 'verification']]);
});

test('check phone returns kyc in progress when not yet released', function () {
    $customer = Customer::factory()->create([
        'dealer_id' => $this->dealer->id,
        'phone' => '255798765432',
        'kyc_status' => 'pending',
        'kyc_stage' => 1,
        'asset_release_status' => 'pending',
    ]);

    Verification::factory()->create([
        'customer_id' => $customer->id,
        'stage' => 1,
        'status' => 'pending',
    ]);

    $this->postJson('/api/v1/customer/check-phone', ['phone' => '0798765432'])
        ->assertOk()
        ->assertJsonPath('data.eligibility', 'kyc_in_progress')
        ->assertJsonPath('data.has_pin', false);
});

test('provisioning service refuses loan without approved kyc', function () {
    $customer = Customer::factory()->create([
        'dealer_id' => $this->dealer->id,
        'kyc_status' => 'pending',
        'asset_release_status' => 'released',
        'cash_price' => 400000,
        'preferred_repayment' => 'monthly',
        'deposit_payment_status' => 'completed',
    ]);

    expect(app(CustomerLoanProvisioningService::class)->canProvision($customer))->toBeFalse();
});
