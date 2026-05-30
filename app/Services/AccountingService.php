<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingService
{
    /**
     * Record a double-entry repayment against a loan installment.
     *
     * @param  array<string, mixed>  $extra
     * @return array{debit: Transaction, credit: Transaction}
     */
    public function recordRepayment(
        Loan $loan,
        RepaymentSchedule $schedule,
        float $amount,
        string $channel = 'cash',
        array $extra = []
    ): array {
        $reference = 'TXN-'.strtoupper(Str::random(12));

        return DB::transaction(function () use ($loan, $schedule, $amount, $channel, $reference, $extra) {
            $shared = [
                'loan_id' => $loan->id,
                'repayment_schedule_id' => $schedule->id,
                'customer_id' => $loan->customer_id,
                'recorded_by' => auth()->id(),
                'amount' => $amount,
                'channel' => $channel,
                'transacted_at' => now(),
                'meta' => $extra,
            ];

            $debit = Transaction::create(array_merge($shared, [
                'reference' => $reference.'-DR',
                'type' => 'repayment',
                'entry_type' => 'debit',
                'description' => 'Customer repayment debit',
            ]));

            $credit = Transaction::create(array_merge($shared, [
                'reference' => $reference.'-CR',
                'type' => 'repayment',
                'entry_type' => 'credit',
                'description' => 'Customer repayment credit',
            ]));

            $this->applyRepaymentToSchedule($schedule, $amount);
            $this->updateLoanBalance($loan, $amount);

            return compact('debit', 'credit');
        });
    }

    /**
     * Apply payment amount to the repayment schedule row.
     */
    private function applyRepaymentToSchedule(RepaymentSchedule $schedule, float $amount): void
    {
        $totalPaid = (float) $schedule->amount_paid + $amount;
        $amountDue = (float) $schedule->amount_due + (float) $schedule->penalty_component;
        $balance = max(0, $amountDue - $totalPaid);

        $status = match (true) {
            $totalPaid >= $amountDue => 'paid',
            $totalPaid > 0 => 'partial',
            default => $schedule->status,
        };

        $schedule->update([
            'amount_paid' => $totalPaid,
            'balance_remaining' => $balance,
            'status' => $status,
            'paid_at' => $status === 'paid' ? now()->toDateString() : null,
        ]);
    }

    /**
     * Recalculate and persist updated loan balance after a payment.
     */
    private function updateLoanBalance(Loan $loan, float $amount): void
    {
        $newAmountPaid = (float) $loan->amount_paid + $amount;
        $newBalance = max(0, (float) $loan->outstanding_balance - $amount);

        $status = $newBalance <= 0 ? 'completed' : 'active';

        $loan->update([
            'amount_paid' => $newAmountPaid,
            'outstanding_balance' => $newBalance,
            'status' => $status,
            'completed_at' => $status === 'completed' ? now()->toDateString() : null,
        ]);
    }

    /**
     * Generate a TRA-ready receipt payload (EFD/VFD integration stub).
     *
     * @return array<string, mixed>
     */
    public function buildReceiptPayload(Transaction $transaction): array
    {
        $loan = $transaction->loan;
        $customer = $transaction->customer;

        $amount = round((float) $transaction->amount, 2);
        $vatRate = (float) config('app.default_vat_rate', 0);
        $vatAmount = $vatRate > 0 ? round($amount * ($vatRate / (100 + $vatRate)), 2) : 0.0;

        return [
            'receipt_number' => $transaction->reference,
            'date' => $transaction->transacted_at->toDateTimeString(),
            'amount' => $amount,
            'vat_rate_percent' => $vatRate,
            'vat' => $vatAmount,
            'net_amount' => round($amount - $vatAmount, 2),
            'customer_name' => $customer?->full_name ?? 'N/A',
            'customer_phone' => $customer?->phone ?? 'N/A',
            'loan_number' => $loan?->loan_number ?? 'N/A',
            'payment_channel' => $transaction->channel,
            'tin' => config('app.company_tin', env('COMPANY_TIN', '')),
            'vrn' => config('app.company_vrn', env('COMPANY_VRN', '')),
            'efd_required' => filter_var(env('TRA_EFD_ENABLED', false), FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function issueTraFiscalReceipt(Transaction $transaction): array
    {
        return app(TraFiscalReceiptService::class)->issueForTransaction($transaction);
    }

    /**
     * Dealer-level ledger summary.
     *
     * @return array<string, float>
     */
    public function dealerLedger(string $dealerId, Carbon $from, Carbon $to): array
    {
        $result = DB::table('transactions as t')
            ->join('loans as l', 'l.id', '=', 't.loan_id')
            ->where('l.dealer_id', $dealerId)
            ->whereBetween('t.transacted_at', [$from, $to])
            ->whereNull('l.deleted_at')
            ->selectRaw(
                'SUM(CASE WHEN t.entry_type = "credit" AND t.type = "repayment" THEN t.amount ELSE 0 END) as total_collected,'
                .'SUM(CASE WHEN t.type = "disbursement" THEN t.amount ELSE 0 END) as total_disbursed,'
                .'SUM(CASE WHEN t.type = "penalty" THEN t.amount ELSE 0 END) as total_penalties'
            )
            ->first();

        return [
            'total_collected' => round((float) ($result->total_collected ?? 0), 2),
            'total_disbursed' => round((float) ($result->total_disbursed ?? 0), 2),
            'total_penalties' => round((float) ($result->total_penalties ?? 0), 2),
        ];
    }
}
