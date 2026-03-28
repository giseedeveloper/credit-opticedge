<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\CommissionLedger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Staff & Commissions
 *
 * Exposes real-time sales performance metrics.
 */
class StaffApiController extends Controller
{
    use ApiResponse;

    /**
     * Agent Metrics Dashboard
     * 
     * Load counts of customers boarded, active loans generated, and overall performance.
     */
    public function metrics(Request $request): JsonResponse
    {
        $user = $request->user();

        // Count how many customers or loans this specific agent onboarded
        $totalCustomers = Loan::where('created_by', $user->id)
            ->distinct('customer_id')
            ->count();

        $activeLoans = Loan::where('created_by', $user->id)
            ->where('status', 'active')
            ->count();

        $metrics = [
            'total_customers_acquired' => $totalCustomers,
            'active_loans_managed'     => $activeLoans,
        ];

        return $this->successResponse($metrics, "Agent performance metrics gathered.");
    }

    /**
     * Commission History
     * 
     * View history if this user is a Vendor Owner mapped to a Ledger.
     */
    public function commissions(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ensure only Vendor Owners or similar roles retrieve this
        // For simplicity, assumed user is linked to vendor:
        $vendorId = $user->branch->vendor_id ?? null;

        if (!$vendorId) {
            return $this->errorResponse("You do not have a mapped Vendor structure to earn commissions.", 403);
        }

        $ledgers = CommissionLedger::where('vendor_id', $vendorId)
            ->orderBy('posted_at', 'desc')
            ->paginate(15);

        return $this->successResponse($ledgers, "Commission ledger extracted.");
    }
}
