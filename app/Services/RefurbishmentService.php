<?php

namespace App\Services;

use App\Models\InventoryUnit;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefurbishmentService
{
    /**
     * Track spare parts costs against a repossessed or faulty unit.
     */
    public function processRefurbishment(InventoryUnit $unit, float $partCost, string $grading, string $notes): InventoryUnit
    {
        return DB::transaction(function () use ($unit, $partCost, $grading, $notes) {
            if (! in_array($unit->status, ['recovered', 'faulty', 'vendor_stock'])) {
                throw new InvalidArgumentException('Device is not eligible for refurbishment.');
            }

            $costStr = (string) $partCost;

            $newRepairCost = bcadd((string) $unit->repair_cost, $costStr, 2);

            $unit->update([
                'grading' => $grading,
                'repair_cost' => $newRepairCost,
                'status' => 'available',
            ]);

            Transaction::create([
                'type' => 'operational_cost',
                'entry_type' => 'debit',
                'amount' => $costStr,
                'channel' => 'internal',
                'reference' => 'RFB-'.uniqid(),
                'recorded_by' => auth()->id(),
                'description' => 'Inventory refurbishment cost',
                'meta' => [
                    'inventory_unit_id' => $unit->id,
                    'grading' => $grading,
                    'notes' => $notes,
                ],
                'transacted_at' => now(),
            ]);

            activity('inventory')
                ->performedOn($unit)
                ->event('refurbished')
                ->withProperties(['new_grading' => $grading, 'cost_added' => $costStr])
                ->log("Device refurbished. {$notes}");

            return $unit->fresh();
        });
    }
}
