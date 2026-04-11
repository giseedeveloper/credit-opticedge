<?php

namespace App\Services;

use App\Models\InventoryUnit;
use App\Models\RecoveryTicket;
use App\Models\RepaymentSchedule;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DeviceLockingService
{
    /**
     * Lock device via the configured MDM driver.
     */
    public function lockDevice(InventoryUnit $unit, string $reason): bool
    {
        if (! $unit->mdm_id) {
            Log::warning("Cannot lock device {$unit->imei_1}. No MDM ID associated.");

            return false;
        }

        if ($unit->lock_status === 'locked') {
            return true;
        }

        $this->sendMdmCommand('lock', $unit, $reason);

        $unit->update(['lock_status' => 'locked']);

        activity('security')
            ->performedOn($unit)
            ->event('locked')
            ->log("Device locked automatically via MDM. Reason: {$reason}");

        return true;
    }

    /**
     * Unlock device via MDM.
     */
    public function unlockDevice(InventoryUnit $unit, string $reason): bool
    {
        if (! $unit->mdm_id || $unit->lock_status === 'unlocked') {
            return false;
        }

        $this->sendMdmCommand('unlock', $unit, $reason);

        $unit->update(['lock_status' => 'unlocked']);

        activity('security')
            ->performedOn($unit)
            ->event('unlocked')
            ->log("Device unlocked automatically via MDM. Reason: {$reason}");

        return true;
    }

    /**
     * Identify overdue devices and trigger locks.
     */
    public function secureOverdueDevices(int $daysOverdueThreshold = 3): void
    {
        $overduePoint = today()->subDays($daysOverdueThreshold);

        $schedules = RepaymentSchedule::with('loan.inventoryUnit')
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('due_date', '<', $overduePoint)
            ->get();

        foreach ($schedules as $schedule) {
            $unit = $schedule->loan->inventoryUnit;
            if ($unit && $unit->lock_status !== 'locked') {
                $this->lockDevice($unit, "Overdue for {$daysOverdueThreshold} days.");
            }
        }
    }

    /**
     * Scan for devices locked > 7 days and boot a physical field ticket.
     */
    public function generateRecoveryTicketsForLockedDevices(): void
    {
        $sevenDaysAgo = today()->subDays(7);

        $units = InventoryUnit::where('lock_status', 'locked')
            ->where('updated_at', '<', $sevenDaysAgo)
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['active', 'defaulted', 'overdue']);
            })
            ->get();

        foreach ($units as $unit) {
            $loan = $unit->loan;

            // Ensure no active ticket exists for this exact loan
            $ticketExists = RecoveryTicket::where('loan_id', $loan->id)
                ->whereIn('status', ['open', 'assigned'])
                ->exists();

            if (! $ticketExists) {
                RecoveryTicket::create([
                    'loan_id' => $loan->id,
                    'status' => 'open',
                    'reward_amount' => 50000.00,
                    'notes' => 'Device locked for > 7 days. Escalated to field team.',
                ]);
                Log::info("Recovery Ticket generated for Loan: {$loan->loan_number}");
            }
        }
    }

    private function sendMdmCommand(string $command, InventoryUnit $unit, string $reason): void
    {
        $driver = config('services.mdm.driver', 'log');

        if ($driver === 'log') {
            Log::info("MDM {$command} command recorded for {$unit->mdm_id}.", [
                'inventory_unit_id' => $unit->id,
                'reason' => $reason,
            ]);

            return;
        }

        throw new RuntimeException("MDM driver [{$driver}] is not implemented.");
    }
}
