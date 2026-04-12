<?php

use App\Models\Customer;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->customer = Customer::factory()->create([
        'phone' => '255712345678',
        'pin' => '1234',
        'asset_release_status' => 'released',
        'kyc_status' => 'approved',
    ]);
});

test('check-phone returns has_pin for existing customer', function () {
    postJson('/api/v1/customer/check-phone', ['phone' => '0712345678'])
        ->assertOk()
        ->assertJsonPath('data.has_pin', true)
        ->assertJsonPath('data.customer_name', $this->customer->first_name);
});

test('check-phone fails for unknown number', function () {
    postJson('/api/v1/customer/check-phone', ['phone' => '0799999999'])
        ->assertUnprocessable();
});

test('set-pin works for customer without pin', function () {
    $noPinCustomer = Customer::factory()->create([
        'phone' => '255799000111',
        'pin' => null,
        'asset_release_status' => 'released',
        'kyc_status' => 'approved',
    ]);

    postJson('/api/v1/customer/set-pin', [
        'phone' => '255799000111',
        'new_pin' => '5566',
        'new_pin_confirmation' => '5566',
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['token', 'customer']]);

    $noPinCustomer->refresh();
    expect(Hash::check('5566', $noPinCustomer->pin))->toBeTrue();
});

test('set-pin rejected if customer already has pin', function () {
    postJson('/api/v1/customer/set-pin', [
        'phone' => '0712345678',
        'new_pin' => '9999',
        'new_pin_confirmation' => '9999',
    ])->assertUnprocessable();
});

test('customer can login with phone and pin', function () {
    postJson('/api/v1/customer/login', [
        'phone' => '0712345678',
        'pin' => '1234',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token', 'customer' => ['id', 'full_name', 'phone']]]);
});

test('customer cannot login with wrong pin', function () {
    postJson('/api/v1/customer/login', [
        'phone' => '0712345678',
        'pin' => '9999',
    ])->assertUnprocessable();
});

test('customer without released asset cannot login', function () {
    $this->customer->update(['asset_release_status' => 'pending']);

    postJson('/api/v1/customer/login', [
        'phone' => '0712345678',
        'pin' => '1234',
    ])->assertUnprocessable();
});

test('authenticated customer can fetch profile', function () {
    $token = $this->customer->createToken('customer-app', ['customer-portal'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/customer/profile')
        ->assertOk()
        ->assertJsonPath('data.id', $this->customer->id);
});

test('authenticated customer can change pin', function () {
    $token = $this->customer->createToken('customer-app', ['customer-portal'])->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/v1/customer/pin', [
            'current_pin' => '1234',
            'new_pin' => '5678',
            'new_pin_confirmation' => '5678',
        ])
        ->assertOk();

    $this->customer->refresh();
    expect(Hash::check('5678', $this->customer->pin))->toBeTrue();
});

test('authenticated customer can view active loan', function () {
    $token = $this->customer->createToken('customer-app', ['customer-portal'])->plainTextToken;

    Loan::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'active',
    ]);

    $this->withToken($token)
        ->getJson('/api/v1/customer/loan')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['id', 'loan_number', 'total_payable', 'amount_paid', 'remaining_balance']]);
});

test('authenticated customer can view repayment schedule', function () {
    $token = $this->customer->createToken('customer-app', ['customer-portal'])->plainTextToken;

    $loan = Loan::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'active',
    ]);

    RepaymentSchedule::factory()
        ->count(4)
        ->sequence(
            ['installment_number' => 1, 'due_date' => now()->addWeek()],
            ['installment_number' => 2, 'due_date' => now()->addWeeks(2)],
            ['installment_number' => 3, 'due_date' => now()->addWeeks(3)],
            ['installment_number' => 4, 'due_date' => now()->addWeeks(4)],
        )
        ->create(['loan_id' => $loan->id]);

    $this->withToken($token)
        ->getJson('/api/v1/customer/loan/schedule')
        ->assertOk()
        ->assertJsonStructure(['data' => ['loan_id', 'schedule']]);
});

test('authenticated customer can view device info', function () {
    $token = $this->customer->createToken('customer-app', ['customer-portal'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/customer/device')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('authenticated customer can logout', function () {
    $token = $this->customer->createToken('customer-app', ['customer-portal'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/customer/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->customer->tokens()->count())->toBe(0);
});

test('unauthenticated access is rejected', function () {
    $this->getJson('/api/v1/customer/profile')->assertUnauthorized();
    $this->getJson('/api/v1/customer/loan')->assertUnauthorized();
    $this->getJson('/api/v1/customer/device')->assertUnauthorized();
});
