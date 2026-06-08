<?php

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\PhoneModel;
use App\Models\RepaymentSchedule;
use App\Services\ImeiLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('resolves brand and model from linked inventory when customer phone model is empty', function () {
    $dealer = Dealer::factory()->create(['name' => 'OpticEdge Kinondoni']);
    $brand = Brand::factory()->create(['name' => 'Samsung']);
    $model = PhoneModel::factory()->create(['brand_id' => $brand->id, 'name' => 'Galaxy A15']);
    $unit = InventoryUnit::factory()->create([
        'dealer_id' => $dealer->id,
        'phone_model_id' => $model->id,
        'imei_1' => '350798484278614',
        'status' => 'sold',
    ]);

    $customer = Customer::factory()->create([
        'inventory_unit_id' => $unit->id,
        'imei_number' => '350798484278614',
        'dealer_id' => $dealer->id,
        'phone' => '0678165524',
        'email' => 'test@example.com',
    ]);

    $loan = Loan::factory()->create([
        'customer_id' => $customer->id,
        'inventory_unit_id' => $unit->id,
        'dealer_id' => $dealer->id,
        'status' => 'active',
        'duration_weeks' => 52,
        'repayment_frequency' => 'monthly',
        'principal_amount' => 429000,
        'outstanding_balance' => 442980,
    ]);

    foreach ([1, 2, 3] as $number) {
        RepaymentSchedule::factory()->create([
            'loan_id' => $loan->id,
            'installment_number' => $number,
            'status' => 'paid',
        ]);
    }
    RepaymentSchedule::factory()->create([
        'loan_id' => $loan->id,
        'installment_number' => 4,
        'status' => 'pending',
    ]);

    $profile = app(ImeiLookupService::class)->lookup('350798484278614');

    expect($profile['match'])->toBe('customer')
        ->and($profile['device']['brand'])->toBe('Samsung')
        ->and($profile['device']['model'])->toBe('Galaxy A15')
        ->and($profile['dealer']['name'])->toBe('OpticEdge Kinondoni')
        ->and($profile['loan_summary']['duration_weeks'])->toBe(52)
        ->and($profile['loan_summary']['installments_paid'])->toBe(3)
        ->and($profile['loan_summary']['installments_total'])->toBe(4);
});
