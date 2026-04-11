<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FinancialComplianceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ComplianceApiController extends Controller
{
    use ApiResponse;

    /**
     * Retrieve IFRS 9 Compliance Stage outputs + ECL requirements natively matrix computed.
     */
    public function report(FinancialComplianceService $complianceEngine): JsonResponse
    {
        $complianceEngine->calculateProvisioning();

        $eclMatrix = $complianceEngine->generateECLReport();

        return $this->successResponse($eclMatrix, 'IFRS 9 Provisioning data successfully matrixed.');
    }
}
