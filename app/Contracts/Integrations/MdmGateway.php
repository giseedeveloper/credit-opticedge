<?php

namespace App\Contracts\Integrations;

use App\Models\InventoryUnit;

interface MdmGateway
{
    public function driverName(): string;

    public function lock(InventoryUnit $unit, string $reason): bool;

    public function unlock(InventoryUnit $unit, string $reason): bool;
}
