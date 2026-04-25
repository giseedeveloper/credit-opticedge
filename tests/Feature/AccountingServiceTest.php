<?php

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\PhoneModel;
use App\Models\Transaction;
use App\Services\AccountingService;
use App\Services\LoanCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AccountingService::class);
    $this->calculator = app(LoanCalculatorService::class);

    $brand = Brand::factory()->create();
    $model = PhoneModel::factory()->create(['brand_id' => $brand->id]);
    $this->vendor = Dealer::factory()->create(['commission_rate' => 5]);
    $customer = Customer::factory()->create(['dealer_id' => $this->vendor->id]);
    $unit = InventoryUnit::factory()->create(['phone_model_id' => $model->id]);

    $computed = $this->calculator->computeFlat(500_000, 20, 4);

    $this->loan = Loan::factory()->create([
        'customer_id' => $customer->id,
        'inventory_unit_id' => $unit->id,
        'dealer_id' => $this->vendor->id,
        'loan_number' => $this->calculator->generateLoanNumber(),
        'principal_amount' => 500_000,
        'interest_rate' => 20,
        'interest_type' => 'flat',
        'total_payable' => $computed['total_payable'],
        'outstanding_balance' => $computed['total_payable'],
        'duration_weeks' => 4,
        'repayment_frequency' => 'weekly',
        'status' => 'active',
        'disbursed_at' => Carbon::today()->toDateString(),
        'due_date' => Carbon::today()->addWeeks(4)->toDateString(),
    ]);

    $this->calculator->createSchedule($this->loan);
    $this->schedule = $this->loan->repaymentSchedules()->first();
});

test('records a double-entry repayment creating debit and credit transactions', function () {
    $result = $this->service->recordRepayment($this->loan, $this->schedule, 50_000);

    expect(Transaction::count())->toBe(2)
        ->and($result['debit']->entry_type)->toBe('debit')
        ->and($result['credit']->entry_type)->toBe('credit')
        ->and($result['debit']->amount)->toEqual('50000.00')
        ->and($result['credit']->amount)->toEqual('50000.00');
});

test('repayment updates loan outstanding balance', function () {
    $initialBalance = (float) $this->loan->outstanding_balance;

    $this->service->recordRepayment($this->loan, $this->schedule, 50_000);

    expect((float) $this->loan->fresh()->outstanding_balance)
        ->toBe(round($initialBalance - 50_000, 2));
});

test('repayment marks schedule as paid when fully paid', function () {
    $this->service->recordRepayment($this->loan, $this->schedule, (float) $this->schedule->amount_due);

    expect($this->schedule->fresh()->status)->toBe('paid')
        ->and($this->schedule->fresh()->paid_at)->not->toBeNull();
});

test('repayment marks schedule as partial when partially paid', function () {
    $this->service->recordRepayment($this->loan, $this->schedule, 1_000);

    expect($this->schedule->fresh()->status)->toBe('partial');
});

test('loan status becomes completed when balance reaches zero', function () {
    $balance = (float) $this->loan->outstanding_balance;

    $this->service->recordRepayment($this->loan, $this->schedule, $balance);

    expect($this->loan->fresh()->status)->toBe('completed')
        ->and($this->loan->fresh()->completed_at)->not->toBeNull();
});

test('build receipt payload contains all required TRA fields', function () {
    $result = $this->service->recordRepayment($this->loan, $this->schedule, 30_000);
    $receipt = $this->service->buildReceiptPayload($result['credit']);

    expect($receipt)->toHaveKeys(['receipt_number', 'date', 'amount', 'vat', 'customer_name', 'loan_number', 'payment_channel']);
});
