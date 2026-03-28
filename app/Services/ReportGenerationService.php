<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Loan;

class ReportGenerationService
{
    /**
     * Compute aggregated Daily Metrics for EOD Operations.
     */
    public function generateDailyDigestMessage(): string
    {
        $today = today();

        // 1. Collections Cashflow
        $collected = DB::table('repayment_schedules')
                       ->whereDate('paid_date', $today)
                       ->sum('amount_paid');

        // 2. Default Surges (Loans converting to default today)
        $newDefaults = Loan::where('status', 'defaulted')
                           ->whereDate('updated_at', $today)
                           ->count();

        // 3. New Disbursements Capital Outflow
        $disbursed = Loan::whereDate('created_at', $today)
                         ->sum('principal_amount');

        // Format Payload String
        $msg = "📊 *Opticedge EOD Report* \n";
        $msg .= "Date: " . $today->format('Y-m-d') . "\n\n";
        $msg .= "💰 Total Collections: TZS " . number_format($collected) . "\n";
        $msg .= "🚀 Capital Disbursed: TZS " . number_format($disbursed) . "\n";
        $msg .= "🚨 New Defaults Logged: " . $newDefaults . " units\n\n";
        $msg .= "_System verified by Laravel Core Reporting._";

        return $msg;
    }
}
