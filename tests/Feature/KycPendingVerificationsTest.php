<?php

use App\Livewire\Kyc\PendingVerifications;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Permission;
use App\Models\User;
use App\Models\Verification;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['loans.create', 'loans.view']);
});

it('prevents approving when no KYC verification exists', function () {
    actingAs($this->user);

    $customer = Customer::factory()->create();

    Livewire::test(PendingVerifications::class)
        ->set('actionCustomerId', $customer->id)
        ->set('actionStage', 1)
        ->call('approveStage')
        ->assertHasErrors(['verification']);
});

it('marks face match as manual_verified from review state', function () {
    actingAs($this->user);

    $dealer = Dealer::factory()->create();
    $customer = Customer::factory()->create(['dealer_id' => $dealer->id]);
    Verification::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'kyc',
        'status' => 'pending',
        'stage' => 1,
        'face_match_status' => 'review',
        'face_match_score' => 0.72,
    ]);

    Livewire::test(PendingVerifications::class)
        ->call('manualVerifyFaceMatch', $customer->id);

    $v = $customer->latestKycVerification()->first();
    expect($v)->not->toBeNull()
        ->and($v->face_match_status)->toBe('manual_verified')
        ->and($v->face_match_manual_verified_by)->toBe($this->user->id);
});

it('does not manual-verify face match when status is already passed', function () {
    actingAs($this->user);

    $dealer = Dealer::factory()->create();
    $customer = Customer::factory()->create(['dealer_id' => $dealer->id]);
    Verification::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'kyc',
        'status' => 'pending',
        'stage' => 1,
        'face_match_status' => 'passed',
        'face_match_score' => 0.95,
    ]);

    Livewire::test(PendingVerifications::class)
        ->call('manualVerifyFaceMatch', $customer->id);

    expect($customer->latestKycVerification->face_match_status)->toBe('passed');
});
