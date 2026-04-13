<?php

namespace App\Http\Controllers\Api\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Loan;
use App\Services\CustomerLoanProvisioningService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Customer Portal — Loan & Repayment
 *
 * Endpoints for viewing the customer's active loan, repayment schedule,
 * and transaction history.
 */
class CustomerLoanController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CustomerLoanProvisioningService $loanProvisioning,
    ) {}

    /**
     * Active loan summary.
     *
     * Returns the customer's current active loan with key financial figures.
     */
    public function loan(Request $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request);
        $loan = $this->resolvePortalLoan($customer);

        if (! $loan) {
            return $this->successResponse([
                'portal_state' => $this->loanProvisioning->portalState($customer),
                'portal_message' => $this->portalMessageFor($customer),
                'loan' => null,
                'release' => $this->serializeReleaseContext($customer),
            ], $this->portalMessageFor($customer));
        }

        return $this->successResponse([
            'portal_state' => 'loan_active',
            'portal_message' => 'Active loan retrieved.',
            'loan' => $this->serializeLoan($loan),
            'release' => $this->serializeReleaseContext($customer),
        ], 'Active loan retrieved.');
    }

    /**
     * Full repayment schedule.
     *
     * Returns every installment for the active loan, ordered by installment number.
     */
    public function schedule(Request $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request);
        $loan = $this->resolvePortalLoan($customer);

        if (! $loan) {
            return $this->successResponse([
                'portal_state' => $this->loanProvisioning->portalState($customer),
                'portal_message' => $this->portalMessageFor($customer),
                'schedule' => null,
                'release' => $this->serializeReleaseContext($customer),
            ], $this->portalMessageFor($customer));
        }

        $schedules = $loan->repaymentSchedules()
            ->orderBy('installment_number')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'installment_number' => $s->installment_number,
                'amount_due' => $s->amount_due,
                'principal_component' => $s->principal_component,
                'interest_component' => $s->interest_component,
                'penalty_component' => $s->penalty_component,
                'amount_paid' => $s->amount_paid,
                'balance_remaining' => $s->balance_remaining,
                'due_date' => $s->due_date?->toDateString(),
                'paid_at' => $s->paid_at?->toDateString(),
                'status' => $s->status,
                'days_overdue' => $s->days_overdue ?? 0,
            ]);

        return $this->successResponse([
            'portal_state' => 'loan_active',
            'portal_message' => 'Repayment schedule retrieved.',
            'schedule' => [
                'loan_id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'total_installments' => $schedules->count(),
                'paid_installments' => $schedules->where('status', 'paid')->count(),
                'next_due' => $schedules->whereIn('status', ['pending', 'partial', 'overdue'])->first(),
                'schedule' => $schedules->values(),
            ],
            'release' => $this->serializeReleaseContext($customer),
        ], 'Repayment schedule retrieved.');
    }

    /**
     * Transaction history.
     *
     * Returns paginated payment transactions for the customer.
     */
    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $customer = $this->resolveCustomer($request);

        $transactions = $customer->transactions()
            ->latest('transacted_at')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        $items = $transactions->getCollection()->map(fn ($t) => [
            'id' => $t->id,
            'reference' => $t->reference,
            'type' => $t->type,
            'amount' => $t->amount,
            'channel' => $t->channel,
            'external_reference' => $t->external_reference,
            'description' => $t->description,
            'transacted_at' => $t->transacted_at?->toDateTimeString(),
        ]);

        return $this->successResponse([
            'data' => $items,
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'total' => $transactions->total(),
        ], 'Transactions retrieved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLoan(Loan $loan): array
    {
        $nextInstallment = $loan->repaymentSchedules()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('installment_number')
            ->first();

        $paidCount = $loan->repaymentSchedules()->where('status', 'paid')->count();
        $totalCount = $loan->repaymentSchedules()->count();

        return [
            'id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'status' => $loan->status,
            'principal_amount' => $loan->principal_amount,
            'deposit_paid' => $loan->deposit_paid,
            'interest_rate' => $loan->interest_rate,
            'interest_type' => $loan->interest_type,
            'total_payable' => $loan->total_payable,
            'amount_paid' => $loan->amount_paid,
            'remaining_balance' => $loan->remaining_balance,
            'outstanding_balance' => $loan->outstanding_balance,
            'penalty_amount' => $loan->penalty_amount,
            'duration_weeks' => $loan->duration_weeks,
            'repayment_frequency' => $loan->repayment_frequency,
            'disbursed_at' => $loan->disbursed_at?->toDateString(),
            'due_date' => $loan->due_date?->toDateString(),
            'paid_installments' => $paidCount,
            'total_installments' => $totalCount,
            'progress_percent' => $totalCount > 0 ? round(($paidCount / $totalCount) * 100, 1) : 0,
            'next_installment' => $nextInstallment ? [
                'installment_number' => $nextInstallment->installment_number,
                'amount_due' => $nextInstallment->amount_due,
                'due_date' => $nextInstallment->due_date?->toDateString(),
                'status' => $nextInstallment->status,
                'days_overdue' => $nextInstallment->days_overdue ?? 0,
            ] : null,
            'is_overdue' => $loan->isOverdue(),
        ];
    }

    private function resolvePortalLoan(Customer $customer): ?Loan
    {
        $customer->loadMissing(['assetReleasedBy', 'inventoryUnit']);

        $loan = $customer->loans()
            ->with(['branch', 'vendor'])
            ->where('status', 'active')
            ->latest('disbursed_at')
            ->latest()
            ->first();

        if ($loan) {
            return $loan;
        }

        return $this->loanProvisioning->provisionForCustomerPortal($customer);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReleaseContext(Customer $customer): array
    {
        $metadata = is_array($customer->metadata) ? $customer->metadata : [];
        $storedTerms = is_array($metadata['loan_terms'] ?? null) ? $metadata['loan_terms'] : [];

        return [
            'asset_release_status' => $customer->asset_release_status,
            'asset_released_at' => $customer->asset_released_at?->toDateTimeString(),
            'cash_price' => $customer->cash_price,
            'deposit_amount' => $customer->deposit_amount,
            'preferred_repayment' => $customer->preferred_repayment,
            'inventory_unit_id' => $customer->inventory_unit_id,
            'loan_interest_rate' => $customer->loan_interest_rate,
            'loan_interest_type' => $customer->loan_interest_type,
            'loan_duration_weeks' => $customer->loan_duration_weeks,
            'loan_grace_period_days' => $customer->loan_grace_period_days,
            'loan_terms_source' => (string) ($storedTerms['source'] ?? 'kyc_capture'),
        ];
    }

    private function portalMessageFor(Customer $customer): string
    {
        return match ($this->loanProvisioning->portalState($customer)) {
            'released_pending_disbursement' => 'Your device has been released, but your loan account is still being prepared.',
            default => 'No active loan found.',
        };
    }

    private function resolveCustomer(Request $request): Customer
    {
        $tokenable = $request->user('sanctum');

        abort_unless($tokenable instanceof Customer, 401, 'Unauthorized.');

        return $tokenable;
    }
}
