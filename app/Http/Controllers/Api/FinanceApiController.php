<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\LoanManagementService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceApiController extends Controller
{
    use ApiResponse;

    /**
     * Get an Early Settlement Quote
     */
    public function settlementQuote(Request $request, string $loanId, LoanManagementService $loanService): JsonResponse
    {
        $loan = Loan::findOrFail($loanId);

        if ($loan->status === 'completed') {
            return $this->errorResponse("Loan is already fully paid.", 400);
        }

        $quote = $loanService->getEarlySettlementQuote($loan);

        return $this->successResponse($quote, "Early settlement quote calculated.");
    }
}
