<?php

namespace App\Services;

use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class FinancialComplianceService
{
    /**
     * IFRS 9: Update DPD (Days Past Due) and map to IFRS Stages natively using postgres features.
     * Can process a large multi-tenant deployment concurrently.
     */
    public function calculateProvisioning(): void
    {
        DB::transaction(function () {
            // Update Days Past Due mathematically to avoid active memory overhead
            DB::statement("
                UPDATE loans
                SET dpd = GREATEST(0, (CURRENT_DATE - (
                    SELECT MIN(due_date) FROM repayment_schedules 
                    WHERE loan_id = loans.id AND status IN ('unpaid', 'partial', 'overdue')
                ))::integer)
                WHERE status IN ('active', 'defaulted')
            ");

            // Recalculate Stages (IFRS 9 Standard)
            // Stage 1: Performing (DPD 0 - 29)
            // Stage 2: Underperforming (DPD 30 - 89)
            // Stage 3: Non-performing (DPD 90+)
            DB::statement("
                UPDATE loans SET ifrs_stage = CASE
                    WHEN dpd >= 90 THEN 3
                    WHEN dpd >= 30 THEN 2
                    ELSE 1
                END
                WHERE status IN ('active', 'defaulted')
            ");
        });
    }

    /**
     * Extract Expected Credit Loss metric.
     * Assuming baseline logic:
     * Stage 1 ECL = 1.5%
     * Stage 2 ECL = 15.0%
     * Stage 3 ECL = 60.0%
     */
    public function generateECLReport(): array
    {
        $loans = Loan::whereIn('status', ['active', 'defaulted'])
            ->selectRaw('ifrs_stage, SUM(remaining_balance) as exposure')
            ->groupBy('ifrs_stage')
            ->get();

        $eclMatrix = [1 => '0.015', 2 => '0.150', 3 => '0.600'];
        $report = [];

        $totalProvisioning = '0.00';

        foreach ($loans as $loan) {
            $stage = $loan->ifrs_stage;
            $exposure = (string) $loan->exposure;
            $rate = $eclMatrix[$stage] ?? '0.00';
            
            $provision = bcmul($exposure, $rate, 2);
            $totalProvisioning = bcadd($totalProvisioning, $provision, 2);

            $report["stage_{$stage}"] = [
                'exposure' => $exposure,
                'provision_required' => $provision,
                'rate' => $rate
            ];
        }

        $report['total_expected_credit_loss'] = $totalProvisioning;

        return $report;
    }
}
