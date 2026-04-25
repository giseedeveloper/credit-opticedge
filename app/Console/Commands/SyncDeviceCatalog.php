<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Services\ExternalDeviceCatalogService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-device-catalog')]
#[Description('Sync external device models into phone_models')]
class SyncDeviceCatalog extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ExternalDeviceCatalogService $catalog): int
    {
        if (! $catalog->isEnabled()) {
            $this->info('Device catalog sync is disabled. Set DEVICE_CATALOG_ENABLED=true to enable.');

            return self::SUCCESS;
        }

        $total = 0;

        Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->each(function (Brand $brand) use (&$total, $catalog): void {
                $count = $catalog->syncModelsForBrand($brand);
                $total += $count;

                if ($count > 0) {
                    $this->line("{$brand->name}: {$count} models synced.");
                }
            });

        $this->info("Done. Total models processed: {$total}.");

        return self::SUCCESS;
    }
}
