<?php

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\DealerWallet;
use App\Models\Loan;
use App\Models\Transaction;
use App\Services\DealerHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(DealerHierarchyService::class);
    $this->dealer = Dealer::factory()->create([
        'commission_rate' => 5.00,
    ]);
    $customer = Customer::factory()->create(['dealer_id' => $this->dealer->id]);
    $this->loan = Loan::factory()->create([
        'customer_id' => $customer->id,
        'dealer_id' => $this->dealer->id,
    ]);
});

test('posts commission and credits dealer wallet', function () {
    $transaction = Transaction::factory()->create([
        'loan_id' => $this->loan->id,
        'amount' => 100_000,
        'type' => 'repayment',
        'entry_type' => 'credit',
    ]);

    $ledger = $this->service->postCommission($this->loan, $transaction);

    expect($ledger)->not->toBeNull()
        ->and($ledger->commission_amount)->toEqual('5000.00')
        ->and($ledger->status)->toBe('posted');

    expect((float) DealerWallet::where('dealer_id', $this->dealer->id)->value('balance'))
        ->toBe(5000.0);
});

test('returns null when dealer has zero commission rate', function () {
    $this->dealer->update(['commission_rate' => 0]);

    $transaction = Transaction::factory()->create([
        'loan_id' => $this->loan->id,
        'amount' => 100_000,
        'type' => 'repayment',
        'entry_type' => 'credit',
    ]);

    $ledger = $this->service->postCommission($this->loan, $transaction);

    expect($ledger)->toBeNull()
        ->and(DealerWallet::count())->toBe(0);
});

test('creditWallet increments balance and total_earned', function () {
    $this->service->creditWallet($this->dealer, 20_000);
    $this->service->creditWallet($this->dealer, 10_000);

    $wallet = $this->dealer->fresh()->wallet;

    expect((float) $wallet->balance)->toBe(30_000.0)
        ->and((float) $wallet->total_earned)->toBe(30_000.0);
});

test('debitWallet decrements balance and increments total_withdrawn', function () {
    $this->service->creditWallet($this->dealer, 50_000);
    $this->service->debitWallet($this->dealer, 20_000);

    $wallet = $this->dealer->fresh()->wallet;

    expect((float) $wallet->balance)->toBe(30_000.0)
        ->and((float) $wallet->total_withdrawn)->toBe(20_000.0);
});

test('debitWallet throws RuntimeException on insufficient balance', function () {
    $this->service->creditWallet($this->dealer, 5_000);
    $this->service->debitWallet($this->dealer, 10_000);
})->throws(RuntimeException::class);

test('walletStatement returns correct summary', function () {
    $this->service->creditWallet($this->dealer, 100_000);
    $this->service->debitWallet($this->dealer, 30_000);

    $statement = $this->service->walletStatement($this->dealer);

    expect($statement['balance'])->toBe(70_000.0)
        ->and($statement['total_earned'])->toBe(100_000.0)
        ->and($statement['total_withdrawn'])->toBe(30_000.0);
});
