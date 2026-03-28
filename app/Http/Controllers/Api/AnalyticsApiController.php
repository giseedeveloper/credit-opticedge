<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AnalyticsApiController extends Controller
{
    use ApiResponse;

    /**
     * Map high default areas physically based on customer metadata GPS
     */
    public function riskMap(): JsonResponse
    {
        $nodes = Customer::whereHas('loans', function($query) {
            $query->whereIn('status', ['overdue', 'defaulted']);
        })->select('nida_number', 'latitude', 'longitude')
          ->get()
          ->map(function ($c) {
              return [
                  'lat' => $c->latitude,
                  'lng' => $c->longitude,
                  'weight' => 10 // Risk multiplier mapping
              ];
          });

        return $this->successResponse($nodes, "High-risk defaulted physical coordinates mapped securely.");
    }

    /**
     * Compute explicit investor ROI returns analyzing disbursed capital vs exact realized interest strings.
     */
    public function profitabilityAnalysis(): JsonResponse
    {
        // Total Base Capital Given
        $capitalDeployed = Loan::where('status', 'active')->sum('principal_amount');
        
        // Total Expected Returns
        $expectedReturns = Loan::where('status', 'active')->sum('total_amount_due');
        
        // Actual Interest Recovered
        $interestRecovered = DB::table('repayment_schedules')
                               ->where('status', 'paid')
                               ->sum('amount_paid');

        // Base ROI Mathematics
        $projectedRoi = 0;
        if ($capitalDeployed > 0) {
            $projectedRoi = round((($expectedReturns - $capitalDeployed) / $capitalDeployed) * 100, 2);
        }

        return $this->successResponse([
            'capital_deployed' => "TZS " . number_format($capitalDeployed),
            'expected_maturity_returns' => "TZS " . number_format($expectedReturns),
            'interest_captured' => "TZS " . number_format($interestRecovered),
            'projected_roi' => $projectedRoi . "%",
            'currency' => 'TZS (Tanzanian Shillings)'
        ], "Venture Capital macro profitability successfully computed.");
    }
}
