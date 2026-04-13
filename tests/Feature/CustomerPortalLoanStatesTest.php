<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\PhoneModel;
use App\Models\RepaymentSchedule;

beforeEach(function () {
    $this->branch = Branch::factory()->create();
    $this->phoneModel = PhoneModel::factory()->create();
    $this->inventoryUnit = InventoryUnit::factory()->create([
        'phone_model_id' => $this->phoneModel->id,
        'branch_id' => $this->branch->id,
        'status' => 'assigned',
    ]);

    $this->customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'phone_model_id' => $this->phoneModel->id,
        'inventory_unit_id' => $this->inventoryUnit->id,
        'phone' => '255712345678',
        'pin' => '1234',
        'asset_release_status' => 'released',
        'asset_released_at' => now(),
        'kyc_status' => 'approved',
    ]);

    $this->token = $this->customer
        ->createToken('customer-app', ['customer-portal'])
        ->plainTextToken;
});

test('released customer without disbursed loan gets explicit pending-disbursement state', function () {
    $this->withToken($this->token)
        ->getJson('/api/v1/customer/loan')
        ->assertOk()
        ->assertJsonPath('data.portal_state', 'released_pending_disbursement')
        ->assertJsonPath('data.loan', null)
        ->assertJsonPath(
            'data.portal_message',
            'Your device has been released, but your loan account is still being prepared.',
        );
});

test('released customer with enough terms is auto-provisioned for the customer portal', function () {
    $this->customer->update([
        'cash_price' => 560000,
        'deposit_amount' => 500,
        'preferred_repayment' => 'weekly',
    ]);

    $this->withToken($this->token)
        ->getJson('/api/v1/customer/loan')
        ->assertOk()
        ->assertJsonPath('data.portal_state', 'loan_active')
        ->assertJsonPath('data.loan.repayment_frequency', 'weekly');

    $loan = Loan::query()
        ->where('customer_id', $this->customer->id)
        ->where('status', 'active')
        ->first();

    expect($loan)->not->toBeNull()
        ->and((float) $loan?->deposit_paid)->toBe(500.0)
        ->and((float) $loan?->principal_amount)->toBe(560000.0)
        ->and(RepaymentSchedule::query()->where('loan_id', $loan?->id)->count())->toBeGreaterThan(0);
});

test('payment endpoint blocks released customer whose loan account is not ready yet', function () {
    $this->withToken($this->token)
        ->postJson('/api/v1/customer/loan/pay', [
            'amount' => 10000,
            'phone' => '0712345678',
        ])
        ->assertStatus(409)
        ->assertJsonPath(
            'message',
            'Your device has been released, but your loan account is still being prepared.',
        );
});
