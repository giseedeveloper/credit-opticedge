<?php

namespace App\Services;

use App\Models\Loan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialComplianceService
{
    /**
     * IFRS 9: Update DPD (Days Past Due) and map to IFRS stages using app schema.
     */
    public function calculateProvisioning(): void
    {
        DB::transaction(function () {
            Loan::query()
                ->whereIn('status', ['active', 'defaulted', 'overdue'])
                ->get()
                ->each(function (Loan $loan): void {
                    $dpd = $this->calculateDaysPastDue($loan);

                    Loan::query()
                        ->whereKey($loan->getKey())
                        ->update([
                            'dpd' => $dpd,
                            'ifrs_stage' => $this->ifrsStageForDaysPastDue($dpd),
                        ]);
                });
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
        $loans = Loan::whereIn('status', ['active', 'defaulted', 'overdue'])
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
                'rate' => $rate,
            ];
        }

        $report['total_expected_credit_loss'] = $totalProvisioning;

        return $report;
    }

    private function calculateDaysPastDue(Loan $loan): int
    {
        $oldestOverdueInstallment = $loan->repaymentSchedules()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->value('due_date');

        if (! $oldestOverdueInstallment) {
            return 0;
        }

        return (int) Carbon::parse($oldestOverdueInstallment)
            ->startOfDay()
            ->diffInDays(today());
    }

    private function ifrsStageForDaysPastDue(int $daysPastDue): int
    {
        return match (true) {
            $daysPastDue >= 90 => 3,
            $daysPastDue >= 30 => 2,
            default => 1,
        };
    }
}
