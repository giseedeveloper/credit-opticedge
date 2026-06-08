<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;

class ImeiLookupService
{
    /**
     * @return array{
     *     match: 'customer'|'inventory_only'|'none',
     *     customer: ?Customer,
     *     inventory_unit: ?InventoryUnit,
     *     loan: ?Loan,
     *     device: array<string, mixed>,
     *     dealer: array<string, mixed>,
     *     kyc: array<string, mixed>,
     *     loan_summary: ?array<string, mixed>,
     *     release: array<string, mixed>,
     *     activities: list<array<string, mixed>>
     * }
     */
    public function lookup(string $rawQuery): array
    {
        $variants = $this->deviceIdentifierVariants($rawQuery);

        if ($variants === []) {
            return $this->emptyResult();
        }

        $customer = $this->findCustomer($variants);
        $inventoryUnit = $this->findInventoryUnit($variants);

        if (! $customer && $inventoryUnit) {
            $customer = Customer::query()
                ->where('inventory_unit_id', $inventoryUnit->id)
                ->first();
        }

        if ($customer) {
            $customer->load([
                'phoneModel.brand',
                'inventoryUnit.phoneModel.brand',
                'inventoryUnit.dealer',
                'dealer',
                'registeredBy',
                'assetReleasedBy',
                'latestKycVerification',
                'loans' => fn ($query) => $query
                    ->with(['dealer', 'inventoryUnit.phoneModel.brand', 'repaymentSchedules'])
                    ->latest('created_at'),
            ]);

            if (! $inventoryUnit && $customer->inventoryUnit) {
                $inventoryUnit = $customer->inventoryUnit;
                $inventoryUnit->loadMissing(['phoneModel.brand', 'dealer']);
            }
        }

        if (! $customer && $inventoryUnit) {
            $inventoryUnit->loadMissing(['phoneModel.brand', 'dealer']);

            return [
                'match' => 'inventory_only',
                'customer' => null,
                'inventory_unit' => $inventoryUnit,
                'loan' => $inventoryUnit->loan?->loadMissing(['dealer', 'customer', 'repaymentSchedules']),
                'device' => $this->deviceFromUnit($inventoryUnit),
                'dealer' => $this->dealerContext($inventoryUnit->dealer),
                'kyc' => [],
                'loan_summary' => null,
                'release' => [],
                'activities' => $this->inventoryActivities($inventoryUnit),
            ];
        }

        if (! $customer) {
            return $this->emptyResult();
        }

        $loan = $this->resolvePrimaryLoan($customer, $inventoryUnit);
        $device = $this->deviceContext($customer, $inventoryUnit, $loan);
        $dealer = $this->dealerContext(
            $customer->dealer
                ?? $inventoryUnit?->dealer
                ?? $loan?->dealer
        );

        return [
            'match' => 'customer',
            'customer' => $customer,
            'inventory_unit' => $inventoryUnit,
            'loan' => $loan,
            'device' => $device,
            'dealer' => $dealer,
            'kyc' => $this->kycContext($customer),
            'loan_summary' => $loan ? $this->loanSummary($loan) : null,
            'release' => $this->releaseContext($customer),
            'activities' => $this->customerActivities($customer),
        ];
    }

    /**
     * @param  list<string>  $variants
     */
    private function findCustomer(array $variants): ?Customer
    {
        return Customer::query()
            ->where(function (Builder $query) use ($variants): void {
                foreach ($variants as $variant) {
                    $query->orWhere(function (Builder $inner) use ($variant): void {
                        $inner->where('imei_number', $variant)
                            ->orWhere('imei_2', $variant)
                            ->orWhere('serial_number', $variant);
                    });
                }
            })
            ->first();
    }

    /**
     * @param  list<string>  $variants
     */
    private function findInventoryUnit(array $variants): ?InventoryUnit
    {
        return InventoryUnit::query()
            ->with(['phoneModel.brand', 'dealer', 'loan.customer'])
            ->where(function (Builder $query) use ($variants): void {
                foreach ($variants as $variant) {
                    $query->orWhere(function (Builder $inner) use ($variant): void {
                        $inner->where('imei_1', $variant)
                            ->orWhere('imei_2', $variant)
                            ->orWhere('serial_number', $variant);
                    });
                }
            })
            ->first();
    }

    private function resolvePrimaryLoan(Customer $customer, ?InventoryUnit $inventoryUnit): ?Loan
    {
        $loans = $customer->loans ?? collect();

        if ($inventoryUnit) {
            $byUnit = $loans->firstWhere('inventory_unit_id', $inventoryUnit->id);
            if ($byUnit instanceof Loan) {
                return $byUnit;
            }
        }

        $active = $loans->firstWhere('status', 'active');
        if ($active instanceof Loan) {
            return $active;
        }

        return $loans->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function deviceContext(Customer $customer, ?InventoryUnit $unit, ?Loan $loan): array
    {
        $phoneModel = $customer->phoneModel
            ?? $unit?->phoneModel
            ?? $loan?->inventoryUnit?->phoneModel;

        $brand = $phoneModel?->brand;

        return [
            'brand' => $brand?->name,
            'model' => $phoneModel?->name,
            'specs' => $customer->device_specs,
            'imei_1' => $customer->imei_number ?: $unit?->imei_1 ?: $loan?->inventoryUnit?->imei_1,
            'imei_2' => $customer->imei_2 ?: $unit?->imei_2 ?: $loan?->inventoryUnit?->imei_2,
            'serial_number' => $customer->serial_number ?: $unit?->serial_number,
            'inventory_status' => $unit?->status,
            'mdm_id' => $unit?->mdm_id,
            'lock_status' => $unit?->lock_status,
            'cash_price' => $customer->cash_price,
            'deposit_amount' => $customer->deposit_amount,
            'preferred_repayment' => $customer->preferred_repayment,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deviceFromUnit(InventoryUnit $unit): array
    {
        return [
            'brand' => $unit->phoneModel?->brand?->name,
            'model' => $unit->phoneModel?->name,
            'specs' => null,
            'imei_1' => $unit->imei_1,
            'imei_2' => $unit->imei_2,
            'serial_number' => $unit->serial_number,
            'inventory_status' => $unit->status,
            'mdm_id' => $unit->mdm_id,
            'lock_status' => $unit->lock_status,
            'cash_price' => null,
            'deposit_amount' => null,
            'preferred_repayment' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dealerContext(?Dealer $dealer): array
    {
        if (! $dealer) {
            return [];
        }

        return [
            'id' => $dealer->id,
            'name' => $dealer->name,
            'code' => $dealer->code,
            'phone' => $dealer->phone,
            'email' => $dealer->email,
            'address' => $dealer->address,
            'status' => $dealer->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kycContext(Customer $customer): array
    {
        $verification = $customer->latestKycVerification;

        return [
            'status' => $customer->kyc_status,
            'stage' => $customer->kyc_stage,
            'verification_status' => $verification?->status,
            'face_match_status' => $verification?->face_match_status,
            'face_match_score' => $verification?->face_match_score,
            'auto_check_status' => $verification?->auto_check_status,
            'registered_by' => $customer->registeredBy?->name,
            'registered_at' => $customer->created_at?->toDateTimeString(),
            'nida_number' => $customer->nida_number,
            'id_type' => $customer->id_type,
            'region' => $customer->region,
            'district' => $customer->district,
            'address' => $customer->address,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loanSummary(Loan $loan): array
    {
        $schedules = $loan->repaymentSchedules ?? collect();
        $paidCount = $schedules->whereIn('status', ['paid', 'completed'])->count();
        $totalCount = $schedules->count();
        $nextDue = $schedules
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sortBy('due_date')
            ->first();

        return [
            'loan_number' => $loan->loan_number,
            'status' => $loan->status,
            'principal_amount' => (float) $loan->principal_amount,
            'outstanding_balance' => (float) ($loan->outstanding_balance ?? $loan->remaining_balance ?? 0),
            'total_payable' => (float) ($loan->total_payable ?? 0),
            'amount_paid' => (float) ($loan->amount_paid ?? 0),
            'duration_weeks' => (int) $loan->duration_weeks,
            'repayment_frequency' => $loan->repayment_frequency,
            'interest_rate' => (float) $loan->interest_rate,
            'interest_type' => $loan->interest_type,
            'disbursed_at' => $loan->disbursed_at?->toDateString(),
            'due_date' => $loan->due_date?->toDateString(),
            'dealer_name' => $loan->dealer?->name,
            'installments_paid' => $paidCount,
            'installments_total' => $totalCount,
            'progress_percent' => $totalCount > 0 ? (int) round(($paidCount / $totalCount) * 100) : 0,
            'next_due_date' => $nextDue?->due_date?->toDateString(),
            'next_due_amount' => $nextDue ? (float) $nextDue->amount_due : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseContext(Customer $customer): array
    {
        return [
            'status' => $customer->asset_release_status,
            'released_at' => $customer->asset_released_at?->toDateTimeString(),
            'released_by' => $customer->assetReleasedBy?->name,
            'deposit_payment_status' => $customer->deposit_payment_status,
            'deposit_paid_at' => $customer->deposit_paid_at?->toDateTimeString(),
            'agreement_accepted' => (bool) $customer->agreement_accepted,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customerActivities(Customer $customer): array
    {
        return Activity::query()
            ->with('causer')
            ->where('subject_type', Customer::class)
            ->where('subject_id', $customer->id)
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'causer' => $activity->causer?->name,
                'created_at' => $activity->created_at?->toDateTimeString(),
                'created_human' => $activity->created_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function inventoryActivities(InventoryUnit $unit): array
    {
        return Activity::query()
            ->with('causer')
            ->where('subject_type', InventoryUnit::class)
            ->where('subject_id', $unit->id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'causer' => $activity->causer?->name,
                'created_at' => $activity->created_at?->toDateTimeString(),
                'created_human' => $activity->created_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    public function deviceIdentifierVariants(string $raw): array
    {
        $trimmed = trim($raw);
        $upper = strtoupper($trimmed);
        $digitsOnly = preg_replace('/\D+/', '', $trimmed) ?? '';

        return array_values(array_unique(array_filter([
            $trimmed,
            $upper,
            $digitsOnly !== '' ? $digitsOnly : null,
        ])));
    }

    /**
     * @return array{
     *     match: 'none',
     *     customer: null,
     *     inventory_unit: null,
     *     loan: null,
     *     device: array{},
     *     dealer: array{},
     *     kyc: array{},
     *     loan_summary: null,
     *     release: array{},
     *     activities: list<array<string, mixed>>
     * }
     */
    private function emptyResult(): array
    {
        return [
            'match' => 'none',
            'customer' => null,
            'inventory_unit' => null,
            'loan' => null,
            'device' => [],
            'dealer' => [],
            'kyc' => [],
            'loan_summary' => null,
            'release' => [],
            'activities' => [],
        ];
    }
}
