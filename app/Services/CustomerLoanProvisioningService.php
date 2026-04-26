<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerLoanProvisioningService
{
    public function __construct(
        private LoanCalculatorService $loanCalculator,
    ) {}

    /**
     * @return array{
     *     interest_rate: float,
     *     interest_type: string,
     *     duration_weeks: int,
     *     repayment_frequency: string,
     *     grace_period_days: int,
     *     source: string
     * }
     */
    public function defaultTerms(?string $preferredRepayment = null): array
    {
        $defaults = config('credit.defaults');
        $repaymentFrequency = in_array($preferredRepayment, ['weekly', 'biweekly', 'monthly'], true)
            ? $preferredRepayment
            : ($defaults['repayment_frequency'] ?? 'monthly');

        return [
            'interest_rate' => (float) ($defaults['interest_rate'] ?? 3.5),
            'interest_type' => (string) ($defaults['interest_type'] ?? 'flat'),
            'duration_weeks' => max(1, (int) ($defaults['duration_weeks'] ?? 52)),
            'repayment_frequency' => $repaymentFrequency,
            'grace_period_days' => max(0, (int) ($defaults['grace_period_days'] ?? 3)),
            'source' => 'credit_defaults',
        ];
    }

    public function portalState(Customer $customer): string
    {
        if ($customer->activeLoans()->exists()) {
            return 'loan_active';
        }

        if ($customer->isAssetReleased()) {
            return 'released_pending_disbursement';
        }

        return 'no_loan';
    }

    public function provisionForCustomerPortal(Customer $customer): ?Loan
    {
        $activeLoan = $this->activeLoan($customer);

        if ($activeLoan) {
            return $activeLoan;
        }

        if (! $this->canProvision($customer)) {
            return null;
        }

        try {
            return $this->provision($customer, $customer->assetReleasedBy);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function canProvision(Customer $customer): bool
    {
        return $customer->isAssetReleased()
            && filled($customer->cash_price)
            && filled($customer->preferred_repayment)
            && $customer->cash_price > 0;
    }

    public function provision(Customer $customer, ?User $actor = null): Loan
    {
        $existingLoan = $this->activeLoan($customer);

        if ($existingLoan) {
            return $existingLoan;
        }

        if (! $this->canProvision($customer)) {
            throw new InvalidArgumentException('Customer is not ready for automatic loan provisioning.');
        }

        $customer->loadMissing(['inventoryUnit', 'assetReleasedBy']);

        $terms = $this->refreshLoanTermsSnapshot($customer);
        $principal = round((float) $customer->cash_price, 2);
        $depositPaid = round((float) ($customer->deposit_amount ?? 0), 2);
        $financedPrincipal = round(max(0, $principal - $depositPaid), 2);

        if ($depositPaid < 0 || $depositPaid > $principal) {
            throw new InvalidArgumentException('Customer deposit is outside the valid range for loan provisioning.');
        }

        $computed = $terms['interest_type'] === 'flat'
            ? $this->loanCalculator->computeFlat(
                $financedPrincipal,
                $terms['interest_rate'],
                $terms['duration_weeks'],
                $terms['repayment_frequency'],
            )
            : $this->loanCalculator->computeReducingBalance(
                $financedPrincipal,
                $terms['interest_rate'],
                $terms['duration_weeks'],
                $terms['repayment_frequency'],
            );

        $totalDebt = round($principal + $computed['total_interest'], 2);
        $remainingBalance = round($computed['total_payable'], 2);

        $loan = DB::transaction(function () use ($actor, $customer, $depositPaid, $principal, $remainingBalance, $terms, $totalDebt): Loan {
            return Loan::create([
                'customer_id' => $customer->id,
                'inventory_unit_id' => $customer->inventory_unit_id,
                'dealer_id' => $customer->dealer_id ?? $customer->inventoryUnit?->dealer_id,
                'disbursed_by' => $actor?->id ?? $customer->asset_released_by ?? $customer->registered_by,
                'approved_by' => $actor?->id ?? $customer->asset_released_by ?? $customer->registered_by,
                'loan_number' => $this->loanCalculator->generateLoanNumber(),
                'principal_amount' => $principal,
                'deposit_paid' => $depositPaid,
                'interest_rate' => $terms['interest_rate'],
                'interest_type' => $terms['interest_type'],
                'total_debt' => $totalDebt,
                'total_payable' => $remainingBalance,
                'amount_paid' => $depositPaid,
                'remaining_balance' => $remainingBalance,
                'outstanding_balance' => $remainingBalance,
                'penalty_amount' => 0,
                'duration_weeks' => $terms['duration_weeks'],
                'grace_period_days' => $terms['grace_period_days'],
                'repayment_frequency' => $terms['repayment_frequency'],
                'status' => 'active',
                'disbursed_at' => now(),
                'due_date' => now()->addWeeks($terms['duration_weeks']),
                'notes' => 'Auto-provisioned from released KYC application.',
            ]);
        });

        $this->loanCalculator->createSchedule($loan);

        activity('loan')
            ->performedOn($loan)
            ->causedBy($actor)
            ->withProperties([
                'customer_id' => $customer->id,
                'terms_source' => $terms['source'],
                'repayment_frequency' => $terms['repayment_frequency'],
            ])
            ->log("Loan auto-provisioned for {$customer->full_name}");

        return $loan->fresh(['repaymentSchedules']);
    }

    /**
     * @return array{
     *     interest_rate: float,
     *     interest_type: string,
     *     duration_weeks: int,
     *     repayment_frequency: string,
     *     grace_period_days: int,
     *     source: string
     * }
     */
    public function refreshLoanTermsSnapshot(Customer $customer): array
    {
        $metadata = is_array($customer->metadata) ? $customer->metadata : [];
        $terms = $this->resolvedTerms($customer);

        $metadata['loan_terms'] = $terms;

        $customer->forceFill(['metadata' => $metadata])->save();

        return $terms;
    }

    private function activeLoan(Customer $customer): ?Loan
    {
        return $customer->loans()
            ->with(['dealer', 'repaymentSchedules'])
            ->where('status', 'active')
            ->latest('disbursed_at')
            ->latest()
            ->first();
    }

    /**
     * @return array{
     *     interest_rate: float,
     *     interest_type: string,
     *     duration_weeks: int,
     *     repayment_frequency: string,
     *     grace_period_days: int,
     *     source: string
     * }
     */
    private function resolvedTerms(Customer $customer): array
    {
        $metadata = is_array($customer->metadata) ? $customer->metadata : [];
        $storedTerms = is_array($metadata['loan_terms'] ?? null) ? $metadata['loan_terms'] : [];
        $defaults = $this->defaultTerms($customer->preferred_repayment);
        $hasPersistedLoanTerms = $customer->loan_interest_rate !== null
            || $customer->loan_interest_type !== null
            || $customer->loan_duration_weeks !== null
            || $customer->loan_grace_period_days !== null;
        $repaymentFrequency = $customer->preferred_repayment
            ?? $storedTerms['repayment_frequency']
            ?? $defaults['repayment_frequency'];

        if (! in_array($repaymentFrequency, ['weekly', 'biweekly', 'monthly'], true)) {
            $repaymentFrequency = $defaults['repayment_frequency'];
        }

        $interestType = $customer->loan_interest_type
            ?? $storedTerms['interest_type']
            ?? $defaults['interest_type'];

        if (! in_array($interestType, ['flat', 'reducing_balance'], true)) {
            $interestType = $defaults['interest_type'];
        }

        return [
            'interest_rate' => round((float) (
                $customer->loan_interest_rate
                ?? $storedTerms['interest_rate']
                ?? $defaults['interest_rate']
            ), 2),
            'interest_type' => $interestType,
            'duration_weeks' => max(1, (int) (
                $customer->loan_duration_weeks
                ?? $storedTerms['duration_weeks']
                ?? $defaults['duration_weeks']
            )),
            'repayment_frequency' => $repaymentFrequency,
            'grace_period_days' => max(0, (int) (
                $customer->loan_grace_period_days
                ?? $storedTerms['grace_period_days']
                ?? $defaults['grace_period_days']
            )),
            'source' => $hasPersistedLoanTerms
                ? (string) ($storedTerms['source'] ?? 'kyc_capture')
                : (is_array($storedTerms) && $storedTerms !== []
                    ? (string) ($storedTerms['source'] ?? 'customer_metadata')
                    : 'credit_defaults'),
        ];
    }
}
