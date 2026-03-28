<?php

namespace App\Services;

use App\Models\InventoryUnit;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryAuditService
{
    /**
     * Transfer stock securely and map the event via Spatie Activitylog.
     */
    public function transferStock(InventoryUnit $unit, string $toVendorId): InventoryUnit
    {
        return DB::transaction(function () use ($unit, $toVendorId) {
            $fromVendorId = $unit->vendor_id;

            if ($fromVendorId === $toVendorId) {
                throw new InvalidArgumentException("Device is already assigned to this vendor.");
            }

            if ($unit->status === 'sold') {
                throw new InvalidArgumentException("Cannot transfer a sold device.");
            }

            $unit->update([
                'vendor_id' => $toVendorId,
                'status'    => 'vendor_stock',
            ]);

            activity('inventory')
                ->performedOn($unit)
                ->causedBy(auth()->user())
                ->event('transfer')
                ->log("Transferred from " . ($fromVendorId ?? 'HQ') . " to {$toVendorId}");

            return $unit->fresh();
        });
    }

    /**
     * Handle returned/repossessed devices, assessing condition and decoupling from loan.
     */
    public function recordReturn(Loan $loan, InventoryUnit $unit, string $condition): void
    {
        DB::transaction(function () use ($loan, $unit, $condition) {
            if ($loan->status === 'completed') {
                throw new InvalidArgumentException("Cannot reclaim a fully paid device.");
            }

            $unit->update([
                'status' => 'returned',
                // optionally we could add a condition column, e.g. 'condition' => $condition
            ]);

            $loan->update([
                'status' => 'defaulted', // or cancelled, repossessed
            ]);

            activity('inventory')
                ->performedOn($unit)
                ->causedBy(auth()->user())
                ->event('returned')
                ->log("Device repossessed/returned. Condition: {$condition}");
        });
    }

    /**
     * Specialized method for Recovery Officers handing over devices.
     */
    public function recordRepossession(\App\Models\RecoveryTicket $ticket, string $condition): InventoryUnit
    {
        return DB::transaction(function () use ($ticket, $condition) {
            $loan = $ticket->loan;
            $unit = $loan->inventoryUnit;

            if (!$unit) {
                throw new InvalidArgumentException("No device attached to this loan.");
            }

            $unit->update([
                'status' => 'recovered',
            ]);

            $loan->update(['status' => 'defaulted']);

            $ticket->update([
                'status' => 'recovered',
                'completed_at' => now()
            ]);

            activity('recovery')
                ->performedOn($unit)
                ->causedBy($ticket->agent)
                ->event('repossessed')
                ->log("Device physically retrieved by recovery officer. Condition: {$condition}");

            return $unit->fresh();
        });
    }
}
