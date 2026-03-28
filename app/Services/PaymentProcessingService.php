<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentProcessingService
{
    /**
     * Record a payment, allocate linearly using bcmath, handle partials and overpayments.
     */
    public function recordPayment(Loan $loan, float $amount, string $reference, string $method): Transaction
    {
        return DB::transaction(function () use ($loan, $amount, $reference, $method) {
            $paymentStr = (string) $amount;
            
            if (bccomp($paymentStr, '0.00', 2) <= 0) {
                throw new InvalidArgumentException("Payment amount must be greater than zero.");
            }

            // Record the transaction
            $transaction = Transaction::create([
                'loan_id'        => $loan->id,
                'customer_id'    => $loan->customer_id,
                'vendor_id'      => $loan->vendor_id,
                'reference'      => $reference,
                'type'           => 'repayment',
                'amount'         => $paymentStr,
                'method'         => $method,
                'status'         => 'completed',
                'recorded_by'    => auth()->id(),
            ]);

            // Update remaining balance using bcmath
            $currentBalance = (string) $loan->remaining_balance;
            $newBalance = bcsub($currentBalance, $paymentStr, 2);
            $loan->update(['remaining_balance' => max(0, $newBalance)]);

            // Waterfall payment against unpaid schedules
            $unpaidSchedules = $loan->schedules()
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->get();

            $remainingAllocation = $paymentStr;

            foreach ($unpaidSchedules as $schedule) {
                if (bccomp($remainingAllocation, '0.00', 2) <= 0) break;

                $scheduleDue = (string) $schedule->amount_due;
                $schedulePaid = (string) $schedule->amount_paid;
                $balanceNeeded = bcsub($scheduleDue, $schedulePaid, 2);

                if (bccomp($balanceNeeded, '0.00', 2) <= 0) continue;

                if (bccomp($remainingAllocation, $balanceNeeded, 2) >= 0) {
                    // Fully pay this schedule
                    $schedule->update([
                        'amount_paid' => $scheduleDue,
                        'status'      => 'paid',
                        'paid_at'     => now()
                    ]);
                    $remainingAllocation = bcsub($remainingAllocation, $balanceNeeded, 2);
                } else {
                    // Partially pay this schedule
                    $newPaid = bcadd($schedulePaid, $remainingAllocation, 2);
                    $schedule->update([
                        'amount_paid' => $newPaid,
                        'status'      => 'partial'
                    ]);
                    $remainingAllocation = '0.00';
                }
            }

            // Check if loan is fully paid
            if (bccomp((string) $loan->remaining_balance, '0.00', 2) <= 0) {
                $loan->update(['status' => 'completed']);
                
                // Unmark inventory assignment if needed, or trigger unlock command
            }

            return $transaction;
        });
    }

    /**
     * Daily penalty job check.
     */
    public function applyPenalty(): void
    {
        DB::transaction(function () {
            $overdueSchedules = RepaymentSchedule::where('due_date', '<', today())
                ->whereIn('status', ['unpaid', 'partial'])
                ->get();

            foreach ($overdueSchedules as $schedule) {
                // Apply a fixed 500 TZS penalty per day overdue
                $penaltyAmount = '500.00';
                $schedule->increment('penalty_amount', (float) $penaltyAmount);
                $schedule->update(['status' => 'overdue']);
                
                $schedule->loan->increment('total_debt', (float) $penaltyAmount);
                $schedule->loan->increment('remaining_balance', (float) $penaltyAmount);
            }
        });
    }
}
