<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PreHandoverChecklistService
{
    public function __construct(
        private DeviceLockingService $deviceLocking,
    ) {}

    /**
     * @return array{
     *     device_unboxed: bool,
     *     device_boot_verified: bool,
     *     mdm_lock_confirmed: bool,
     *     completed_at: ?string,
     *     completed_by: ?string,
     *     mdm_lock_status: ?string
     * }
     */
    public function checklist(Customer $customer): array
    {
        $stored = data_get($customer->metadata, 'pre_handover_checklist', []);

        if (! is_array($stored)) {
            $stored = [];
        }

        return [
            'device_unboxed' => (bool) ($stored['device_unboxed'] ?? false),
            'device_boot_verified' => (bool) ($stored['device_boot_verified'] ?? false),
            'mdm_lock_confirmed' => (bool) ($stored['mdm_lock_confirmed'] ?? false),
            'completed_at' => isset($stored['completed_at']) ? (string) $stored['completed_at'] : null,
            'completed_by' => isset($stored['completed_by']) ? (string) $stored['completed_by'] : null,
            'mdm_lock_status' => isset($stored['mdm_lock_status']) ? (string) $stored['mdm_lock_status'] : null,
        ];
    }

    public function isComplete(Customer $customer): bool
    {
        if (! config('credit.require_pre_handover_checklist', true)) {
            return true;
        }

        $checklist = $this->checklist($customer);

        return $checklist['device_unboxed']
            && $checklist['device_boot_verified']
            && $checklist['mdm_lock_confirmed'];
    }

    /**
     * @param  array{
     *     device_unboxed: bool,
     *     device_boot_verified: bool,
     *     mdm_lock_confirmed: bool
     * }  $validated
     * @return array<string, mixed>
     */
    public function complete(Customer $customer, array $validated, User $agent): array
    {
        if (! $validated['device_unboxed'] || ! $validated['device_boot_verified'] || ! $validated['mdm_lock_confirmed']) {
            throw ValidationException::withMessages([
                'pre_handover_checklist' => 'Confirm device unboxed, boot verified, and MDM lock before release.',
            ]);
        }

        $customer->loadMissing('inventoryUnit');
        $mdmLockStatus = $this->applyMdmLock($customer);

        $metadata = $customer->metadata ?? [];
        $metadata['pre_handover_checklist'] = [
            'device_unboxed' => true,
            'device_boot_verified' => true,
            'mdm_lock_confirmed' => true,
            'completed_at' => now()->toIso8601String(),
            'completed_by' => (string) $agent->id,
            'mdm_lock_status' => $mdmLockStatus,
        ];

        $customer->update(['metadata' => $metadata]);

        return $this->checklist($customer->fresh());
    }

    private function applyMdmLock(Customer $customer): string
    {
        $unit = $customer->inventoryUnit;

        if (! $unit || ! filled($unit->mdm_id)) {
            if (config('credit.require_mdm_lock_at_handover', false)) {
                throw ValidationException::withMessages([
                    'mdm_lock_confirmed' => 'Linked inventory unit has no MDM ID. Contact operations before handover.',
                ]);
            }

            return 'skipped_no_mdm_id';
        }

        $locked = $this->deviceLocking->lockDevice($unit, 'Pre-handover credit lock');

        if (! $locked && config('credit.require_mdm_lock_at_handover', false)) {
            throw ValidationException::withMessages([
                'mdm_lock_confirmed' => 'MDM lock command failed. Verify MDM integration and try again.',
            ]);
        }

        return $locked ? 'locked' : 'failed';
    }
}
