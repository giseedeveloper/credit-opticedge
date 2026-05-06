<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\ManualReconciliationRequest;
use App\Services\DeviceLockingService;
use App\Services\PaymentProcessingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * @group Admin Security & Overrides
 *
 * Highly secured endpoints for HQ Admins to manually verify payloads or lock devices.
 */
class SecurityApiController extends Controller
{
    use ApiResponse;

    public function listManualReconciliations(Request $request): JsonResponse
    {
        $traceId = $this->newTraceId();
        $request->validate([
            'status' => 'nullable|string|in:pending,approved,rejected',
            'reference' => 'nullable|string|max:80',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ManualReconciliationRequest::query()
            ->with(['loan:id,loan_number,customer_id', 'requestedBy:id,name,email', 'reviewedBy:id,name,email'])
            ->latest();

        $status = $request->string('status')->toString();
        if ($status !== '') {
            $query->where('status', $status);
        }

        $reference = trim((string) $request->input('reference', ''));
        if ($reference !== '') {
            $query->where('reference', 'like', '%'.$reference.'%');
        }

        $perPage = (int) $request->input('per_page', 20);
        $requests = $query->paginate($perPage);

        return $this->successWithMeta(
            $this->successResponse($requests, 'Manual reconciliation requests retrieved successfully.'),
            $traceId,
            'manual_reconcile.listed'
        );
    }

    /**
     * Force Lock Device
     */
    public function lockDevice(Request $request, string $unitId, DeviceLockingService $mdm): JsonResponse
    {
        $unit = InventoryUnit::findOrFail($unitId);
        $reason = $request->input('reason', 'Manual Admin Override');

        $success = $mdm->lockDevice($unit, $reason);

        if (! $success) {
            return $this->errorResponse('Device lacks MDM integration or is already locked', 422);
        }

        return $this->successResponse($unit->fresh(), 'Command issued globally.');
    }

    /**
     * Force Unlock Device
     */
    public function unlockDevice(Request $request, string $unitId, DeviceLockingService $mdm): JsonResponse
    {
        $unit = InventoryUnit::findOrFail($unitId);
        $reason = $request->input('reason', 'Manual Admin Override');

        $success = $mdm->unlockDevice($unit, $reason);

        if (! $success) {
            return $this->errorResponse('Device is already unlocked or lacks MDM', 422);
        }

        return $this->successResponse($unit->fresh(), 'Unlock command issued successfully.');
    }

    /**
     * Manual Payment Reconciliation Override
     *
     * Admins force-allocate a payment that failed webhook mapping (e.g. wrong account number).
     */
    public function manualReconciliation(Request $request): JsonResponse
    {
        $traceId = $this->newTraceId();
        $request->validate([
            'loan_id' => 'required|uuid|exists:loans,id',
            'amount' => 'required|numeric',
            'reference' => 'required|string',
            'method' => 'required|string',
            'override_reason' => 'required|string',
        ]);

        $loan = Loan::query()->findOrFail($request->string('loan_id'));
        $maker = $request->user();
        $reference = trim((string) $request->input('reference'));

        $reconciliationRequest = ManualReconciliationRequest::query()->create([
            'loan_id' => $loan->id,
            'requested_by' => $maker?->id,
            'amount' => round((float) $request->input('amount'), 2),
            'reference' => $reference,
            'method' => strtolower(trim((string) $request->input('method'))),
            'override_reason' => trim((string) $request->input('override_reason')),
            'status' => 'pending',
            'request_snapshot' => [
                'loan_id' => $loan->id,
                'amount' => round((float) $request->input('amount'), 2),
                'reference' => $reference,
                'method' => (string) $request->input('method'),
                'override_reason' => trim((string) $request->input('override_reason')),
            ],
        ]);

        activity('security')
            ->performedOn($reconciliationRequest)
            ->causedBy($maker)
            ->event('manual_reconcile_requested')
            ->withProperties([
                'trace_id' => $traceId,
                'loan_id' => $loan->id,
                'amount' => $reconciliationRequest->amount,
                'reference' => $reconciliationRequest->reference,
            ])
            ->log('Manual payment reconciliation request submitted.');

        return $this->successWithMeta(
            $this->successResponse(
                $reconciliationRequest->fresh(),
                'Manual reconciliation request submitted and awaiting checker approval.'
            ),
            $traceId,
            'manual_reconcile.requested'
        );
    }

    public function approveManualReconciliation(
        Request $request,
        string $requestId,
        PaymentProcessingService $paymentService
    ): JsonResponse {
        $traceId = $this->newTraceId();
        $request->validate([
            'review_note' => 'nullable|string',
        ]);

        $checker = $request->user();

        if (! $checker) {
            return $this->errorWithMeta(
                $this->errorResponse('Unauthorized', 401),
                $traceId,
                'auth.unauthorized',
                'manual_reconcile.approve_failed'
            );
        }

        try {
            $transaction = DB::transaction(function () use ($requestId, $checker, $paymentService, $request) {
                $pending = ManualReconciliationRequest::query()
                    ->whereKey($requestId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($pending->status !== 'pending') {
                    throw new InvalidArgumentException('This reconciliation request has already been reviewed.');
                }

                if ($pending->requested_by === $checker->id) {
                    throw new InvalidArgumentException('Maker-checker rule violated: requester cannot approve their own request.');
                }

                $loan = Loan::query()->findOrFail($pending->loan_id);

                $transaction = $paymentService->recordPayment(
                    $loan,
                    (float) $pending->amount,
                    $pending->reference,
                    $pending->method,
                    [
                        'override_reason' => $pending->override_reason,
                        'description' => 'Manual payment reconciliation override (approved)',
                        'maker_checker' => [
                            'request_id' => $pending->id,
                            'requested_by' => $pending->requested_by,
                            'approved_by' => $checker->id,
                        ],
                    ]
                );

                $pending->update([
                    'status' => 'approved',
                    'reviewed_by' => $checker->id,
                    'review_note' => $request->string('review_note')->toString() ?: null,
                    'approved_at' => now(),
                    'processed_transaction_id' => $transaction->id,
                ]);

                return $transaction;
            });
        } catch (InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'Duplicate') ? 409 : 422;
            $errorCode = str_contains($e->getMessage(), 'Duplicate')
                ? 'manual_reconcile.duplicate_reference'
                : 'manual_reconcile.invalid_state';

            return $this->errorWithMeta(
                $this->errorResponse($e->getMessage(), $status),
                $traceId,
                $errorCode,
                'manual_reconcile.approve_failed'
            );
        } catch (Throwable $e) {
            return $this->errorWithMeta(
                $this->errorResponse('Manual reconciliation approval failed.', 500),
                $traceId,
                'manual_reconcile.approval_error',
                'manual_reconcile.approve_failed'
            );
        }

        activity('security')
            ->performedOn($transaction)
            ->causedBy($checker)
            ->event('manual_reconcile_approved')
            ->withProperties([
                'trace_id' => $traceId,
                'transaction_id' => $transaction->id,
                'reference' => $transaction->reference,
            ])
            ->log('Manual payment reconciliation approved by checker.');

        return $this->successWithMeta(
            $this->successResponse($transaction, 'Manual reconciliation approved and payment posted.'),
            $traceId,
            'manual_reconcile.approved'
        );
    }

    public function rejectManualReconciliation(Request $request, string $requestId): JsonResponse
    {
        $traceId = $this->newTraceId();
        $request->validate([
            'review_note' => 'required|string',
        ]);

        $checker = $request->user();

        if (! $checker) {
            return $this->errorWithMeta(
                $this->errorResponse('Unauthorized', 401),
                $traceId,
                'auth.unauthorized',
                'manual_reconcile.reject_failed'
            );
        }

        try {
            $reconciliation = DB::transaction(function () use ($requestId, $checker, $request) {
                $pending = ManualReconciliationRequest::query()
                    ->whereKey($requestId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($pending->status !== 'pending') {
                    throw new InvalidArgumentException('This reconciliation request has already been reviewed.');
                }

                if ($pending->requested_by === $checker->id) {
                    throw new InvalidArgumentException('Maker-checker rule violated: requester cannot reject their own request.');
                }

                $pending->update([
                    'status' => 'rejected',
                    'reviewed_by' => $checker->id,
                    'review_note' => trim((string) $request->input('review_note')),
                    'rejected_at' => now(),
                ]);

                return $pending->fresh();
            });
        } catch (InvalidArgumentException $e) {
            return $this->errorWithMeta(
                $this->errorResponse($e->getMessage(), 422),
                $traceId,
                'manual_reconcile.invalid_state',
                'manual_reconcile.reject_failed'
            );
        } catch (Throwable $e) {
            return $this->errorWithMeta(
                $this->errorResponse('Manual reconciliation rejection failed.', 500),
                $traceId,
                'manual_reconcile.rejection_error',
                'manual_reconcile.reject_failed'
            );
        }

        activity('security')
            ->performedOn($reconciliation)
            ->causedBy($checker)
            ->event('manual_reconcile_rejected')
            ->withProperties([
                'trace_id' => $traceId,
                'request_id' => $reconciliation->id,
                'reference' => $reconciliation->reference,
            ])
            ->log('Manual payment reconciliation rejected by checker.');

        return $this->successWithMeta(
            $this->successResponse($reconciliation, 'Manual reconciliation request rejected.'),
            $traceId,
            'manual_reconcile.rejected'
        );
    }

    private function newTraceId(): string
    {
        return (string) str()->uuid();
    }

    private function successWithMeta(JsonResponse $response, string $traceId, string $event): JsonResponse
    {
        $payload = $response->getData(true);
        $payload['meta'] = [
            'trace_id' => $traceId,
            'event' => $event,
        ];
        $response->setData($payload);

        return $response;
    }

    private function errorWithMeta(
        JsonResponse $response,
        string $traceId,
        string $errorCode,
        string $event
    ): JsonResponse {
        $payload = $response->getData(true);
        $payload['meta'] = [
            'trace_id' => $traceId,
            'event' => $event,
            'error_code' => $errorCode,
        ];
        $response->setData($payload);

        return $response;
    }
}
