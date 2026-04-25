<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Portfolio at Risk (PAR) — percentage of outstanding loan value with overdue installments.
     *
     * @return array{par_30: float, par_60: float, par_90: float, total_outstanding: float}
     */
    public function portfolioAtRisk(?string $dealerId = null): array
    {
        $today = Carbon::today();

        $base = Loan::where('status', 'active')
            ->when($dealerId, fn ($q) => $q->where('dealer_id', $dealerId));

        $totalOutstanding = (float) (clone $base)->sum('outstanding_balance');

        $overdueQuery = fn (int $days) => (clone $base)
            ->whereHas('repaymentSchedules', fn ($q) => $q
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->where('due_date', '<=', $today->copy()->subDays($days)))
            ->sum('outstanding_balance');

        return [
            'par_30' => $totalOutstanding > 0 ? round(($overdueQuery(30) / $totalOutstanding) * 100, 2) : 0,
            'par_60' => $totalOutstanding > 0 ? round(($overdueQuery(60) / $totalOutstanding) * 100, 2) : 0,
            'par_90' => $totalOutstanding > 0 ? round(($overdueQuery(90) / $totalOutstanding) * 100, 2) : 0,
            'total_outstanding' => round($totalOutstanding, 2),
        ];
    }

    /**
     * Collection efficiency — amount collected vs amount due in a period.
     *
     * @return array{collected: float, due: float, efficiency_percent: float}
     */
    public function collectionEfficiency(Carbon $from, Carbon $to, ?string $dealerId = null): array
    {
        $due = (float) RepaymentSchedule::whereBetween('due_date', [$from, $to])
            ->when($dealerId, fn ($q) => $q->whereHas('loan', fn ($l) => $l->where('dealer_id', $dealerId)))
            ->sum('amount_due');

        $collected = (float) Transaction::where('type', 'repayment')
            ->whereBetween('transacted_at', [$from, $to])
            ->when($dealerId, fn ($q) => $q->whereHas('loan', fn ($l) => $l->where('dealer_id', $dealerId)))
            ->sum('amount');

        return [
            'collected' => round($collected, 2),
            'due' => round($due, 2),
            'efficiency_percent' => $due > 0 ? round(($collected / $due) * 100, 2) : 0,
        ];
    }

    /**
     * Disbursement summary — total loans and amount disbursed by period.
     *
     * @return array{count: int, total_disbursed: float, avg_loan_size: float}
     */
    public function disbursementSummary(Carbon $from, Carbon $to, ?string $dealerId = null): array
    {
        $loans = Loan::whereBetween('disbursed_at', [$from, $to])
            ->when($dealerId, fn ($q) => $q->where('dealer_id', $dealerId))
            ->selectRaw('COUNT(*) as cnt, SUM(principal_amount) as total, AVG(principal_amount) as avg_size')
            ->first();

        return [
            'count' => (int) ($loans->cnt ?? 0),
            'total_disbursed' => round((float) ($loans->total ?? 0), 2),
            'avg_loan_size' => round((float) ($loans->avg_size ?? 0), 2),
        ];
    }

    /**
     * Overdue installments breakdown by dealer.
     *
     * @return array<int, array{dealer_id: string, dealer_name: string, overdue_amount: float, count: int}>
     */
    public function overdueByDealer(): array
    {
        return DB::table('repayment_schedules as rs')
            ->join('loans as l', 'l.id', '=', 'rs.loan_id')
            ->join('dealers as d', 'd.id', '=', 'l.dealer_id')
            ->whereIn('rs.status', ['pending', 'partial', 'overdue'])
            ->where('rs.due_date', '<', now())
            ->whereNull('l.deleted_at')
            ->groupBy('d.id', 'd.name')
            ->selectRaw('d.id as dealer_id, d.name as dealer_name, SUM(rs.amount_due - rs.amount_paid) as overdue_amount, COUNT(rs.id) as count')
            ->orderByDesc('overdue_amount')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
    }
}
