<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Transaction;

class ReportGenerationService
{
    /**
     * Compute aggregated Daily Metrics for EOD Operations.
     */
    public function generateDailyDigestMessage(): string
    {
        $today = today();

        $collected = Transaction::where('type', 'repayment')
            ->where('entry_type', 'credit')
            ->whereDate('transacted_at', $today)
            ->sum('amount');

        $newDefaults = Loan::where('status', 'defaulted')
            ->whereDate('updated_at', $today)
            ->count();

        $disbursed = Loan::whereDate('disbursed_at', $today)
            ->sum('principal_amount');

        $msg = "📊 *Opticedge EOD Report*\n";
        $msg .= 'Date: '.$today->format('Y-m-d')."\n";
        $msg .= '💰 Total Collections: TZS '.number_format((float) $collected)."\n";
        $msg .= '🚀 Capital Disbursed: TZS '.number_format((float) $disbursed)."\n";
        $msg .= '🚨 New Defaults Logged: '.$newDefaults." units\n";
        $msg .= '_System verified by Laravel Core Reporting._';

        return $msg;
    }
}
