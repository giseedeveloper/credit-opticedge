<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notification as BaseNotification;

class VerificationWorkflowService
{
    /**
     * Initiate a KYC verification request for a customer.
     */
    public function initiateKyc(Customer $customer): Verification
    {
        $verification = $customer->verifications()->create([
            'type' => 'kyc',
            'status' => 'pending',
        ]);

        $customer->update(['kyc_status' => 'under_review']);

        $this->notifyAdmins($customer, $verification);

        return $verification;
    }

    /**
     * Upload a KYC document and compress if it is an image.
     *
     * @param  string  $collection  nida_card|selfie|handover_photo
     */
    public function uploadDocument(Customer $customer, UploadedFile $file, string $collection): void
    {
        $customer
            ->addMedia($file)
            ->withCustomProperties(['uploaded_at' => now()->toIso8601String()])
            ->toMediaCollection($collection);
    }

    /**
     * Approve a verification request.
     */
    public function approve(Verification $verification, User $reviewer, ?string $notes = null): Verification
    {
        $verification->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'notes' => $notes,
        ]);

        if ($verification->type === 'kyc') {
            $verification->customer->update(['kyc_status' => 'approved']);
        }

        return $verification->fresh();
    }

    /**
     * Reject a verification request.
     */
    public function reject(Verification $verification, User $reviewer, string $reason): Verification
    {
        $verification->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        if ($verification->type === 'kyc') {
            $verification->customer->update(['kyc_status' => 'rejected']);
        }

        return $verification->fresh();
    }

    /**
     * Notify admin users about a new pending KYC.
     */
    private function notifyAdmins(Customer $customer, Verification $verification): void
    {
        $admins = User::role('admin')->get();

        $admins->each(function (User $admin) use ($customer, $verification) {
            $admin->notify(new class($customer, $verification) extends BaseNotification
            {
                public function __construct(
                    public Customer $customer,
                    public Verification $verification
                ) {}

                public function via(object $notifiable): array
                {
                    return ['database'];
                }

                /** @return array<string, mixed> */
                public function toArray(object $notifiable): array
                {
                    return [
                        'title' => 'New KYC Verification Request',
                        'message' => "Customer {$this->customer->full_name} submitted KYC documents.",
                        'verification_id' => $this->verification->id,
                        'customer_id' => $this->customer->id,
                    ];
                }
            });
        });
    }
}
