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
     * Record a payment against the next open installments for a loan.
     *
     * @param  array<string, mixed>  $meta
     */
    public function recordPayment(Loan $loan, float $amount, string $reference, string $channel, array $meta = []): Transaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($loan, $amount, $reference, $channel, $meta) {
            /** @var Loan $lockedLoan */
            $lockedLoan = Loan::query()
                ->whereKey($loan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $currentOutstanding = max(
                (float) $lockedLoan->outstanding_balance,
                (float) $lockedLoan->remaining_balance
            );

            if ($currentOutstanding <= 0) {
                throw new InvalidArgumentException('Loan has no outstanding balance.');
            }

            $duplicateReferenceExists = Transaction::query()
                ->where('reference', $reference)
                ->orWhere('external_reference', $reference)
                ->exists();

            if ($duplicateReferenceExists) {
                throw new InvalidArgumentException('Duplicate transaction reference.');
            }

            $remainingAllocation = round($amount, 2);
            $allocations = [];

            $openSchedules = RepaymentSchedule::query()
                ->where('loan_id', $lockedLoan->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();

            foreach ($openSchedules as $schedule) {
                if ($remainingAllocation <= 0) {
                    break;
                }

                $scheduleOutstanding = round(
                    ((float) $schedule->amount_due + (float) $schedule->penalty_component) - (float) $schedule->amount_paid,
                    2
                );

                if ($scheduleOutstanding <= 0) {
                    continue;
                }

                $allocatedAmount = min($remainingAllocation, $scheduleOutstanding);
                $newAmountPaid = round((float) $schedule->amount_paid + $allocatedAmount, 2);
                $newBalanceRemaining = max(
                    0,
                    round(
                        ((float) $schedule->amount_due + (float) $schedule->penalty_component) - $newAmountPaid,
                        2
                    )
                );

                $schedule->update([
                    'amount_paid' => $newAmountPaid,
                    'balance_remaining' => $newBalanceRemaining,
                    'status' => $newBalanceRemaining <= 0 ? 'paid' : 'partial',
                    'paid_at' => $newBalanceRemaining <= 0 ? now()->toDateString() : null,
                ]);

                $allocations[] = [
                    'schedule_id' => $schedule->id,
                    'installment_number' => $schedule->installment_number,
                    'allocated_amount' => $allocatedAmount,
                ];

                $remainingAllocation = round($remainingAllocation - $allocatedAmount, 2);
            }

            $appliedAmount = min(round($amount, 2), $currentOutstanding);
            $unappliedAmount = round(max(0, $amount - $appliedAmount), 2);
            $newOutstanding = round(max(0, $currentOutstanding - $appliedAmount), 2);
            $normalizedChannel = $this->normalizeChannel($channel);

            $transactionMeta = array_merge($meta, [
                'source_channel' => $channel,
                'allocations' => $allocations,
                'applied_amount' => $appliedAmount,
                'unapplied_amount' => $unappliedAmount,
            ]);

            $transaction = Transaction::create([
                'loan_id' => $lockedLoan->id,
                'customer_id' => $lockedLoan->customer_id,
                'recorded_by' => auth()->id(),
                'reference' => $reference,
                'type' => 'repayment',
                'entry_type' => 'credit',
                'amount' => round($amount, 2),
                'channel' => $normalizedChannel,
                'external_reference' => $meta['external_reference'] ?? null,
                'description' => $meta['description'] ?? "Repayment via {$normalizedChannel}",
                'meta' => $transactionMeta,
                'transacted_at' => now(),
            ]);

            $lockedLoan->update([
                'amount_paid' => round((float) $lockedLoan->amount_paid + $appliedAmount, 2),
                'remaining_balance' => $newOutstanding,
                'outstanding_balance' => $newOutstanding,
                'status' => $newOutstanding <= 0 ? 'completed' : $lockedLoan->status,
                'completed_at' => $newOutstanding <= 0 ? now()->toDateString() : null,
            ]);

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
                ->whereIn('status', ['pending', 'partial'])
                ->get();

            foreach ($overdueSchedules as $schedule) {
                $penaltyAmount = 500.00;

                $schedule->increment('penalty_component', $penaltyAmount);
                $schedule->update(['status' => 'overdue']);

                $schedule->loan->increment('penalty_amount', $penaltyAmount);
                $schedule->loan->increment('remaining_balance', $penaltyAmount);
                $schedule->loan->increment('outstanding_balance', $penaltyAmount);
            }
        });
    }

    private function normalizeChannel(string $channel): string
    {
        $normalized = strtolower(trim($channel));

        return match ($normalized) {
            'm-pesa', 'mpesa' => 'mpesa',
            'tigo', 'tigo pesa', 'tigo_pesa', 'tigopesa' => 'tigopesa',
            'halo pesa', 'halo_pesa', 'halopesa' => 'halopesa',
            'bank transfer', 'bank_transfer' => 'bank',
            default => str_replace(' ', '_', $normalized),
        };
    }
}
