<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Jobs\SendWelcomeSmsJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanManagementService
{
    /**
     * Calculate amortization details with bcmath precision.
     * 
     * @param float|string $principal
     * @param float|string $interestRate (e.g., 20 for 20%)
     * @param int $durationDays
     * @param string $frequency (weekly/monthly)
     * @return array
     */
    public function calculateAmortization($principal, $interestRate, int $durationDays, string $frequency = 'weekly'): array
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
            'principal'          => $principalStr,
            'interest'           => $interest,
            'total_debt'         => $totalDebt,
            'installments'       => $installments,
            'periodic_payment'   => $periodicPayment,
            'payment_frequency'  => $frequency,
        ];
    }

    /**
     * Approve the loan, map repayment schedules, change stock status, and trigger SMS.
     */
    public function approveAndDisburse(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            if ($loan->status !== 'pending') {
                throw new InvalidArgumentException("Only pending loans can be disbursed.");
            }

            // Verify Stock
            $unit = $loan->inventoryUnit;
            if (!$unit || $unit->status !== 'available') {
                throw new InvalidArgumentException("Device is already assigned or unavailable.");
            }

            // Mark unit as sold
            $unit->update(['status' => 'sold']);
            
            // Log via Spatie Activitylog directly
            activity('inventory')
                ->performedOn($unit)
                ->causedBy(auth()->user())
                ->event('sold')
                ->log("Device disbursed for Loan {$loan->loan_number}");

            // Generate Repayment Schedules
            $calc = $this->calculateAmortization(
                $loan->total_debt, 
                0, // Assuming total_debt already contains interest
                $loan->duration_days, 
                $loan->payment_frequency
            );

            $installments = $calc['installments'];
            $installmentAmount = $calc['periodic_payment'];
            
            $schedules = [];
            $currentDate = now();
            
            for ($i = 1; $i <= $installments; $i++) {
                if ($loan->payment_frequency === 'weekly') {
                    $dueDate = clone $currentDate->addDays(7);
                } else {
                    $dueDate = clone $currentDate->addDays(30);
                }

                $schedules[] = [
                    'id'               => Str::orderedUuid()->toString(),
                    'loan_id'          => $loan->id,
                    'installment_no'   => $i,
                    'amount_due'       => $installmentAmount,
                    'amount_paid'      => '0.00',
                    'due_date'         => $dueDate->toDateString(),
                    'status'           => 'unpaid',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            RepaymentSchedule::insert($schedules);

            // Activate Loan
            $loan->update([
                'status' => 'active',
                'disbursed_at' => now(),
            ]);

            // Queue SMS
            SendWelcomeSmsJob::dispatch($loan);

            return $loan->fresh(['schedules']);
        });
    }

    /**
     * Provide a rebate quote for early settlement.
     */
    public function getEarlySettlementQuote(Loan $loan): array
    {
        // Total unpaid on schedules
        $unpaidSchedules = $loan->schedules()->whereIn('status', ['unpaid', 'partial'])->get();
        
        $totalOutstanding = '0.00';
        foreach ($unpaidSchedules as $schedule) {
            $totalOutstanding = bcadd($totalOutstanding, bcsub((string) $schedule->amount_due, (string) $schedule->amount_paid, 2), 2);
        }

        // Rebate Logic: E.g., 20% discount on the remaining outstanding balance if paid early
        $rebatePercentage = '0.20';
        $rebateAmount = bcmul($totalOutstanding, $rebatePercentage, 2);
        
        $settlementAmount = bcsub($totalOutstanding, $rebateAmount, 2);

        return [
            'loan_id' => $loan->id,
            'total_outstanding' => $totalOutstanding,
            'rebate_applied' => $rebateAmount,
            'final_settlement_amount' => $settlementAmount,
            'valid_until' => today()->endOfDay()->toDateTimeString()
        ];
    }

    /**
     * Process an early settlement payment overriding standard schedules.
     */
    public function processSettlement(Loan $loan, float $amountIn, string $reference): \App\Models\Transaction
    {
        return DB::transaction(function () use ($loan, $amountIn, $reference) {
            $quote = $this->getEarlySettlementQuote($loan);
            $amountStr = (string) $amountIn;

            if (bccomp($amountStr, $quote['final_settlement_amount'], 2) < 0) {
                throw new InvalidArgumentException("Insufficient funds for early settlement.");
            }

            // Create Settlement Transaction
            $transaction = \App\Models\Transaction::create([
                'loan_id' => $loan->id,
                'customer_id' => $loan->customer_id,
                'vendor_id' => $loan->vendor_id,
                'reference' => $reference,
                'type' => 'settlement',
                'amount' => $amountStr,
                'method' => 'bank_transfer',
                'status' => 'completed',
                'recorded_by' => auth()->id() ?? User::where('email', 'admin@opticedge.co.tz')->first()->id ?? null,
            ]);

            // Clear remaining schedules
            $loan->schedules()->whereIn('status', ['unpaid', 'partial', 'overdue'])->update([
                'status' => 'paid',
                'amount_paid' => DB::raw('amount_due'),
                'paid_at' => now(),
                'updated_at' => now()
            ]);

            // Update loan status
            $loan->update([
                'remaining_balance' => 0,
                'status' => 'completed'
            ]);

            return $transaction;
        });
    }
}
