<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Services\VendorHierarchyService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(VendorHierarchyService::class);
    $branch = Branch::factory()->create();
    $this->vendor = Vendor::factory()->create([
        'branch_id'       => $branch->id,
        'commission_rate' => 5.00,
    ]);
    $customer = Customer::factory()->create(['branch_id' => $branch->id]);
    $this->loan = Loan::factory()->create([
        'customer_id' => $customer->id,
        'vendor_id'   => $this->vendor->id,
        'branch_id'   => $branch->id,
    ]);
});

test('posts commission and credits vendor wallet', function () {
    $transaction = Transaction::factory()->create([
        'loan_id'    => $this->loan->id,
        'amount'     => 100_000,
        'type'       => 'repayment',
        'entry_type' => 'credit',
    ]);

    $ledger = $this->service->postCommission($this->loan, $transaction);

    expect($ledger)->not->toBeNull()
        ->and($ledger->commission_amount)->toEqual('5000.00')
        ->and($ledger->status)->toBe('posted');

    expect((float) VendorWallet::where('vendor_id', $this->vendor->id)->value('balance'))
        ->toBe(5000.0);
});

test('returns null when vendor has zero commission rate', function () {
    $this->vendor->update(['commission_rate' => 0]);

    $transaction = Transaction::factory()->create([
        'loan_id'    => $this->loan->id,
        'amount'     => 100_000,
        'type'       => 'repayment',
        'entry_type' => 'credit',
    ]);

    $ledger = $this->service->postCommission($this->loan, $transaction);

    expect($ledger)->toBeNull()
        ->and(VendorWallet::count())->toBe(0);
});

test('creditWallet increments balance and total_earned', function () {
    $this->service->creditWallet($this->vendor, 20_000);
    $this->service->creditWallet($this->vendor, 10_000);

    $wallet = $this->vendor->fresh()->wallet;

    expect((float) $wallet->balance)->toBe(30_000.0)
        ->and((float) $wallet->total_earned)->toBe(30_000.0);
});

test('debitWallet decrements balance and increments total_withdrawn', function () {
    $this->service->creditWallet($this->vendor, 50_000);
    $this->service->debitWallet($this->vendor, 20_000);

    $wallet = $this->vendor->fresh()->wallet;

    expect((float) $wallet->balance)->toBe(30_000.0)
        ->and((float) $wallet->total_withdrawn)->toBe(20_000.0);
});

test('debitWallet throws RuntimeException on insufficient balance', function () {
    $this->service->creditWallet($this->vendor, 5_000);
    $this->service->debitWallet($this->vendor, 10_000);
})->throws(\RuntimeException::class);

test('walletStatement returns correct summary', function () {
    $this->service->creditWallet($this->vendor, 100_000);
    $this->service->debitWallet($this->vendor, 30_000);

    $statement = $this->service->walletStatement($this->vendor);

    expect($statement['balance'])->toBe(70_000.0)
        ->and($statement['total_earned'])->toBe(100_000.0)
        ->and($statement['total_withdrawn'])->toBe(30_000.0);
});
