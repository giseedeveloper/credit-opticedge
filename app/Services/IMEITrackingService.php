<?php

namespace App\Services;

use App\Models\InventoryUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IMEITrackingService
{
    /**
     * Validate that an IMEI does not already exist in the system.
     *
     * @throws ValidationException
     */
    public function assertImeiUnique(string $imei1, ?string $imei2 = null): void
    {
        if (InventoryUnit::where('imei_1', $imei1)->orWhere('imei_2', $imei1)->exists()) {
            throw ValidationException::withMessages(['imei_1' => "IMEI {$imei1} already exists in inventory."]);
        }

        if ($imei2 && InventoryUnit::where('imei_1', $imei2)->orWhere('imei_2', $imei2)->exists()) {
            throw ValidationException::withMessages(['imei_2' => "IMEI {$imei2} already exists in inventory."]);
        }
    }

    /**
     * Register a single unit after IMEI uniqueness check.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerUnit(array $data): InventoryUnit
    {
        $this->assertImeiUnique($data['imei_1'], $data['imei_2'] ?? null);

        return InventoryUnit::create($data);
    }

    /**
     * Bulk-insert units from a parsed import collection.
     * Skips rows with duplicate IMEIs and returns a report.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{inserted: int, skipped: int, errors: array<int, string>}
     */
    public function bulkRegister(Collection $rows): array
    {
        $inserted = 0;
        $skipped = 0;
        $errors = [];

        $existingImeis = InventoryUnit::withTrashed()
            ->pluck('imei_1')
            ->merge(InventoryUnit::withTrashed()->whereNotNull('imei_2')->pluck('imei_2'))
            ->flip();

        $chunks = $rows->chunk(500);

        foreach ($chunks as $chunk) {
            $toInsert = [];

            foreach ($chunk as $i => $row) {
                $imei1 = trim($row['imei_1'] ?? '');
                $imei2 = trim($row['imei_2'] ?? '');

                if (empty($imei1)) {
                    $errors[$i] = 'Row ' . ($i + 1) . ': imei_1 is required.';
                    $skipped++;
                    continue;
                }

                if (isset($existingImeis[$imei1]) || ($imei2 && isset($existingImeis[$imei2]))) {
                    $errors[$i] = 'Row ' . ($i + 1) . ": IMEI {$imei1} already exists.";
                    $skipped++;
                    continue;
                }

                $existingImeis[$imei1] = true;
                if ($imei2) {
                    $existingImeis[$imei2] = true;
                }

                $toInsert[] = array_merge($row, [
                    'id'         => \Illuminate\Support\Str::orderedUuid()->toString(),
                    'imei_1'     => $imei1,
                    'imei_2'     => $imei2 ?: null,
                    'status'     => $row['status'] ?? 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (! empty($toInsert)) {
                DB::transaction(fn () => InventoryUnit::insert($toInsert));
                $inserted += count($toInsert);
            }
        }

        return compact('inserted', 'skipped', 'errors');
    }

    /**
     * Transfer a unit to a vendor or branch.
     */
    public function transferUnit(InventoryUnit $unit, string $vendorId): InventoryUnit
    {
        $unit->update(['vendor_id' => $vendorId, 'status' => 'assigned']);

        return $unit->fresh();
    }
}
