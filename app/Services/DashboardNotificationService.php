<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use App\Models\Verification;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class DashboardNotificationService
{
    private const int MAX_ITEMS = 20;

    /**
     * @return array{
     *     count: int,
     *     items: list<array<string, mixed>>
     * }
     */
    public function feed(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return ['count' => 0, 'items' => []];
        }

        $items = collect()
            ->merge($this->loanRiskAlerts($user))
            ->merge($this->dueSoonAlerts($user))
            ->merge($this->kycQueueAlerts($user))
            ->merge($this->releaseReadyAlerts($user))
            ->merge($this->trackingActivityAlerts($user))
            ->sortByDesc(fn (array $item): int => (int) ($item['sort_at'] ?? 0))
            ->take(self::MAX_ITEMS)
            ->values()
            ->map(fn (array $item): array => collect($item)->except('sort_at')->all())
            ->all();

        return [
            'count' => $this->actionableCount($user),
            'items' => $items,
        ];
    }

    public function actionableCount(?User $user = null): int
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return 0;
        }

        if (! $user->canAccess('loans.view')) {
            return 0;
        }

        $overdue = $this->loanQueryForUser($user)
            ->whereIn('status', ['overdue', 'defaulted'])
            ->count();

        $pendingKyc = Verification::query()
            ->where('type', 'kyc')
            ->where('status', 'pending')
            ->count();

        $faceReview = Verification::query()
            ->where('type', 'kyc')
            ->whereIn('face_match_status', ['review', 'failed'])
            ->where('status', 'pending')
            ->count();

        $releaseReady = Customer::query()
            ->where('asset_release_status', 'pending')
            ->whereIn('kyc_status', Customer::approvedKycStatuses())
            ->where('deposit_payment_status', 'completed')
            ->where('agreement_accepted', true)
            ->whereNotNull('customer_signature_path')
            ->whereNotNull('fo_signature_path')
            ->whereNotNull('asset_handover_list_path')
            ->count();

        return $overdue + $pendingKyc + $faceReview + $releaseReady;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loanRiskAlerts(User $user): array
    {
        if (! $user->canAccess('loans.view')) {
            return [];
        }

        return $this->loanQueryForUser($user)
            ->with(['customer.phoneModel', 'customer.inventoryUnit', 'inventoryUnit.phoneModel'])
            ->whereIn('status', ['overdue', 'defaulted'])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(function (Loan $loan): array {
                $customer = $loan->customer;
                $context = $this->customerContext($customer, $loan);

                return $this->item(
                    id: 'loan-risk-'.$loan->id,
                    category: 'loan_risk',
                    type: $loan->status === 'defaulted' ? 'danger' : 'warning',
                    icon: $loan->status === 'defaulted' ? 'exclamation-circle' : 'clock',
                    title: ($loan->status === 'defaulted' ? 'Defaulted loan' : 'Overdue loan').' · '.$loan->loan_number,
                    summary: 'Outstanding TZS '.number_format((float) ($loan->remaining_balance ?: $loan->outstanding_balance)),
                    context: $context,
                    occurredAt: $loan->updated_at ?? now(),
                    url: route('credit.defaulters'),
                    sortAt: ($loan->updated_at ?? now())->getTimestamp(),
                );
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dueSoonAlerts(User $user): array
    {
        if (! $user->canAccess('loans.view')) {
            return [];
        }

        return $this->loanQueryForUser($user)
            ->with(['customer.phoneModel', 'customer.inventoryUnit', 'inventoryUnit.phoneModel'])
            ->where('status', 'active')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(function (Loan $loan): array {
                $customer = $loan->customer;
                $dueLabel = $loan->due_date?->isToday()
                    ? 'Due today'
                    : 'Due '.$loan->due_date?->diffForHumans();

                return $this->item(
                    id: 'loan-due-'.$loan->id,
                    category: 'loan_due',
                    type: 'info',
                    icon: 'calendar-days',
                    title: 'Upcoming repayment · '.$loan->loan_number,
                    summary: $dueLabel.' · Balance TZS '.number_format((float) ($loan->remaining_balance ?: $loan->outstanding_balance)),
                    context: $this->customerContext($customer, $loan),
                    occurredAt: $loan->due_date ?? now(),
                    url: route('credit.schedules'),
                    sortAt: ($loan->due_date ?? now())->getTimestamp(),
                );
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function kycQueueAlerts(User $user): array
    {
        if (! $user->canAccess('loans.view')) {
            return [];
        }

        $pending = Verification::query()
            ->with(['customer.phoneModel', 'customer.inventoryUnit'])
            ->where('type', 'kyc')
            ->where('status', 'pending')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (Verification $verification): array {
                $customer = $verification->customer;
                $faceNeedsReview = in_array($verification->face_match_status, ['review', 'failed'], true);

                return $this->item(
                    id: 'kyc-pending-'.$verification->id,
                    category: $faceNeedsReview ? 'face_review' : 'kyc_pending',
                    type: $faceNeedsReview ? 'warning' : 'info',
                    icon: $faceNeedsReview ? 'user-circle' : 'identification',
                    title: $faceNeedsReview
                        ? 'Face match needs review'
                        : 'KYC awaiting approval',
                    summary: $faceNeedsReview
                        ? 'Score '.number_format((float) ($verification->face_match_score ?? 0) * 100, 1).'%. Stage '.$verification->stage.'.'
                        : 'Application at stage '.$verification->stage.' requires reviewer action.',
                    context: $this->customerContext($customer),
                    occurredAt: $verification->updated_at ?? now(),
                    url: route('kyc.pending'),
                    sortAt: ($verification->updated_at ?? now())->getTimestamp(),
                );
            })
            ->all();

        return $pending;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function releaseReadyAlerts(User $user): array
    {
        if (! $user->canAccess('loans.view')) {
            return [];
        }

        return Customer::query()
            ->with(['phoneModel', 'inventoryUnit.phoneModel'])
            ->where('asset_release_status', 'pending')
            ->whereIn('kyc_status', Customer::approvedKycStatuses())
            ->where('deposit_payment_status', 'completed')
            ->where('agreement_accepted', true)
            ->whereNotNull('customer_signature_path')
            ->whereNotNull('fo_signature_path')
            ->whereNotNull('asset_handover_list_path')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function (Customer $customer): array {
                $ready = $customer->isReadyForAssetRelease();

                return $this->item(
                    id: 'release-ready-'.$customer->id,
                    category: 'release_ready',
                    type: $ready ? 'success' : 'info',
                    icon: 'device-phone-mobile',
                    title: $ready ? 'Ready for asset release' : 'Release checklist in progress',
                    summary: $ready
                        ? 'Deposit paid, agreement signed, handover uploaded.'
                        : 'Complete remaining release gates before handover.',
                    context: $this->customerContext($customer),
                    occurredAt: $customer->updated_at ?? now(),
                    url: route('kyc.customers'),
                    sortAt: ($customer->updated_at ?? now())->getTimestamp(),
                );
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function trackingActivityAlerts(User $user): array
    {
        if (! $user->canAccess('reports.view')) {
            return [];
        }

        return Activity::query()
            ->with(['causer', 'subject'])
            ->whereIn('log_name', ['kyc', 'loan', 'security', 'inventory', 'audit_trail', 'dealers'])
            ->where('created_at', '>=', now()->subHours(72))
            ->whereNotIn('description', ['Successful Web Login (Secure Console).'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (Activity $activity): array {
                $customer = $this->resolveCustomerFromActivity($activity);
                $type = match ($activity->log_name) {
                    'security' => 'danger',
                    'loan' => 'warning',
                    default => 'info',
                };

                return $this->item(
                    id: 'activity-'.$activity->id,
                    category: 'system_tracking',
                    type: $type,
                    icon: 'signal',
                    title: ucfirst((string) $activity->log_name).' · '.str($activity->description)->limit(64),
                    summary: $activity->causer?->name
                        ? 'By '.$activity->causer->name.' · '.$activity->created_at?->diffForHumans()
                        : (string) $activity->created_at?->diffForHumans(),
                    context: $this->customerContext($customer),
                    occurredAt: $activity->created_at ?? now(),
                    url: route('audits.logs'),
                    sortAt: ($activity->created_at ?? now())->getTimestamp(),
                );
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function item(
        string $id,
        string $category,
        string $type,
        string $icon,
        string $title,
        string $summary,
        array $context,
        CarbonInterface $occurredAt,
        string $url,
        int $sortAt,
    ): array {
        return [
            'id' => $id,
            'category' => $category,
            'type' => $type,
            'icon' => $icon,
            'title' => $title,
            'summary' => $summary,
            'customer_id' => $context['customer_id'] ?? null,
            'customer_name' => $context['customer_name'] ?? null,
            'customer_phone' => $context['customer_phone'] ?? null,
            'customer_email' => $context['customer_email'] ?? null,
            'nida_number' => $context['nida_number'] ?? null,
            'loan_number' => $context['loan_number'] ?? null,
            'imei' => $context['imei'] ?? null,
            'device' => $context['device'] ?? null,
            'occurred_at' => $occurredAt->toDateTimeString(),
            'occurred_human' => $occurredAt->diffForHumans(),
            'url' => $url,
            'sort_at' => $sortAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customerContext(?Customer $customer, ?Loan $loan = null): array
    {
        if (! $customer instanceof Customer) {
            return [
                'loan_number' => $loan?->loan_number,
            ];
        }

        $device = $customer->device_specs
            ?: $customer->phoneModel?->name
            ?: $customer->inventoryUnit?->phoneModel?->name
            ?: $loan?->inventoryUnit?->phoneModel?->name;

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'nida_number' => $customer->nida_number,
            'loan_number' => $loan?->loan_number,
            'imei' => $customer->imei_number
                ?: $customer->inventoryUnit?->imei_1
                ?: $loan?->inventoryUnit?->imei_1,
            'device' => $device,
        ];
    }

    private function resolveCustomerFromActivity(Activity $activity): ?Customer
    {
        if ($activity->subject instanceof Customer) {
            return $activity->subject;
        }

        if ($activity->subject_type === Customer::class && filled($activity->subject_id)) {
            return Customer::query()
                ->with(['phoneModel', 'inventoryUnit.phoneModel'])
                ->find($activity->subject_id);
        }

        return null;
    }

    /**
     * @return Builder<Loan>
     */
    private function loanQueryForUser(User $user): Builder
    {
        $query = Loan::query();

        if ($user->isAdmin()) {
            return $query;
        }

        if (filled($user->dealer_id)) {
            return $query->where('dealer_id', $user->dealer_id);
        }

        return $query;
    }
}
