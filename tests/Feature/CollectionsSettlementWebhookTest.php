<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\PhoneModel;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Services\LoanCalculatorService;
use App\Services\LoanManagementService;
use App\Services\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->paymentService = app(PaymentProcessingService::class);
    $this->loanService = app(LoanManagementService::class);
});

test('payment processing allocates repayments across open schedules', function () {
    $loan = createCollectionsLoan(principal: 520_000, weeks: 4);
    $initialBalance = (float) $loan->outstanding_balance;

    $firstSchedule = $loan->repaymentSchedules()->orderBy('installment_number')->firstOrFail();
    $secondSchedule = $loan->repaymentSchedules()->orderBy('installment_number')->skip(1)->firstOrFail();
    $paymentAmount = round((float) $firstSchedule->amount_due + 10_000, 2);

    $transaction = $this->paymentService->recordPayment(
        $loan,
        $paymentAmount,
        'MNO-TXN-001',
        'M-Pesa',
        ['external_reference' => 'MNO-TXN-001']
    );

    $loan->refresh();
    $firstSchedule->refresh();
    $secondSchedule->refresh();

    expect($transaction->type)->toBe('repayment')
        ->and($transaction->entry_type)->toBe('credit')
        ->and($transaction->channel)->toBe('mpesa')
        ->and((float) $transaction->meta['applied_amount'])->toBe($paymentAmount)
        ->and(count($transaction->meta['allocations']))->toBe(2);

    expect($firstSchedule->status)->toBe('paid')
        ->and($firstSchedule->paid_at)->not->toBeNull()
        ->and($secondSchedule->status)->toBe('partial')
        ->and((float) $secondSchedule->amount_paid)->toBe(10_000.0)
        ->and((float) $loan->outstanding_balance)->toBe(round($initialBalance - $paymentAmount, 2));
});

test('finance settlement quote endpoint returns the current payoff values', function () {
    Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('loans.view');
    Sanctum::actingAs($user);

    $loan = createCollectionsLoan(principal: 400_000, weeks: 4);

    $response = $this->getJson("/api/v1/finance/settlement-quote/{$loan->id}");

    $expectedOutstanding = round((float) $loan->outstanding_balance, 2);
    $expectedRebate = round($expectedOutstanding * 0.20, 2);
    $expectedSettlement = round($expectedOutstanding - $expectedRebate, 2);

    $response->assertOk()
        ->assertJsonPath('data.loan_id', $loan->id)
        ->assertJsonPath('data.total_outstanding', $expectedOutstanding)
        ->assertJsonPath('data.rebate_applied', $expectedRebate)
        ->assertJsonPath('data.final_settlement_amount', $expectedSettlement);
});

test('process settlement completes the loan and closes open schedules', function () {
    $loan = createCollectionsLoan(principal: 450_000, weeks: 4);
    $quote = $this->loanService->getEarlySettlementQuote($loan);

    $transaction = $this->loanService->processSettlement(
        $loan,
        (float) $quote['final_settlement_amount'],
        'SETTLE-001'
    );

    $loan->refresh();

    expect($transaction->type)->toBe('settlement')
        ->and($transaction->entry_type)->toBe('credit')
        ->and($transaction->channel)->toBe('bank')
        ->and((float) $transaction->meta['rebate_applied'])->toBe((float) $quote['rebate_applied'])
        ->and($loan->status)->toBe('completed')
        ->and((float) $loan->remaining_balance)->toBe(0.0)
        ->and((float) $loan->outstanding_balance)->toBe(0.0)
        ->and($loan->completed_at)->not->toBeNull();

    expect(
        RepaymentSchedule::where('loan_id', $loan->id)
            ->where('status', 'paid')
            ->count()
    )->toBe($loan->repaymentSchedules()->count());
});

test('webhook rejects invalid signatures when a secret is configured', function () {
    Config::set('services.collections.webhook_secret', 'test-secret');

    $loan = createCollectionsLoan(principal: 300_000, weeks: 4);

    $response = $this->postJson('/api/v1/collection/webhook', [
        'transaction_id' => 'COLL-TXN-001',
        'amount' => 25_000,
        'account_number' => $loan->loan_number,
        'msisdn' => '255712345678',
        'network' => 'M-Pesa',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid webhook signature');

    expect(Transaction::count())->toBe(0);
});

test('webhook records signed payments and blocks duplicate references', function () {
    Config::set('services.collections.webhook_secret', 'test-secret');

    $loan = createCollectionsLoan(principal: 300_000, weeks: 4);
    $payload = [
        'transaction_id' => 'COLL-TXN-001',
        'amount' => 25_000,
        'account_number' => $loan->loan_number,
        'msisdn' => '255712345678',
        'network' => 'M-Pesa',
    ];
    $signature = webhookSignature($payload, 'test-secret');

    $firstResponse = $this->withHeaders(['X-MNO-Signature' => $signature])
        ->postJson('/api/v1/collection/webhook', $payload);

    $firstResponse->assertOk()
        ->assertJsonPath('data.reference', 'COLL-TXN-001')
        ->assertJsonPath('data.channel', 'mpesa');

    $secondResponse = $this->withHeaders(['X-MNO-Signature' => $signature])
        ->postJson('/api/v1/collection/webhook', $payload);

    $secondResponse->assertStatus(409)
        ->assertJsonPath('message', 'Duplicate transaction reference');

    expect(Transaction::count())->toBe(1)
        ->and(Transaction::first()?->meta['msisdn'])->toBe('255712345678');
});

function createCollectionsLoan(float $principal = 400_000, int $weeks = 4): Loan
{
    $branch = Branch::factory()->create();
    $brand = Brand::factory()->create();
    $model = PhoneModel::factory()->create(['brand_id' => $brand->id]);
    $vendor = Vendor::factory()->create(['branch_id' => $branch->id]);
    $customer = Customer::factory()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
    ]);
    $unit = InventoryUnit::factory()->create([
        'phone_model_id' => $model->id,
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
    ]);

    $calculator = app(LoanCalculatorService::class);
    $computed = $calculator->computeFlat($principal, 20, $weeks);

    $loan = Loan::factory()->create([
        'customer_id' => $customer->id,
        'inventory_unit_id' => $unit->id,
        'vendor_id' => $vendor->id,
        'branch_id' => $branch->id,
        'loan_number' => $calculator->generateLoanNumber(),
        'principal_amount' => $principal,
        'interest_rate' => 20,
        'interest_type' => 'flat',
        'total_debt' => $computed['total_payable'],
        'total_payable' => $computed['total_payable'],
        'amount_paid' => 0,
        'remaining_balance' => $computed['total_payable'],
        'outstanding_balance' => $computed['total_payable'],
        'duration_weeks' => $weeks,
        'repayment_frequency' => 'weekly',
        'status' => 'active',
        'disbursed_at' => now()->subWeek()->toDateString(),
        'due_date' => now()->addWeeks($weeks)->toDateString(),
    ]);

    $calculator->createSchedule($loan);

    return $loan->fresh(['repaymentSchedules']);
}

function webhookSignature(array $payload, string $secret): string
{
    $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);

    return base64_encode(hash_hmac('sha256', $encodedPayload, $secret, true));
}
