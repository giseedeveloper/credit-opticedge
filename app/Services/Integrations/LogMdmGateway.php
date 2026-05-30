<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\MdmGateway;
use App\Models\InventoryUnit;
use Illuminate\Support\Facades\Log;

class LogMdmGateway implements MdmGateway
{
    public function driverName(): string
    {
        return 'log';
    }

    public function lock(InventoryUnit $unit, string $reason): bool
    {
        Log::info('MDM lock (log driver)', [
            'inventory_unit_id' => $unit->id,
            'mdm_id' => $unit->mdm_id,
            'reason' => $reason,
        ]);

        return true;
    }

    public function unlock(InventoryUnit $unit, string $reason): bool
    {
        Log::info('MDM unlock (log driver)', [
            'inventory_unit_id' => $unit->id,
            'mdm_id' => $unit->mdm_id,
            'reason' => $reason,
        ]);

        return true;
    }
}
