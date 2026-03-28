<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\FraudDetectionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class FraudApiController extends Controller
{
    use ApiResponse;

    /**
     * API Hook scanning new applicants for multi-tenant footprint flags
     */
    public function scanApplication(Customer $customer, FraudDetectionService $fraudService): JsonResponse
    {
        $metrics = $fraudService->checkApplicationIntegrity($customer);

        // Security override layer block if critical logic breaches
        if ($metrics['risk_level'] === 'critical') {
            return $this->errorResponse("Fraud System: Critical overlaps detected. Applicant suspended.", 403, $metrics);
        }

        return $this->successResponse($metrics, "Background checks completed successfully.");
    }
}
