<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxService
{
    /**
     * Generate a VFD verification link for the transaction reference.
     */
    public function issueFiscalReceipt(Transaction $transaction): ?string
    {
        Log::info("TRA Fiscalization Trigger: Proceeding to issue receipt for Transaction {$transaction->reference}");

        $verifyBaseUrl = rtrim((string) config('services.tra.vfd_verify_base_url', 'https://vfd.tra.go.tz/verify'), '/');

        return $verifyBaseUrl.'/'.urlencode($transaction->reference);
    }

    /**
     * Daily collection breakdown per vendor to cross-check Z-Reports
     */
    public function generateDailyCashierReport(string $vendorId, ?string $date = null): array
    {
        $targetDate = $date ?? today()->toDateString();

        $transactions = DB::table('transactions')
            ->join('loans', 'transactions.loan_id', '=', 'loans.id')
            ->where('loans.dealer_id', $vendorId)
            ->where('transactions.type', 'repayment')
            ->where('transactions.entry_type', 'credit')
            ->whereDate('transactions.transacted_at', $targetDate)
            ->select('transactions.amount', 'transactions.channel')
            ->get();

        $methods = $transactions->groupBy(fn ($transaction) => $transaction->channel ?? 'unknown')->map(function ($items) {
            return $items->sum('amount');
        });

        return [
            'date' => $targetDate,
            'dealer_id' => $vendorId,
            'total_collections' => $transactions->sum('amount'),
            'methods' => $methods->toArray(),
        ];
    }
}
