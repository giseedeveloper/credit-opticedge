<?php

namespace App\Jobs;

use App\Services\IMEITrackingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BulkInventoryImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @param  array<int, array<string, mixed>>  $rows  Pre-parsed rows from Excel
     * @param  int  $uploadedBy  User ID who initiated the import
     */
    public function __construct(
        public readonly array $rows,
        public readonly int $uploadedBy,
        public readonly ?string $dealerId = null,
        public readonly ?int $phoneModelId = null
    ) {
        $this->onQueue('imports');
    }

    public function handle(IMEITrackingService $service): void
    {
        $rows = collect($this->rows)->map(function (array $row) {
            return array_merge($row, [
                'dealer_id' => $this->dealerId,
                'phone_model_id' => $row['phone_model_id'] ?? $this->phoneModelId,
            ]);
        });

        $report = $service->bulkRegister($rows);

        Log::info('Bulk inventory import completed', array_merge(
            $report,
            ['uploaded_by' => $this->uploadedBy]
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BulkInventoryImportJob failed', [
            'uploaded_by' => $this->uploadedBy,
            'exception' => $exception->getMessage(),
        ]);
    }
}
