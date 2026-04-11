<?php

namespace App\Services;

use App\Jobs\SendWelcomeSmsJob;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanManagementService
{
    /**
     * Calculate amortization details with bcmath precision.
     *
     * @param  float|string  $interestRate  (e.g., 20 for 20%)
     * @param  string  $frequency  (weekly/monthly)
     */
    public function calculateAmortization(float|string $principal, float|string $interestRate, int $durationDays, string $frequency = 'weekly'): array
    {
        // Simple Interest Calculation
        $principalStr = (string) $principal;
        $rateDecimal = bcdiv((string) $interestRate, '100', 4);

        $interest = bcmul($principalStr, $rateDecimal, 2);
        $totalDebt = bcadd($principalStr, $interest, 2);

        // Approximate periods
        $installments = ($frequency === 'weekly') ? max(1, floor($durationDays / 7)) : max(1, floor($durationDays / 30));

        $periodicPayment = bcdiv($totalDebt, (string) $installments, 2);

        return [
            'principal' => $principalStr,
            'interest' => $interest,
            'total_debt' => $totalDebt,
            'installments' => $installments,
            'periodic_payment' => $periodicPayment,
            'payment_frequency' => $frequency,
        ];
    }

    /**
     * Approve the loan, map repayment schedules, change stock status, and trigger SMS.
     */
    public function approveAndDisburse(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            if ($loan->status !== 'pending') {
                throw new InvalidArgumentException('Only pending loans can be disbursed.');
            }

            // Verify Stock
            $unit = $loan->inventoryUnit;
            if (! $unit || $unit->status !== 'available') {
                throw new InvalidArgumentException('Device is already assigned or unavailable.');
            }

            // Mark unit as sold
            $unit->update(['status' => 'sold']);

            // Log via Spatie Activitylog directly
            activity('inventory')
                ->performedOn($unit)
                ->causedBy(auth()->user())
                ->event('sold')
                ->log("Device disbursed for Loan {$loan->loan_number}");

            // Activate Loan
            $loan->update([
                'status' => 'active',
                'disbursed_at' => now(),
            ]);

            app(LoanCalculatorService::class)->createSchedule($loan->fresh());

            // Queue SMS
            SendWelcomeSmsJob::dispatch($loan);

            return $loan->fresh(['repaymentSchedules']);
        });
    }

    /**
     * Provide a rebate quote for early settlement.
     */
    public function getEarlySettlementQuote(Loan $loan): array
    {
        $openSchedules = $loan->repaymentSchedules()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->get();

        $scheduleOutstanding = round($openSchedules->sum(function (RepaymentSchedule $schedule): float {
            return max(
                0,
                round(
                    ((float) $schedule->amount_due + (float) $schedule->penalty_component) - (float) $schedule->amount_paid,
                    2
                )
            );
        }), 2);

        $loanOutstanding = round(max(
            $scheduleOutstanding,
            (float) $loan->outstanding_balance,
            (float) $loan->remaining_balance
        ), 2);

        $rebateAmount = round($loanOutstanding * 0.20, 2);
        $settlementAmount = round(max(0, $loanOutstanding - $rebateAmount), 2);

        return [
            'loan_id' => $loan->id,
            'total_outstanding' => $loanOutstanding,
            'rebate_applied' => $rebateAmount,
            'final_settlement_amount' => $settlementAmount,
            'open_installments' => $openSchedules->count(),
            'valid_until' => today()->endOfDay()->toDateTimeString(),
        ];
    }

    /**
     * Process an early settlement payment overriding standard schedules.
     */
    public function processSettlement(Loan $loan, float $amountIn, string $reference): Transaction
    {
        return DB::transaction(function () use ($loan, $amountIn, $reference) {
            if ($amountIn <= 0) {
                throw new InvalidArgumentException('Settlement amount must be greater than zero.');
            }

            /** @var Loan $lockedLoan */
            $lockedLoan = Loan::query()
                ->whereKey($loan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status === 'completed') {
                throw new InvalidArgumentException('Loan is already fully paid.');
            }

            $duplicateReferenceExists = Transaction::query()
                ->where('reference', $reference)
                ->orWhere('external_reference', $reference)
                ->exists();

            if ($duplicateReferenceExists) {
                throw new InvalidArgumentException('Duplicate transaction reference.');
            }

            $quote = $this->getEarlySettlementQuote($lockedLoan);
            $settlementAmount = round((float) $quote['final_settlement_amount'], 2);
            $rebateApplied = round((float) $quote['rebate_applied'], 2);
            $amount = round($amountIn, 2);

            if ($amount < $settlementAmount) {
                throw new InvalidArgumentException('Insufficient funds for early settlement.');
            }

            $openSchedules = RepaymentSchedule::query()
                ->where('loan_id', $lockedLoan->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();

            foreach ($openSchedules as $schedule) {
                $scheduleOutstanding = max(
                    0,
                    round(
                        ((float) $schedule->amount_due + (float) $schedule->penalty_component) - (float) $schedule->amount_paid,
                        2
                    )
                );

                $schedule->update([
                    'amount_paid' => round((float) $schedule->amount_paid + $scheduleOutstanding, 2),
                    'balance_remaining' => 0,
                    'status' => 'paid',
                    'paid_at' => now()->toDateString(),
                ]);
            }

            $transaction = Transaction::create([
                'loan_id' => $lockedLoan->id,
                'customer_id' => $lockedLoan->customer_id,
                'recorded_by' => auth()->id() ?? User::query()->where('email', 'admin@opticedge.co.tz')->value('id'),
                'reference' => $reference,
                'type' => 'settlement',
                'entry_type' => 'credit',
                'amount' => $amount,
                'channel' => 'bank',
                'description' => 'Early settlement payment received',
                'meta' => [
                    'quoted_amount' => $settlementAmount,
                    'rebate_applied' => $rebateApplied,
                    'open_installments_closed' => $openSchedules->count(),
                    'unapplied_amount' => round(max(0, $amount - $settlementAmount), 2),
                ],
                'transacted_at' => now(),
            ]);

            $lockedLoan->update([
                'amount_paid' => round((float) $lockedLoan->amount_paid + $settlementAmount, 2),
                'remaining_balance' => 0,
                'outstanding_balance' => 0,
                'status' => 'completed',
                'completed_at' => now()->toDateString(),
            ]);

            return $transaction;
        });
    }
}
