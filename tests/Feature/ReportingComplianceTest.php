<?php

use App\Exports\CollectionsExport;
use App\Exports\InventoryExport;
use App\Jobs\SendSmsJob;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\PhoneModel;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DeviceLockingService;
use App\Services\DocumentService;
use App\Services\FinancialComplianceService;
use App\Services\LoanManagementService;
use App\Services\RefurbishmentService;
use App\Services\ReportGenerationService;
use App\Services\TaxService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\actingAs;

it('calculates ifrs stages and ecl using the current repayment schedule schema', function () {
    ['loan' => $loan] = createReportingLoanFixture([
        'status' => 'active',
        'remaining_balance' => 200_000,
        'outstanding_balance' => 200_000,
    ]);

    RepaymentSchedule::factory()->create([
        'loan_id' => $loan->id,
        'installment_number' => 1,
        'amount_due' => 100_000,
        'due_date' => today()->subDays(35)->toDateString(),
        'status' => 'pending',
    ]);

    $service = app(FinancialComplianceService::class);
    $service->calculateProvisioning();
    $report = $service->generateECLReport();

    $loan->refresh();

    expect($loan->dpd)->toBe(35)
        ->and($loan->ifrs_stage)->toBe(2)
        ->and((float) $report['stage_2']['exposure'])->toBe(200_000.0)
        ->and($report['stage_2']['provision_required'])->toBe('30000.00');
});

it('exports collections from posted repayment transactions', function () {
    ['loan' => $loan, 'customer' => $customer] = createReportingLoanFixture();

    Transaction::factory()->create([
        'loan_id' => $loan->id,
        'customer_id' => $customer->id,
        'reference' => 'RPT-COLL-001',
        'type' => 'repayment',
        'entry_type' => 'credit',
        'amount' => 75_000,
        'channel' => 'mpesa',
        'transacted_at' => now(),
    ]);

    $export = new CollectionsExport;
    $row = $export->collection()->first();
    $mapped = $export->map($row);

    expect($row->reference)->toBe('RPT-COLL-001')
        ->and((float) $row->amount)->toBe(75_000.0)
        ->and($mapped)->toContain('mpesa', 'RPT-COLL-001');
});

it('exports inventory valuation through phone model and dealer relations', function () {
    $brand = Brand::factory()->create(['name' => 'Tecno', 'slug' => 'tecno']);
    $model = PhoneModel::factory()->create([
        'brand_id' => $brand->id,
        'name' => 'Spark 20',
        'slug' => 'spark-20',
    ]);
    $dealer = Dealer::factory()->create(['name' => 'Kariakoo Partner']);
    $unit = InventoryUnit::factory()->create([
        'phone_model_id' => $model->id,
        'dealer_id' => $dealer->id,
        'purchase_price' => 420_000,
        'grading' => 'Grade A',
    ]);

    $mapped = (new InventoryExport)->map($unit->fresh(['phoneModel.brand', 'dealer']));

    expect($mapped[1])->toBe('Tecno')
        ->and($mapped[2])->toBe('Spark 20')
        ->and((float) $mapped[5])->toBe(420_000.0)
        ->and($mapped[7])->toBe('Kariakoo Partner')
        ->and($mapped[8])->toBe('Grade A');
});

it('renders loan agreements with current loan and inventory fields', function () {
    ['loan' => $loan] = createReportingLoanFixture([
        'loan_number' => 'LN-REPORT-001',
        'principal_amount' => 500_000,
        'total_payable' => 560_000,
        'total_debt' => 560_000,
    ]);

    RepaymentSchedule::factory()->create(['loan_id' => $loan->id]);

    $response = app(DocumentService::class)->generateLoanAgreement($loan);

    expect($response->headers->get('content-disposition'))->toContain('LN-REPORT-001');
});

it('uses total payable and paid interest in roi analytics', function () {
    Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');
    Sanctum::actingAs($user);

    ['loan' => $loan] = createReportingLoanFixture([
        'status' => 'active',
        'principal_amount' => 100_000,
        'total_payable' => 120_000,
        'total_debt' => 120_000,
    ]);

    RepaymentSchedule::factory()->create([
        'loan_id' => $loan->id,
        'status' => 'paid',
        'amount_paid' => 30_000,
        'interest_component' => 5_000,
        'paid_at' => today()->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/analytics/roi-analysis');

    $response->assertOk()
        ->assertJsonPath('data.capital_deployed', 'TZS 100,000')
        ->assertJsonPath('data.expected_maturity_returns', 'TZS 120,000')
        ->assertJsonPath('data.interest_captured', 'TZS 5,000')
        ->assertJsonPath('data.projected_roi', '20%');
});

it('builds daily digest metrics from transactions and disbursement dates', function () {
    ['loan' => $loan, 'customer' => $customer] = createReportingLoanFixture([
        'principal_amount' => 300_000,
        'disbursed_at' => today()->toDateString(),
    ]);

    Transaction::factory()->create([
        'loan_id' => $loan->id,
        'customer_id' => $customer->id,
        'amount' => 75_000,
        'type' => 'repayment',
        'entry_type' => 'credit',
        'transacted_at' => now(),
    ]);

    Loan::factory()->create([
        'status' => 'defaulted',
        'disbursed_at' => today()->subDay()->toDateString(),
        'updated_at' => now(),
    ]);

    $message = app(ReportGenerationService::class)->generateDailyDigestMessage();

    expect($message)->toContain('Total Collections: TZS 75,000')
        ->and($message)->toContain('Capital Disbursed: TZS 300,000')
        ->and($message)->toContain('New Defaults Logged: 1 units');
});

it('records daily digest command output without pretending to send email', function () {
    $this->artisan('opticedge:daily-digest')
        ->expectsOutput('Compiling End of Day Business Digest...')
        ->expectsOutput('Digest Complete. 0 executive digests recorded.')
        ->assertExitCode(0);
});

it('generates dealer cashier reports from loan-linked repayment transactions', function () {
    ['dealer' => $dealer, 'loan' => $loan, 'customer' => $customer] = createReportingLoanFixture();
    ['loan' => $otherLoan, 'customer' => $otherCustomer] = createReportingLoanFixture();

    Transaction::factory()->create([
        'loan_id' => $loan->id,
        'customer_id' => $customer->id,
        'amount' => 90_000,
        'type' => 'repayment',
        'entry_type' => 'credit',
        'channel' => 'cash',
        'transacted_at' => now(),
    ]);
    Transaction::factory()->create([
        'loan_id' => $otherLoan->id,
        'customer_id' => $otherCustomer->id,
        'amount' => 10_000,
        'type' => 'repayment',
        'entry_type' => 'credit',
        'channel' => 'cash',
        'transacted_at' => now(),
    ]);

    $report = app(TaxService::class)->generateDailyCashierReport($dealer->id);

    expect((float) $report['total_collections'])->toBe(90_000.0)
        ->and((float) $report['methods']['cash'])->toBe(90_000.0);
});

it('locks overdue devices using pending repayment schedules', function () {
    ['loan' => $loan, 'unit' => $unit] = createReportingLoanFixture([
        'status' => 'active',
    ], [
        'mdm_id' => 'MDM-001',
        'lock_status' => 'unlocked',
    ]);

    RepaymentSchedule::factory()->create([
        'loan_id' => $loan->id,
        'due_date' => today()->subDays(5)->toDateString(),
        'status' => 'pending',
    ]);

    app(DeviceLockingService::class)->secureOverdueDevices(3);

    expect($unit->fresh()->lock_status)->toBe('locked');
});

it('records refurbishment costs with the current transaction schema', function () {
    ['unit' => $unit] = createReportingLoanFixture([], [
        'status' => 'recovered',
        'repair_cost' => 1_000,
    ]);

    $freshUnit = app(RefurbishmentService::class)->processRefurbishment(
        $unit,
        2_500,
        'Grade B',
        'Screen replacement'
    );

    $transaction = Transaction::where('type', 'operational_cost')->firstOrFail();

    expect($freshUnit->status)->toBe('available')
        ->and((float) $freshUnit->repair_cost)->toBe(3_500.0)
        ->and($transaction->entry_type)->toBe('debit')
        ->and($transaction->channel)->toBe('internal')
        ->and($transaction->meta['inventory_unit_id'])->toBe($unit->id);
});

it('creates pending schedules when approving and disbursing a loan', function () {
    Queue::fake();

    $user = User::factory()->create();
    actingAs($user);

    ['loan' => $loan, 'unit' => $unit] = createReportingLoanFixture([
        'status' => 'pending',
        'duration_weeks' => 4,
        'repayment_frequency' => 'weekly',
    ], [
        'status' => 'available',
    ]);

    $freshLoan = app(LoanManagementService::class)->approveAndDisburse($loan);

    expect($freshLoan->status)->toBe('active')
        ->and($unit->fresh()->status)->toBe('sold')
        ->and($freshLoan->repaymentSchedules)->toHaveCount(4)
        ->and($freshLoan->repaymentSchedules->pluck('status')->unique()->values()->all())->toBe(['pending']);
});

it('fails fast when a non-log sms driver is configured without an implementation', function () {
    Config::set('services.sms.driver', 'unsupported');

    expect(fn () => (new SendSmsJob('255700000000', 'Test message'))->handle())
        ->toThrow(RuntimeException::class, 'SMS gateway driver [unsupported] is not implemented.');
});

function createReportingLoanFixture(array $loanOverrides = [], array $unitOverrides = []): array
{
    $brand = Brand::factory()->create();
    $model = PhoneModel::factory()->create(['brand_id' => $brand->id]);
    $dealer = Dealer::factory()->create();
    $customer = Customer::factory()->create([
        'dealer_id' => $dealer->id,
    ]);
    $unit = InventoryUnit::factory()->create(array_merge([
        'phone_model_id' => $model->id,
        'dealer_id' => $dealer->id,
    ], $unitOverrides));
    $loan = Loan::factory()->create(array_merge([
        'customer_id' => $customer->id,
        'inventory_unit_id' => $unit->id,
        'dealer_id' => $dealer->id,
    ], $loanOverrides));

    return [
        'brand' => $brand,
        'model' => $model,
        'dealer' => $dealer,
        'customer' => $customer,
        'unit' => $unit,
        'loan' => $loan,
    ];
}
