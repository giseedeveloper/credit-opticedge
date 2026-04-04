<?php

use App\Livewire\Kyc\VerificationWizard;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\User;
use App\Models\Verification;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    $this->fo = User::factory()->create();
    $this->fo->givePermissionTo('loans.create');
    $this->branch = Branch::factory()->create();
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
        ->assertHasErrors(['imeiNumber', 'deviceSpecs', 'cashPrice', 'depositAmount', 'preferredRepayment']);
});

it('advances to step 2 after valid step 1', function () {
    actingAs($this->fo);

    Livewire::test(VerificationWizard::class)
        ->set('deviceSpecs', 'Tecno Camon 30 – 8GB/256GB')
        ->set('imeiNumber', '123456789012345')
        ->set('cashPrice', '450000')
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
    $existing = Customer::factory()->create(['phone' => '0712000001']);

    Livewire::test(VerificationWizard::class)
        ->set('step', 3)
        ->set('phone', '0712000001')
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

    // Step 2: NIDA unique check needs a real unique value
    $nida = str_pad('1', 20, '0');

    Livewire::test(VerificationWizard::class)
        // Step 1
        ->set('deviceSpecs', 'Samsung A15 – 4GB/128GB')
        ->set('imeiNumber', '999888777666555')
        ->set('cashPrice', '350000')
        ->set('depositAmount', '35000')
        ->set('preferredRepayment', 'monthly')
        // Step 2
        ->set('firstName', 'Amina')
        ->set('lastName', 'Juma')
        ->set('gender', 'female')
        ->set('nidaNumber', $nida)
        ->set('idType', 'nida')
        // Step 3
        ->set('phone', '0712999888')
        ->set('branchId', $this->branch->id)
        // Step 4
        ->set('monthlyIncome', '500000')
        // Step 5
        ->set('nokName', 'John Mwangi')
        ->set('nokPhone', '0754111222')
        ->set('nokRelationship', 'spouse')
        // Step 6
        ->set('termsAccepted', true)
        ->set('dataConsentAccepted', true)
        ->set('callConsentAccepted', true)
        // Submit — service is DI-injected by Livewire automatically
        ->call('processApplication')
        ->assertSet('submitted', true);

    expect(Customer::where('phone', '0712999888')->exists())->toBeTrue();
    expect(Verification::whereHas('customer', fn ($q) => $q->where('phone', '0712999888'))->exists())->toBeTrue();
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
