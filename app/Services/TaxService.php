<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class TaxService
{
    /**
     * Virtual Fiscal Device placeholder for TRA integration
     * Generating QR Code links for E-FD receipts via SMS
     */
    public function issueFiscalReceipt(Transaction $transaction): ?string
    {
        // 1. Prepare XML Payload structurally mapped to TRA specs
        // 2. Fetch the Bearer/Cert tokens from the VFD box (Middleware)
        // 3. Post to the VFDS API
        
        Log::info("TRA Fiscalization Trigger: Proceeding to issue receipt for Transaction {$transaction->reference}");
        
        // Placeholder return link
        return "https://vfd.tra.go.tz/verify/{$transaction->reference}";
    }

    /**
     * Daily collection breakdown per vendor to cross-check Z-Reports
     */
    public function generateDailyCashierReport(string $vendorId, string $date = null): array
    {
        $targetDate = $date ?? today()->toDateString();

        $transactions = Transaction::where('vendor_id', $vendorId)
            ->whereDate('created_at', $targetDate)
            ->where('status', 'completed')
            ->get();

        $methods = $transactions->groupBy('method')->map(function ($items) {
            return $items->sum('amount');
        });

        return [
            'date' => $targetDate,
            'vendor_id' => $vendorId,
            'total_collections' => $transactions->sum('amount'),
            'methods' => $methods->toArray(),
        ];
    }
}
