<?php

use App\Livewire\Notifications\AlertBell;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\User;
use App\Models\Verification;
use App\Services\DashboardNotificationService;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
});

it('returns live dashboard notifications with customer and device context', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['loans.view', 'reports.view', 'dashboard.view']);

    $customer = Customer::factory()->create([
        'first_name' => 'Amina',
        'last_name' => 'Juma',
        'phone' => '0712345678',
        'email' => 'amina@example.com',
        'nida_number' => '20012345678901234567',
        'imei_number' => '356789012345678',
        'device_specs' => 'Samsung Galaxy A15 6/128',
        'kyc_status' => 'approved',
        'asset_release_status' => 'pending',
        'deposit_payment_status' => 'completed',
        'agreement_accepted' => true,
        'customer_signature_path' => 'kyc/customer-signatures/test.png',
        'fo_signature_path' => 'kyc/fo-signatures/test.png',
        'asset_handover_list_path' => 'kyc/handover/test.pdf',
    ]);

    Loan::factory()->create([
        'customer_id' => $customer->id,
        'loan_number' => 'LN-TEST-001',
        'status' => 'overdue',
        'remaining_balance' => 250000,
        'outstanding_balance' => 250000,
    ]);

    Verification::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'kyc',
        'status' => 'pending',
        'face_match_status' => 'review',
        'face_match_score' => 0.38,
        'stage' => 2,
    ]);

    $feed = app(DashboardNotificationService::class)->feed($user);

    expect($feed['count'])->toBeGreaterThan(0)
        ->and($feed['items'])->not->toBeEmpty();

    $loanAlert = collect($feed['items'])->firstWhere('category', 'loan_risk');
    expect($loanAlert)->not->toBeNull()
        ->and($loanAlert['customer_name'])->toBe('Amina Juma')
        ->and($loanAlert['customer_phone'])->toBe('0712345678')
        ->and($loanAlert['customer_email'])->toBe('amina@example.com')
        ->and($loanAlert['nida_number'])->toBe('20012345678901234567')
        ->and($loanAlert['imei'])->toBe('356789012345678')
        ->and($loanAlert['device'])->toContain('Samsung');
});

it('exposes dashboard notifications through the authenticated json endpoint', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['loans.view', 'reports.view', 'dashboard.view']);

    $customer = Customer::factory()->create([
        'first_name' => 'Peter',
        'last_name' => 'Mushi',
        'phone' => '0755000111',
        'kyc_status' => 'approved',
        'asset_release_status' => 'pending',
        'deposit_payment_status' => 'completed',
        'agreement_accepted' => true,
        'customer_signature_path' => 'kyc/customer-signatures/test.png',
        'fo_signature_path' => 'kyc/fo-signatures/test.png',
        'asset_handover_list_path' => 'kyc/handover/test.pdf',
    ]);

    Loan::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'defaulted',
        'remaining_balance' => 120000,
    ]);

    $this->actingAs($user)
        ->getJson(route('dashboard.notifications'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'count',
                'items' => [
                    ['id', 'category', 'title', 'summary', 'customer_name', 'url'],
                ],
            ],
        ]);
});

it('renders the alert bell with live notification data', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['loans.view', 'dashboard.view']);

    $customer = Customer::factory()->create(['first_name' => 'Live', 'last_name' => 'Bell']);
    Loan::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'overdue',
        'remaining_balance' => 90000,
    ]);

    Livewire\Livewire::actingAs($user)
        ->test(AlertBell::class)
        ->assertSee('System Notifications')
        ->assertSee('Live Bell');
});
