<?php

use App\Livewire\Partnership\VendorDirectory;
use App\Models\Branch;
use App\Models\CommissionLedger;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'vendors.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'vendors.create', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['is_active' => true]);
    $this->user->givePermissionTo('vendors.view');
    $this->user->givePermissionTo('vendors.create');
});

it('supports vendor directory search on mysql compatible drivers', function () {
    $branch = Branch::factory()->create(['name' => 'Kariakoo Branch']);

    Vendor::factory()->create([
        'branch_id' => $branch->id,
        'name' => 'Alpha Dealers',
        'phone' => '0712345678',
        'email' => 'alpha@example.test',
    ]);

    $this->actingAs($this->user)
        ->get(route('partners.vendors'))
        ->assertOk()
        ->assertSeeText('Vendor Directory');

    Livewire::actingAs($this->user)
        ->test(VendorDirectory::class)
        ->set('search', 'alpha')
        ->assertSee('Alpha Dealers')
        ->assertSee('Kariakoo Branch');
});

it('allows authorized users to add a vendor and boots a wallet record', function () {
    $branch = Branch::factory()->create(['name' => 'Mwenge Branch']);
    $owner = User::factory()->create([
        'name' => 'Rachel Manager',
        'branch_id' => $branch->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(VendorDirectory::class)
        ->call('openCreateModal')
        ->set('newName', 'Mwenge Dealer Hub')
        ->set('newBranchId', $branch->id)
        ->set('newOwnerUserId', $owner->id)
        ->set('newPhone', '0711223344')
        ->set('newEmail', 'dealer@mwenge.test')
        ->set('newTinNumber', '123-456-789')
        ->set('newCommissionRate', '7.50')
        ->set('newStatus', 'active')
        ->set('newAddress', 'Mwenge, Dar es Salaam')
        ->call('createVendor')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    $vendor = Vendor::query()->where('name', 'Mwenge Dealer Hub')->first();

    expect($vendor)->not->toBeNull()
        ->and($vendor?->branch_id)->toBe($branch->id)
        ->and($vendor?->owner_user_id)->toBe($owner->id)
        ->and($vendor?->code)->toStartWith('VND-')
        ->and((float) $vendor?->commission_rate)->toBe(7.5);

    expect(VendorWallet::query()->where('vendor_id', $vendor->id)->exists())->toBeTrue();
});

it('shows deep vendor performance details in the slide over', function () {
    $branch = Branch::factory()->create(['name' => 'Mwanza HQ']);
    $owner = User::factory()->create([
        'name' => 'Leah Supervisor',
        'branch_id' => $branch->id,
        'is_active' => true,
    ]);

    $vendor = Vendor::factory()->create([
        'branch_id' => $branch->id,
        'owner_user_id' => $owner->id,
        'name' => 'Lake Zone Devices',
        'code' => 'VND-LAKE-1111',
        'commission_rate' => 6.5,
    ]);

    VendorWallet::create([
        'vendor_id' => $vendor->id,
        'balance' => 40_000,
        'total_earned' => 120_000,
        'total_withdrawn' => 80_000,
        'last_transaction_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'first_name' => 'Amina',
        'last_name' => 'Msuya',
        'phone' => '0712000001',
        'kyc_status' => 'approved',
    ]);

    $inventoryUnit = InventoryUnit::factory()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'status' => 'vendor_stock',
    ]);

    $loan = Loan::factory()->create([
        'customer_id' => $customer->id,
        'inventory_unit_id' => $inventoryUnit->id,
        'vendor_id' => $vendor->id,
        'branch_id' => $branch->id,
        'principal_amount' => 600_000,
        'total_payable' => 720_000,
        'amount_paid' => 180_000,
        'outstanding_balance' => 540_000,
        'status' => 'active',
    ]);

    CommissionLedger::create([
        'vendor_id' => $vendor->id,
        'loan_id' => $loan->id,
        'transaction_id' => null,
        'commission_rate' => 6.5,
        'commission_amount' => 39_000,
        'status' => 'posted',
        'description' => 'Commission on Lake Zone Devices repayment',
        'posted_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(VendorDirectory::class)
        ->call('openDetail', $vendor->id)
        ->assertSet('showDetail', true)
        ->assertSee('Business Snapshot')
        ->assertSee('Recent Customers Served')
        ->assertSee('Recent Commissions')
        ->assertSee('Recent Loans')
        ->assertSee('Amina Msuya')
        ->assertSee('Lake Zone Devices')
        ->assertSee('Commission on Lake Zone Devices repayment');
});
