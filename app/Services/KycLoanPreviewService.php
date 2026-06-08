<?php

namespace App\Services;

class KycLoanPreviewService
{
    public function __construct(
        private LoanCalculatorService $loanCalculator,
        private CustomerLoanProvisioningService $loanProvisioning,
    ) {}

    /**
     * @param  array{
     *     cash_price: float|int|string,
     *     deposit_amount?: float|int|string|null,
     *     preferred_repayment?: ?string,
     *     interest_rate?: float|int|string|null,
     *     interest_type?: ?string,
     *     duration_weeks?: int|string|null
     * }  $input
     * @return array{
     *     financed_principal: float,
     *     installment_count: int,
     *     installment_amount: float,
     *     total_interest: float,
     *     total_payable: float,
     *     repayment_frequency: string,
     *     interest_rate: float,
     *     interest_type: string,
     *     duration_weeks: int
     * }
     */
    public function preview(array $input): array
    {
        $cashPrice = max(0, (float) ($input['cash_price'] ?? 0));
        $deposit = max(0, (float) ($input['deposit_amount'] ?? 0));
        $financedPrincipal = max(0, round($cashPrice - $deposit, 2));

        $preferredRepayment = (string) ($input['preferred_repayment'] ?? 'weekly');
        $defaults = $this->loanProvisioning->defaultTerms($preferredRepayment);

        $interestRate = round((float) ($input['interest_rate'] ?? $defaults['interest_rate']), 2);
        $interestType = in_array($input['interest_type'] ?? null, ['flat', 'reducing_balance'], true)
            ? (string) $input['interest_type']
            : (string) $defaults['interest_type'];
        $durationWeeks = max(1, (int) ($input['duration_weeks'] ?? $defaults['duration_weeks']));
        $repaymentFrequency = in_array($preferredRepayment, ['daily', 'weekly', 'biweekly', 'monthly'], true)
            ? $preferredRepayment
            : (string) $defaults['repayment_frequency'];

        $computed = $interestType === 'flat'
            ? $this->loanCalculator->computeFlat($financedPrincipal, $interestRate, $durationWeeks, $repaymentFrequency)
            : $this->loanCalculator->computeReducingBalance($financedPrincipal, $interestRate, $durationWeeks, $repaymentFrequency);

        $installmentCount = $this->loanCalculator->installmentCount($durationWeeks, $repaymentFrequency);

        return [
            'financed_principal' => $financedPrincipal,
            'installment_count' => $installmentCount,
            'installment_amount' => $computed['installment_amount'],
            'total_interest' => $computed['total_interest'],
            'total_payable' => $computed['total_payable'],
            'repayment_frequency' => $repaymentFrequency,
            'interest_rate' => $interestRate,
            'interest_type' => $interestType,
            'duration_weeks' => $durationWeeks,
        ];
    }
}
