<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\PhoneModel;
use App\Models\Vendor;
use App\Services\LoanCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(LoanCalculatorService::class);
});

test('flat rate computes correct total payable', function () {
    $result = $this->service->computeFlat(1_000_000, 20, 12);

    expect($result['total_interest'])->toBe(46_153.85)
        ->and($result['total_payable'])->toBe(1_046_153.85)
        ->and($result['installment_amount'])->toBe(87_179.49);
});

test('reducing balance computes lower total interest than flat rate', function () {
    $flat = $this->service->computeFlat(1_000_000, 20, 12);
    $reducing = $this->service->computeReducingBalance(1_000_000, 20, 12);

    expect($reducing['total_interest'])->toBeLessThan($flat['total_interest']);
});

test('generates correct number of schedule rows', function () {
    $loan = createTestLoan(principal: 500_000, weeks: 12, type: 'flat');

    $this->service->createSchedule($loan);

    expect($loan->repaymentSchedules()->count())->toBe(12);
});

test('schedule installment numbers are sequential', function () {
    $loan = createTestLoan(principal: 300_000, weeks: 4, type: 'flat');

    $this->service->createSchedule($loan);

    $numbers = $loan->repaymentSchedules()->pluck('installment_number')->toArray();
    expect($numbers)->toBe([1, 2, 3, 4]);
});

test('monthly schedules use installment count derived from weeks', function () {
    $loan = createTestLoan(
        principal: 500_000,
        weeks: 52,
        type: 'flat',
        repaymentFrequency: 'monthly',
    );

    $this->service->createSchedule($loan);

    expect($loan->repaymentSchedules()->count())->toBe(13)
        ->and($loan->repaymentSchedules()->first()?->due_date?->diffInDays($loan->disbursed_at))->toBeGreaterThanOrEqual(28);
});

test('biweekly schedules use half-week installment count', function () {
    $loan = createTestLoan(
        principal: 500_000,
        weeks: 12,
        type: 'flat',
        repaymentFrequency: 'biweekly',
    );

    $this->service->createSchedule($loan);

    expect($loan->repaymentSchedules()->count())->toBe(6)
        ->and($loan->repaymentSchedules()->first()?->due_date?->diffInDays($loan->disbursed_at))->toBe(14);
});

test('generate loan number is unique', function () {
    $n1 = $this->service->generateLoanNumber();
    $n2 = $this->service->generateLoanNumber();

    expect($n1)->toStartWith('LN-')
        ->and($n1)->not->toBe($n2);
});

test('applies penalties to overdue installments', function () {
    $loan = createTestLoan(principal: 200_000, weeks: 4, type: 'flat');
    $this->service->createSchedule($loan);

    $loan->repaymentSchedules()->update([
        'due_date' => Carbon::today()->subDays(10),
        'status' => 'pending',
    ]);

    $affected = $this->service->applyPenalties($loan, 1.0);

    expect($affected)->toBe(4);
    expect($loan->fresh()->penalty_amount)->toBeGreaterThan(0);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function createTestLoan(
    float $principal,
    int $weeks,
    string $type,
    string $repaymentFrequency = 'weekly'
): Loan {
    $branch = Branch::factory()->create();
    $brand = Brand::factory()->create();
    $model = PhoneModel::factory()->create(['brand_id' => $brand->id]);
    $vendor = Vendor::factory()->create(['branch_id' => $branch->id]);
    $customer = Customer::factory()->create(['branch_id' => $branch->id, 'vendor_id' => $vendor->id]);
    $unit = InventoryUnit::factory()->create(['phone_model_id' => $model->id, 'vendor_id' => $vendor->id]);

    $service = app(LoanCalculatorService::class);
    $computed = $type === 'flat'
        ? $service->computeFlat($principal, 20, $weeks, $repaymentFrequency)
        : $service->computeReducingBalance($principal, 20, $weeks, $repaymentFrequency);

    return Loan::factory()->create([
        'customer_id' => $customer->id,
        'inventory_unit_id' => $unit->id,
        'vendor_id' => $vendor->id,
        'branch_id' => $branch->id,
        'loan_number' => $service->generateLoanNumber(),
        'principal_amount' => $principal,
        'interest_rate' => 20,
        'interest_type' => $type,
        'total_payable' => $computed['total_payable'],
        'outstanding_balance' => $computed['total_payable'],
        'duration_weeks' => $weeks,
        'repayment_frequency' => $repaymentFrequency,
        'status' => 'active',
        'disbursed_at' => Carbon::today()->subWeeks($weeks + 1),
        'due_date' => Carbon::today()->subWeek(),
    ]);
}
