<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class FoAgentDashboardService
{
    /**
     * @return array{
     *     total_registered: int,
     *     pending: int,
     *     verified: int,
     *     declined: int,
     *     drafts: int,
     *     stale_drafts: int,
     *     face_review: int,
     *     ready_for_release: int,
     *     actionable_count: int,
     *     actions: list<array<string, mixed>>
     * }
     */
    public function statsForAgent(string|int $agentId): array
    {
        $base = $this->agentCustomerQuery($agentId);

        $total = (clone $base)->count();
        $pending = (clone $base)->whereHas('latestVerification', fn (Builder $q) => $q->where('status', 'pending'))->count();
        $verified = (clone $base)->whereHas('latestVerification', fn (Builder $q) => $q->where('status', 'approved'))->count();
        $declined = (clone $base)->whereHas('latestVerification', fn (Builder $q) => $q->where('status', 'rejected'))->count();
        $drafts = (clone $base)
            ->whereDoesntHave('verifications')
            ->whereNotNull('kyc_fo_saved_as_draft_at')
            ->count();
        $staleDrafts = (clone $base)
            ->whereDoesntHave('verifications')
            ->whereNotNull('kyc_fo_saved_as_draft_at')
            ->where('kyc_fo_saved_as_draft_at', '<', now()->subDay())
            ->count();
        $faceReview = (clone $base)
            ->whereHas('latestVerification', fn (Builder $q) => $q
                ->where('status', 'pending')
                ->whereIn('face_match_status', ['review', 'failed']))
            ->count();
        $readyForRelease = $this->countReadyForRelease($agentId);

        $actions = $this->buildActions(
            drafts: $drafts,
            staleDrafts: $staleDrafts,
            pending: $pending,
            faceReview: $faceReview,
            readyForRelease: $readyForRelease,
            declined: $declined,
        );

        $actionableCount = $drafts + $pending + $faceReview + $readyForRelease;

        return [
            'total_registered' => $total,
            'pending' => $pending,
            'verified' => $verified,
            'declined' => $declined,
            'drafts' => $drafts,
            'stale_drafts' => $staleDrafts,
            'face_review' => $faceReview,
            'ready_for_release' => $readyForRelease,
            'actionable_count' => $actionableCount,
            'actions' => $actions,
        ];
    }

    private function agentCustomerQuery(string|int $agentId): Builder
    {
        return Customer::query()
            ->where('registered_by', $agentId)
            ->whereNot('first_name', '_draft_');
    }

    private function countReadyForRelease(string|int $agentId): int
    {
        return $this->agentCustomerQuery($agentId)
            ->whereIn('kyc_status', Customer::approvedKycStatuses())
            ->where('deposit_payment_status', 'completed')
            ->where('agreement_accepted', true)
            ->whereNotNull('customer_signature_path')
            ->whereNotNull('fo_signature_path')
            ->whereNotNull('asset_handover_list_path')
            ->whereNotNull('agreement_document_id')
            ->where(function (Builder $query): void {
                $query->where('asset_release_status', 'pending')
                    ->orWhereNull('asset_release_status');
            })
            ->get()
            ->filter(fn (Customer $customer): bool => $customer->hasCompletedPreHandoverChecklist())
            ->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildActions(
        int $drafts,
        int $staleDrafts,
        int $pending,
        int $faceReview,
        int $readyForRelease,
        int $declined,
    ): array {
        $actions = [];

        if ($staleDrafts > 0) {
            $actions[] = [
                'key' => 'stale_drafts',
                'title' => 'Stale drafts',
                'subtitle' => 'Unfinished applications older than 24 hours',
                'count' => $staleDrafts,
                'tab' => 'draft',
                'severity' => 'warning',
            ];
        } elseif ($drafts > 0) {
            $actions[] = [
                'key' => 'drafts',
                'title' => 'Resume drafts',
                'subtitle' => 'Continue onboarding from where you left off',
                'count' => $drafts,
                'tab' => 'draft',
                'severity' => 'info',
            ];
        }

        if ($pending > 0) {
            $actions[] = [
                'key' => 'pending',
                'title' => 'Pending HQ review',
                'subtitle' => 'Applications waiting for approval',
                'count' => $pending,
                'tab' => 'pending',
                'severity' => 'info',
            ];
        }

        if ($faceReview > 0) {
            $actions[] = [
                'key' => 'face_review',
                'title' => 'Face match review',
                'subtitle' => 'Applications flagged for face verification',
                'count' => $faceReview,
                'tab' => 'pending',
                'severity' => 'warning',
            ];
        }

        if ($readyForRelease > 0) {
            $actions[] = [
                'key' => 'ready_for_release',
                'title' => 'Ready for release',
                'subtitle' => 'Approved customers ready for device handover',
                'count' => $readyForRelease,
                'tab' => 'approved',
                'severity' => 'success',
            ];
        }

        if ($declined > 0) {
            $actions[] = [
                'key' => 'declined',
                'title' => 'Declined applications',
                'subtitle' => 'Review rejected cases and follow up',
                'count' => $declined,
                'tab' => 'rejected',
                'severity' => 'danger',
            ];
        }

        return $actions;
    }
}
