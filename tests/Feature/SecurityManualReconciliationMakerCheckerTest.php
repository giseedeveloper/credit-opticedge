<?php

use App\Models\Loan;
use App\Models\ManualReconciliationRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate('admin', 'web');
});

test('manual reconciliation requires checker approval before posting payment', function () {
    $maker = User::factory()->create();
    $maker->assignRole('admin');

    $checker = User::factory()->create();
    $checker->assignRole('admin');

    $loan = Loan::factory()->create([
        'status' => 'active',
        'outstanding_balance' => 500000,
        'remaining_balance' => 500000,
    ]);

    Sanctum::actingAs($maker);
    $reference = 'MANUAL-REQ-'.Str::upper(Str::random(10));

    $requestResponse = $this->postJson('/api/v1/security/reconcile/manual', [
        'loan_id' => $loan->id,
        'amount' => 20000,
        'reference' => $reference,
        'method' => 'bank',
        'override_reason' => 'Customer used wrong account and paid at branch.',
    ]);

    $requestResponse
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('meta.event', 'manual_reconcile.requested')
        ->assertJsonPath('meta.trace_id', fn ($traceId) => is_string($traceId) && $traceId !== '');

    expect(ManualReconciliationRequest::query()->count())->toBe(1)
        ->and(ManualReconciliationRequest::query()->value('processed_transaction_id'))->toBeNull();

    Sanctum::actingAs($checker);
    $approvalResponse = $this->postJson(sprintf(
        '/api/v1/security/reconcile/manual/%s/approve',
        ManualReconciliationRequest::query()->value('id')
    ), [
        'review_note' => 'Approved after checking bank statement.',
    ]);

    $approvalResponse
        ->assertOk()
        ->assertJsonPath('data.reference', $reference)
        ->assertJsonPath('meta.event', 'manual_reconcile.approved');

    $storedRequest = ManualReconciliationRequest::query()->firstOrFail();
    $loan->refresh();

    expect($storedRequest->status)->toBe('approved')
        ->and($storedRequest->processed_transaction_id)->not->toBeNull()
        ->and($storedRequest->reviewed_by)->toBe($checker->id)
        ->and((float) $loan->outstanding_balance)->toBe(480000.0);
});

test('maker cannot approve their own reconciliation request', function () {
    $maker = User::factory()->create();
    $maker->assignRole('admin');
    $loan = Loan::factory()->create([
        'status' => 'active',
        'outstanding_balance' => 300000,
        'remaining_balance' => 300000,
    ]);

    Sanctum::actingAs($maker);
    $reference = 'MANUAL-SELF-'.Str::upper(Str::random(8));

    $this->postJson('/api/v1/security/reconcile/manual', [
        'loan_id' => $loan->id,
        'amount' => 15000,
        'reference' => $reference,
        'method' => 'bank',
        'override_reason' => 'Temporary mismatch in automated mapping.',
    ])->assertOk();

    $requestId = (string) ManualReconciliationRequest::query()->value('id');

    $this->postJson("/api/v1/security/reconcile/manual/{$requestId}/approve")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Maker-checker rule violated: requester cannot approve their own request.')
        ->assertJsonPath('meta.error_code', 'manual_reconcile.invalid_state')
        ->assertJsonPath('meta.event', 'manual_reconcile.approve_failed');

    $storedRequest = ManualReconciliationRequest::query()->firstOrFail();

    expect($storedRequest->status)->toBe('pending')
        ->and($storedRequest->processed_transaction_id)->toBeNull();
});

test('checker can reject reconciliation request and no payment is posted', function () {
    $maker = User::factory()->create();
    $maker->assignRole('admin');

    $checker = User::factory()->create();
    $checker->assignRole('admin');

    $loan = Loan::factory()->create([
        'status' => 'active',
        'outstanding_balance' => 250000,
        'remaining_balance' => 250000,
    ]);

    Sanctum::actingAs($maker);
    $reference = 'MANUAL-REJ-'.Str::upper(Str::random(8));

    $this->postJson('/api/v1/security/reconcile/manual', [
        'loan_id' => $loan->id,
        'amount' => 12000,
        'reference' => $reference,
        'method' => 'bank',
        'override_reason' => 'Unsupported reference format from branch receipt.',
    ])->assertOk();

    $requestId = (string) ManualReconciliationRequest::query()->value('id');

    Sanctum::actingAs($checker);
    $this->postJson("/api/v1/security/reconcile/manual/{$requestId}/reject", [
        'review_note' => 'Rejected: amount mismatch with bank statement.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('meta.event', 'manual_reconcile.rejected');

    $storedRequest = ManualReconciliationRequest::query()->firstOrFail();
    $loan->refresh();

    expect($storedRequest->status)->toBe('rejected')
        ->and($storedRequest->processed_transaction_id)->toBeNull()
        ->and($storedRequest->reviewed_by)->toBe($checker->id)
        ->and((float) $loan->outstanding_balance)->toBe(250000.0);
});

test('admin can list and filter manual reconciliation requests', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $requester = User::factory()->create();
    $requester->assignRole('admin');

    $reviewer = User::factory()->create();
    $reviewer->assignRole('admin');

    $loanA = Loan::factory()->create();
    $loanB = Loan::factory()->create();

    ManualReconciliationRequest::query()->create([
        'loan_id' => $loanA->id,
        'requested_by' => $requester->id,
        'reviewed_by' => null,
        'amount' => 10000,
        'reference' => 'OPS-PENDING-001',
        'method' => 'bank',
        'override_reason' => 'Pending branch reconciliation',
        'status' => 'pending',
    ]);

    ManualReconciliationRequest::query()->create([
        'loan_id' => $loanB->id,
        'requested_by' => $requester->id,
        'reviewed_by' => $reviewer->id,
        'amount' => 15000,
        'reference' => 'OPS-APPROVED-001',
        'method' => 'bank',
        'override_reason' => 'Approved previously',
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/security/reconcile/manual?status=pending')
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.reference', 'OPS-PENDING-001')
        ->assertJsonPath('meta.event', 'manual_reconcile.listed');

    $this->getJson('/api/v1/security/reconcile/manual?reference=APPROVED')
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.reference', 'OPS-APPROVED-001');
});
