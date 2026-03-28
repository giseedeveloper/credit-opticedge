<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoanCalculatorService
{
    /**
     * Compute total interest and payable using flat rate.
     *
     * @return array{total_interest: float, total_payable: float, installment_amount: float}
     */
    public function computeFlat(float $principal, float $ratePercent, int $durationWeeks): array
    {
        $totalInterest = $principal * ($ratePercent / 100) * ($durationWeeks / 52);
        $totalPayable = $principal + $totalInterest;
        $installmentAmount = $totalPayable / $durationWeeks;

        return [
            'total_interest' => round($totalInterest, 2),
            'total_payable' => round($totalPayable, 2),
            'installment_amount' => round($installmentAmount, 2),
        ];
    }

    /**
     * Compute amortization schedule using reducing balance method.
     *
     * @return array{total_interest: float, total_payable: float, installment_amount: float}
     */
    public function computeReducingBalance(float $principal, float $annualRatePercent, int $durationWeeks): array
    {
        $weeklyRate = ($annualRatePercent / 100) / 52;
        $installment = $weeklyRate === 0
            ? $principal / $durationWeeks
            : ($principal * $weeklyRate) / (1 - pow(1 + $weeklyRate, -$durationWeeks));

        $totalPayable = $installment * $durationWeeks;
        $totalInterest = $totalPayable - $principal;

        return [
            'total_interest' => round($totalInterest, 2),
            'total_payable' => round($totalPayable, 2),
            'installment_amount' => round($installment, 2),
        ];
    }

    /**
     * Generate unique loan number.
     */
    public function generateLoanNumber(): string
    {
        do {
            $number = 'LN-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Loan::where('loan_number', $number)->exists());

        return $number;
    }

    /**
     * Generate repayment schedule rows for a loan.
     */
    public function generateSchedule(Loan $loan): Collection
    {
        $schedules = collect();
        $dueDate = Carbon::parse($loan->disbursed_at);
        $principal = (float) $loan->principal_amount;
        $balance = $principal;

        if ($loan->interest_type === 'flat') {
            $result = $this->computeFlat($principal, (float) $loan->interest_rate, $loan->duration_weeks);
        } else {
            $result = $this->computeReducingBalance($principal, (float) $loan->interest_rate, $loan->duration_weeks);
        }

        $installment = $result['installment_amount'];
        $weeklyRate = ($loan->interest_type === 'reducing_balance')
            ? (($loan->interest_rate / 100) / 52)
            : 0;

        for ($i = 1; $i <= $loan->duration_weeks; $i++) {
            $dueDate = match ($loan->repayment_frequency) {
                'biweekly' => Carbon::parse($loan->disbursed_at)->addWeeks($i * 2),
                'monthly'  => Carbon::parse($loan->disbursed_at)->addMonths($i),
                default    => Carbon::parse($loan->disbursed_at)->addWeeks($i),
            };

            $interestComponent = $loan->interest_type === 'reducing_balance'
                ? round($balance * $weeklyRate, 2)
                : round(($result['total_interest']) / $loan->duration_weeks, 2);

            $principalComponent = round($installment - $interestComponent, 2);
            $balance = max(0, round($balance - $principalComponent, 2));

            $schedules->push([
                'id'                   => Str::orderedUuid()->toString(),
                'loan_id'              => $loan->id,
                'installment_number'   => $i,
                'amount_due'           => $installment,
                'principal_component'  => $principalComponent,
                'interest_component'   => $interestComponent,
                'penalty_component'    => 0,
                'amount_paid'          => 0,
                'balance_remaining'    => $balance,
                'due_date'             => $dueDate->toDateString(),
                'status'               => 'pending',
                'days_overdue'         => 0,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        return $schedules;
    }

    /**
     * Persist schedule to DB inside a transaction.
     */
    public function createSchedule(Loan $loan): void
    {
        DB::transaction(function () use ($loan) {
            $loan->repaymentSchedules()->delete();
            RepaymentSchedule::insert($this->generateSchedule($loan)->toArray());
        });
    }

    /**
     * Apply penalty to all overdue installments of a loan.
     *
     * @param  float  $penaltyRatePercent  Daily penalty as % of installment
     */
    public function applyPenalties(Loan $loan, float $penaltyRatePercent = 1.0): int
    {
        $today = Carbon::today();
        $affected = 0;

        $loan->repaymentSchedules()
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', $today)
            ->each(function (RepaymentSchedule $schedule) use ($today, $penaltyRatePercent, &$affected) {
                $daysOverdue = Carbon::parse($schedule->due_date)->diffInDays($today);
                $penalty = round($schedule->amount_due * ($penaltyRatePercent / 100) * $daysOverdue, 2);

                $schedule->update([
                    'penalty_component' => $penalty,
                    'days_overdue'      => $daysOverdue,
                    'status'            => 'overdue',
                ]);
                $affected++;
            });

        if ($affected > 0) {
            $totalPenalty = $loan->repaymentSchedules()->sum('penalty_component');
            $loan->update(['penalty_amount' => $totalPenalty]);
        }

        return $affected;
    }

}
