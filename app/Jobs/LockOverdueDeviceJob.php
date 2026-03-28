<?php

namespace App\Jobs;

use App\Services\DeviceLockingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LockOverdueDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $daysOverdue = 3)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(DeviceLockingService $lockingService): void
    {
        Log::info("Starting MDM check for devices overdue by {$this->daysOverdue} days");
        
        $lockingService->secureOverdueDevices($this->daysOverdue);

        Log::info("Completed routine MDM lock check.");
    }
}
