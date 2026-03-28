<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('loans:apply-penalties {--rate=1.0 : Daily penalty percentage of installment amount} {--dry-run : Preview without writing}')]
#[Description('Apply daily penalties to all overdue loan installments (runs nightly at 06:00)')]
class PenaltyAutomator extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(\App\Services\LoanCalculatorService $calculator, \App\Services\CommunicationBridgeService $comms): void
    {
        $rate = (float) $this->option('rate');
        $dryRun = (bool) $this->option('dry-run');

        $loans = \App\Models\Loan::where('status', 'active')
            ->whereHas('repaymentSchedules', fn ($q) => $q
                ->whereIn('status', ['pending', 'partial'])
                ->where('due_date', '<', now())
            )
            ->with('repaymentSchedules', 'customer')
            ->cursor();

        $totalLoans = 0;
        $totalInstallments = 0;

        foreach ($loans as $loan) {
            if ($dryRun) {
                $this->line("[DRY-RUN] Loan {$loan->loan_number} would have penalties applied.");
                $totalLoans++;
                continue;
            }

            $affected = $calculator->applyPenalties($loan, $rate);

            if ($affected > 0) {
                $totalLoans++;
                $totalInstallments += $affected;

                $loan->repaymentSchedules()
                    ->where('status', 'overdue')
                    ->each(fn ($schedule) => $comms->notifyOverdue($schedule));
            }
        }

        $this->info("Penalties applied: {$totalLoans} loans, {$totalInstallments} installments.");

        \Illuminate\Support\Facades\Log::info('PenaltyAutomator ran', [
            'loans'        => $totalLoans,
            'installments' => $totalInstallments,
            'rate'         => $rate,
            'dry_run'      => $dryRun,
        ]);
    }
}
