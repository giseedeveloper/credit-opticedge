<?php

use App\Livewire\Kyc\VerificationWizard;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Permission;
use App\Models\PhoneModel;
use App\Models\SelcomPaymentRequest;
use App\Models\SystemDocument;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    Storage::fake('public');
    $this->branch = Branch::factory()->create();
    $this->fo = User::factory()->create(['branch_id' => $this->branch->id]);
    $this->fo->givePermissionTo('loans.create');
    $this->brand = Brand::factory()->create(['name' => 'Tecno']);
    $this->phoneModel = PhoneModel::factory()->create([
        'brand_id' => $this->brand->id,
        'name' => 'Camon 30',
        'retail_price' => 450000,
        'specifications' => ['ram' => '8GB', 'storage' => '256GB', 'color' => 'Black'],
    ]);
    $this->inventoryUnit = InventoryUnit::factory()->create([
        'phone_model_id' => $this->phoneModel->id,
        'branch_id' => $this->branch->id,
        'status' => 'hq_stock',
        'imei_1' => '123456789012345',
        'serial_number' => 'SN-CAMON30-0001',
    ]);
});

it('redirects unauthenticated users', function () {
    $this->get(route('kyc.wizard'))->assertRedirect(route('login'));
});

it('renders the wizard for authorised users', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->assertStatus(200)
        ->assertSet('step', 1);
});

it('validates step 1 device fields', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->call('nextStep')
        ->assertHasErrors(['brandId', 'phoneModelId', 'deviceSpecs', 'cashPrice', 'depositAmount', 'preferredRepayment']);
});

it('autofills device identifiers when a stock unit is selected', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->set('brandId', $this->brand->id)
        ->set('phoneModelId', $this->phoneModel->id)
        ->set('inventoryUnitId', $this->inventoryUnit->id)
        ->assertSet('imeiNumber', $this->inventoryUnit->imei_1)
        ->assertSet('serialNumber', $this->inventoryUnit->serial_number)
        ->assertSet('deviceSpecs', 'Tecno - Camon 30 - 8GB/256GB/Black');
});

it('shows a clear validation error when selcom checkout is not configured', function () {
    actingAs($this->fo);

    Config::set('services.selcom.vendor', null);
    Config::set('services.selcom.api_key', null);
    Config::set('services.selcom.api_secret', null);

    Livewire::test(VerificationWizard::class)
        ->set('draftReference', 'draft-livewire-no-selcom')
        ->set('depositAmount', '50000')
        ->set('phone', '0712345678')
        ->set('paymentPhone', '0712345678')
        ->call('initiateDepositPayment')
        ->assertHasErrors(['paymentPhone']);
});

it('fills imei and serial from a scanned device image payload', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->set('brandId', $this->brand->id)
        ->set('phoneModelId', $this->phoneModel->id)
        ->call('applyDetectedIdentifiers', [
            'raw_text' => 'IMEI: 356789012345678 Serial Number: TECNO-C30-0009',
            'detectors' => ['text'],
        ])
        ->assertSet('imeiNumber', '356789012345678')
        ->assertSet('serialNumber', 'TECNO-C30-0009')
        ->assertSet('scanFeedbackTone', 'emerald');
});

it('advances to step 2 after valid step 1', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->set('brandId', $this->brand->id)
        ->set('phoneModelId', $this->phoneModel->id)
        ->set('inventoryUnitId', $this->inventoryUnit->id)
        ->set('depositAmount', '50000')
        ->set('preferredRepayment', 'weekly')
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertHasNoErrors();
});

it('validates step 2 identity fields', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->set('step', 2)
        ->call('nextStep')
        ->assertHasErrors(['firstName', 'lastName', 'gender', 'nidaNumber', 'idType']);
});

it('validates step 3 phone uniqueness', function () {
    actingAs($this->fo);
    Customer::factory()->create(['phone' => '+255712000001']);

    Livewire::test(VerificationWizard::class)
        ->set('step', 3)
        ->set('phone', '0712000001')
        ->set('phoneCountry', 'TZ')
        ->set('branchId', $this->branch->id)
        ->call('nextStep')
        ->assertHasErrors(['phone']);
});

it('validates step 6 consent required', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->set('step', 6)
        ->call('nextStep')
        ->assertHasErrors(['termsAccepted', 'dataConsentAccepted', 'callConsentAccepted']);
});

it('creates customer and verification on submit and runs auto-checks', function () {
    actingAs($this->fo);

    $nida = str_pad('1', 20, '0');
    $agreement = SystemDocument::factory()->create([
        'key' => 'kyc_customer_agreement',
        'disk' => 'public',
        'path' => 'agreements/customer-agreement.pdf',
        'is_active' => true,
        'uploaded_by' => $this->fo->id,
    ]);

    $component = Livewire::test(VerificationWizard::class);
    $draftReference = $component->get('draftReference');

    SelcomPaymentRequest::factory()->create([
        'draft_reference' => $draftReference,
        'initiated_by' => $this->fo->id,
        'phone' => '255712999888',
        'amount' => 35000,
        'status' => 'completed',
        'payment_status' => 'COMPLETED',
        'result' => 'SUCCESS',
        'resultcode' => '000',
        'selcom_reference' => 'SEL-REF-001',
        'paid_at' => now(),
    ]);

    $component
        // Step 1
        ->set('brandId', $this->brand->id)
        ->set('phoneModelId', $this->phoneModel->id)
        ->set('inventoryUnitId', $this->inventoryUnit->id)
        ->set('depositAmount', '35000')
        ->set('preferredRepayment', 'monthly')
        ->set('deviceAccessories', [
            ['code' => 'screen_protector', 'name' => 'Screen Protector', 'quantity' => 1, 'offer_type' => 'free', 'unit_price' => '', 'notes' => 'Promo gift'],
            ['code' => 'phone_cover', 'name' => 'Phone Cover', 'quantity' => 1, 'offer_type' => 'charged', 'unit_price' => '15000', 'notes' => 'Premium cover'],
        ])
        ->set('storeOfferNotes', 'Weekend offer included a free protector.')
        // Step 2
        ->set('firstName', 'Amina')
        ->set('lastName', 'Juma')
        ->set('gender', 'female')
        ->set('nidaNumber', $nida)
        ->set('idType', 'nida')
        // Step 3
        ->set('phone', '0712999888')
        ->set('phoneCountry', 'TZ')
        ->set('branchId', $this->branch->id)
        // Step 4
        ->set('monthlyIncome', '500000')
        // Step 5
        ->set('nokName', 'John Mwangi')
        ->set('nokPhone', '0754111222')
        ->set('nokPhoneCountry', 'TZ')
        ->set('nokRelationship', 'spouse')
        // Step 6
        ->set('termsAccepted', true)
        ->set('dataConsentAccepted', true)
        ->set('callConsentAccepted', true)
        // Step 7 payment & agreement
        ->set('agreementDecision', 'yes')
        ->set('customerSignatureData', kycWizardSignatureDataUrl())
        ->set('foSignatureData', kycWizardSignatureDataUrl())
        ->set('assetHandoverList', UploadedFile::fake()->create('handover.pdf', 120, 'application/pdf'))
        ->set('assetHandoverNotes', 'Phone, charger, box, cover and protector issued to customer.')
        // Submit — service is DI-injected by Livewire automatically
        ->call('processApplication')
        ->assertSet('submitted', true);

    $customer = Customer::where('phone', '+255712999888')
        ->where('phone_model_id', $this->phoneModel->id)
        ->where('inventory_unit_id', $this->inventoryUnit->id)
        ->latest()
        ->first();

    expect($customer)->not->toBeNull()
        ->and($customer?->phone_metadata['phone']['country_iso'])->toBe('TZ')
        ->and($customer?->nok_phone)->toBe('+255754111222')
        ->and($customer?->device_accessories)->toHaveCount(2)
        ->and($customer?->store_offer_notes)->toBe('Weekend offer included a free protector.')
        ->and($customer?->agreement_document_id)->toBe($agreement->id)
        ->and($customer?->deposit_payment_status)->toBe('completed')
        ->and($customer?->asset_release_status)->toBe('pending')
        ->and($customer?->asset_handover_list_path)->not->toBeNull();

    Storage::disk('public')->assertExists($customer?->customer_signature_path);
    Storage::disk('public')->assertExists($customer?->fo_signature_path);
    Storage::disk('public')->assertExists($customer?->asset_handover_list_path);

    expect(Verification::whereHas('customer', fn ($q) => $q->where('phone', '+255712999888'))->exists())->toBeTrue();
});

it('can go back to a previous step', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->set('step', 3)
        ->call('previousStep')
        ->assertSet('step', 2);
});

it('resets state on startNew', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->set('step', 4)
        ->set('firstName', 'Test')
        ->call('startNew')
        ->assertSet('step', 1)
        ->assertSet('firstName', '');
});

function kycWizardSignatureDataUrl(): string
{
    return 'data:image/png;base64,'.base64_encode(base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sWwaP8AAAAASUVORK5CYII=',
        true
    ));
}
