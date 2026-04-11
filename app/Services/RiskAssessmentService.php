<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;

class RiskAssessmentService
{
    /**
     * Calculate credit score (0-100) based on multiple parameters.
     */
    public function generateCreditScore(Customer $customer): int
    {
        $score = 50; // Base score

        // 1. Verification / KYC Status
        if ($customer->hasApprovedKyc() && $customer->nida_number) {
            $score += 20;
        }

        // 2. Employment/Income Logic (from JSONB metadata)
        $metadata = $customer->metadata ?? [];
        $income = (float) ($metadata['monthly_income'] ?? $customer->monthly_income ?? 0);

        if ($income > 1000000) {
            $score += 15;
        } elseif ($income > 500000) {
            $score += 10;
        }

        // 3. Past Repayment History
        $completedLoans = Loan::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->count();

        $defaultedLoans = Loan::where('customer_id', $customer->id)
            ->whereIn('status', ['defaulted', 'overdue'])
            ->count();

        $score += ($completedLoans * 10);
        $score -= ($defaultedLoans * 30);

        // Clamping score between 0 and 100
        return max(0, min(100, $score));
    }

    /**
     * Assess loan application risk.
     */
    public function assessApplication(Customer $customer, float $requestedAmount): array
    {
        $score = $this->generateCreditScore($customer);

        $decision = 'approved';
        $reason = 'Meets criteria';

        if ($score < 40) {
            $decision = 'rejected';
            $reason = "Credit score ({$score}) is too low.";
        } elseif ($score < 60 && $requestedAmount > 500000) {
            $decision = 'requires_guarantor';
            $reason = 'High requested amount for moderate risk score.';
        }

        return [
            'score' => $score,
            'decision' => $decision,
            'reason' => $reason,
        ];
    }
}
