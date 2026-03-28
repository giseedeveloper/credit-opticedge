<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Services\DeviceLockingService;
use App\Services\PaymentProcessingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin Security & Overrides
 *
 * Highly secured endpoints for HQ Admins to manually verify payloads or lock devices.
 */
class SecurityApiController extends Controller
{
    use ApiResponse;

    /**
     * Force Lock Device
     */
    public function lockDevice(Request $request, string $unitId, DeviceLockingService $mdm): JsonResponse
    {
        $unit = InventoryUnit::findOrFail($unitId);
        $reason = $request->input('reason', 'Manual Admin Override');

        $success = $mdm->lockDevice($unit, $reason);

        if (!$success) {
            return $this->errorResponse("Device lacks MDM integration or is already locked", 422);
        }

        return $this->successResponse($unit->fresh(), "Command issued globally.");
    }

    /**
     * Force Unlock Device
     */
    public function unlockDevice(Request $request, string $unitId, DeviceLockingService $mdm): JsonResponse
    {
        $unit = InventoryUnit::findOrFail($unitId);
        $reason = $request->input('reason', 'Manual Admin Override');

        $success = $mdm->unlockDevice($unit, $reason);

        if (!$success) {
            return $this->errorResponse("Device is already unlocked or lacks MDM", 422);
        }

        return $this->successResponse($unit->fresh(), "Unlock command issued successfully.");
    }

    /**
     * Manual Payment Reconciliation Override
     * 
     * Admins force-allocate a payment that failed webhook mapping (e.g. wrong account number).
     */
    public function manualReconciliation(Request $request, PaymentProcessingService $paymentService): JsonResponse
    {
        $request->validate([
            'loan_id' => 'required|uuid|exists:loans,id',
            'amount' => 'required|numeric',
            'reference' => 'required|string',
            'method' => 'required|string',
            'override_reason' => 'required|string'
        ]);

        $loan = Loan::findOrFail($request->loan_id);

        $transaction = $paymentService->recordPayment(
            $loan,
            (float) $request->amount,
            $request->reference,
            $request->method
        );

        // Security Log mapping to Admin
        activity('security')
            ->performedOn($transaction)
            ->causedBy(auth()->user())
            ->event('manual_reconcile')
            ->withProperties(['reason' => $request->override_reason])
            ->log("Manual payment override processed by HQ.");

        return $this->successResponse($transaction, "Manual payment successfully reconciled.");
    }
}
