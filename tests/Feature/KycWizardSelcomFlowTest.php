<?php

use App\Livewire\Kyc\CustomerProfiles;
use App\Livewire\Kyc\VerificationWizard;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Permission;
use App\Models\PhoneModel;
use App\Models\SystemDocument;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web']);

    Storage::fake('public');

    $this->branch = Branch::factory()->create();
    $this->fo = User::factory()->create(['branch_id' => $this->branch->id]);
    $this->fo->givePermissionTo(['loans.create', 'loans.view']);

    $this->brand = Brand::factory()->create(['name' => 'Samsung']);
    $this->phoneModel = PhoneModel::factory()->create([
        'brand_id' => $this->brand->id,
        'name' => 'Galaxy A55',
        'retail_price' => 650000,
        'specifications' => ['ram' => '8GB', 'storage' => '256GB', 'color' => 'Blue'],
    ]);
    $this->inventoryUnit = InventoryUnit::factory()->create([
        'phone_model_id' => $this->phoneModel->id,
        'branch_id' => $this->branch->id,
        'status' => 'hq_stock',
        'imei_1' => '353456789012345',
        'serial_number' => 'SAM-A55-0001',
    ]);
});

it('blocks application submission until a successful payment exists', function () {
    actingAs($this->fo);

    SystemDocument::factory()->create([
        'key' => 'kyc_customer_agreement',
        'disk' => 'public',
        'path' => 'agreements/a55-agreement.pdf',
        'is_active' => true,
        'uploaded_by' => $this->fo->id,
    ]);

    Livewire::test(VerificationWizard::class)
        ->set('brandId', $this->brand->id)
        ->set('phoneModelId', $this->phoneModel->id)
        ->set('inventoryUnitId', $this->inventoryUnit->id)
        ->set('depositAmount', '85000')
        ->set('preferredRepayment', 'monthly')
        ->set('loanInterestRate', '4.75')
        ->set('loanInterestType', 'flat')
        ->set('loanDurationWeeks', '52')
        ->set('loanGracePeriodDays', '3')
        ->set('firstName', 'Neema')
        ->set('lastName', 'Paul')
        ->set('gender', 'female')
        ->set('nidaNumber', '98765432109876543210')
        ->set('idType', 'nida')
        ->set('phone', '0712555000')
        ->set('phoneCountry', 'TZ')
        ->set('branchId', $this->branch->id)
        ->set('monthlyIncome', '700000')
        ->set('nokName', 'Rehema Paul')
        ->set('nokPhone', '0755444333')
        ->set('nokPhoneCountry', 'TZ')
        ->set('nokRelationship', 'sibling')
        ->set('termsAccepted', true)
        ->set('dataConsentAccepted', true)
        ->set('callConsentAccepted', true)
        ->set('agreementDecision', 'yes')
        ->set('customerSignatureData', kycWizardFlowSignatureDataUrl())
        ->set('foSignatureData', kycWizardFlowSignatureDataUrl())
        ->set('assetHandoverList', UploadedFile::fake()->create('handover.pdf', 80, 'application/pdf'))
        ->call('processApplication')
        ->assertHasErrors(['depositAmount']);

    expect(Customer::count())->toBe(0);
});

it('releases the asset after approved payment and agreement checks are complete', function () {
    actingAs($this->fo);

    $agreement = SystemDocument::factory()->create([
        'key' => 'kyc_customer_agreement',
        'disk' => 'public',
        'path' => 'agreements/release-agreement.pdf',
        'is_active' => true,
        'uploaded_by' => $this->fo->id,
    ]);

    Storage::disk('public')->put('kyc/customer-signatures/customer.png', base64_decode(kycWizardFlowRawSignature(), true));
    Storage::disk('public')->put('kyc/fo-signatures/fo.png', base64_decode(kycWizardFlowRawSignature(), true));
    Storage::disk('public')->put('kyc/handover/release.pdf', 'handover-checklist');

    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'registered_by' => $this->fo->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'agreement_document_id' => $agreement->id,
        'agreement_accepted' => true,
        'agreement_presented_at' => now()->subMinutes(10),
        'agreement_decision_at' => now()->subMinutes(8),
        'customer_signature_path' => 'kyc/customer-signatures/customer.png',
        'fo_signature_path' => 'kyc/fo-signatures/fo.png',
        'asset_handover_list_path' => 'kyc/handover/release.pdf',
        'asset_handover_notes' => 'Customer received phone, charger, cover, and screen protector.',
        'deposit_payment_status' => 'completed',
        'deposit_payment_amount' => 85000,
        'deposit_payment_reference' => 'SEL-RELEASE-001',
        'deposit_paid_at' => now()->subMinutes(12),
        'asset_release_status' => 'pending',
        'kyc_status' => 'approved',
    ]);

    Verification::create([
        'customer_id' => $customer->id,
        'fo_id' => $this->fo->id,
        'reviewed_by' => $this->fo->id,
        'type' => 'kyc',
        'status' => 'approved',
        'stage' => 4,
        'reviewed_at' => now()->subMinutes(5),
    ]);

    Livewire::test(CustomerProfiles::class)
        ->call('releaseAsset', $customer->id);

    expect($customer->fresh()->asset_release_status)->toBe('released')
        ->and($customer->fresh()->asset_released_by)->toBe($this->fo->id)
        ->and($customer->fresh()->asset_released_at)->not->toBeNull();

    expect($this->inventoryUnit->fresh()->status)->toBe('assigned');
});

function kycWizardFlowSignatureDataUrl(): string
{
    return 'data:image/png;base64,'.kycWizardFlowRawSignature();
}

function kycWizardFlowRawSignature(): string
{
    return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sWwaP8AAAAASUVORK5CYII=';
}
