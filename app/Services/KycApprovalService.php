<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Validation\ValidationException;

class KycApprovalService
{
    public function manualVerifyFaceMatch(Customer $customer, User $actor): Verification
    {
        $verification = $this->resolveKycVerification($customer);

        if ($verification->face_match_status === 'manual_verified') {
            return $verification;
        }

        if (! in_array($verification->face_match_status, ['review', 'failed'], true)) {
            throw ValidationException::withMessages([
                'face_match' => 'Face match is not awaiting manual verification.',
            ]);
        }

        $verification->update([
            'face_match_status' => 'manual_verified',
            'face_match_manual_verified_by' => $actor->id,
            'face_match_manual_verified_at' => now(),
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy($actor)
            ->log("Face match manually verified for {$customer->full_name}");

        return $verification->fresh();
    }

    public function approveStage(Customer $customer, int $stage, User $actor, ?string $notes = null): Verification
    {
        if (! in_array($stage, [1, 2], true)) {
            throw ValidationException::withMessages([
                'stage' => 'Only stages 1 and 2 can be approved through this action.',
            ]);
        }

        $verification = $this->resolveKycVerification($customer);
        $nextStage = $stage + 1;

        $verification->update([
            "stage{$stage}_status" => 'approved',
            "stage{$stage}_reviewed_by" => $actor->id,
            "stage{$stage}_reviewed_at" => now(),
            "stage{$stage}_notes" => $notes,
            'stage' => $nextStage <= 4 ? $nextStage : $stage,
            'status' => $nextStage > 4 ? 'approved' : 'pending',
        ]);

        $customer->update([
            'kyc_stage' => $nextStage <= 4 ? $nextStage : $stage,
            'kyc_status' => $nextStage > 4 ? 'approved' : 'pending',
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy($actor)
            ->log("Stage {$stage} approved for {$customer->full_name}");

        return $verification->fresh();
    }

    public function rejectStage(
        Customer $customer,
        int $stage,
        User $actor,
        string $reason,
        ?string $notes = null,
    ): Verification {
        if (! in_array($stage, [1, 2], true)) {
            throw ValidationException::withMessages([
                'stage' => 'Only stages 1 and 2 can be rejected through this action.',
            ]);
        }

        $verification = $this->resolveKycVerification($customer);

        $verification->update([
            "stage{$stage}_status" => 'rejected',
            "stage{$stage}_reviewed_by" => $actor->id,
            "stage{$stage}_reviewed_at" => now(),
            "stage{$stage}_rejection_reason" => $reason,
            "stage{$stage}_notes" => $notes,
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $customer->update(['kyc_status' => 'rejected']);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy($actor)
            ->withProperties(['reason' => $reason])
            ->log("Stage {$stage} rejected for {$customer->full_name}");

        return $verification->fresh();
    }

    public function recordConfirmationCall(
        Customer $customer,
        User $actor,
        string $outcome,
        ?string $notes = null,
    ): Verification {
        if (! in_array($outcome, ['confirmed', 'not_confirmed'], true)) {
            throw ValidationException::withMessages([
                'outcome' => 'Outcome must be confirmed or not_confirmed.',
            ]);
        }

        $verification = $this->resolveKycVerification($customer);
        $isConfirmed = $outcome === 'confirmed';

        $verification->update([
            'stage3_status' => $isConfirmed ? 'approved' : 'rejected',
            'confirmation_call_outcome' => $outcome,
            'confirmation_call_notes' => $notes,
            'confirmation_called_at' => now(),
            'confirmation_called_by' => $actor->id,
            'stage' => $isConfirmed ? 4 : 3,
            'status' => $isConfirmed ? 'pending' : 'rejected',
        ]);

        $customer->update([
            'kyc_stage' => $isConfirmed ? 4 : 3,
            'kyc_status' => $isConfirmed ? 'pending' : 'rejected',
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy($actor)
            ->log("Confirmation call {$outcome} for {$customer->full_name}");

        return $verification->fresh();
    }

    public function recordNokCall(
        Customer $customer,
        User $actor,
        string $outcome,
        ?string $notes = null,
    ): Verification {
        if (! in_array($outcome, ['confirmed', 'not_confirmed'], true)) {
            throw ValidationException::withMessages([
                'outcome' => 'Outcome must be confirmed or not_confirmed.',
            ]);
        }

        $verification = $this->resolveKycVerification($customer);
        $isConfirmed = $outcome === 'confirmed';

        $verification->update([
            'stage4_status' => $isConfirmed ? 'approved' : 'rejected',
            'nok_call_outcome' => $outcome,
            'nok_call_notes' => $notes,
            'nok_called_at' => now(),
            'nok_called_by' => $actor->id,
            'status' => $isConfirmed ? 'approved' : 'rejected',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);

        $customer->update([
            'kyc_stage' => 4,
            'kyc_status' => $isConfirmed ? 'approved' : 'rejected',
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy($actor)
            ->log($isConfirmed
                ? "KYC fully approved for {$customer->full_name}"
                : "NOK call failed — KYC rejected for {$customer->full_name}");

        return $verification->fresh();
    }

    public function resolveKycVerification(Customer $customer): Verification
    {
        $verification = $customer->latestKycVerification()->first();

        if (! $verification instanceof Verification) {
            throw ValidationException::withMessages([
                'verification' => 'No KYC verification record found for this customer.',
            ]);
        }

        return $verification;
    }
}
