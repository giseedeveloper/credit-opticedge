<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReportGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opticedge:daily-digest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates EOD metrics for admins and owners';

    /**
     * Execute the console command.
     */
    public function handle(ReportGenerationService $reportService): int
    {
        $this->info('Compiling End of Day Business Digest...');

        $payload = $reportService->generateDailyDigestMessage();

        $owners = User::whereHas('roles', function ($query): void {
            $query->whereIn('name', ['owner', 'admin']);
        })->get();

        foreach ($owners as $executive) {
            Log::info('Daily digest prepared for executive delivery.', [
                'user_id' => $executive->id,
                'email' => $executive->email,
                'payload' => $payload,
            ]);
        }

        $this->info('Digest Complete. '.$owners->count().' executive digests recorded.');

        return self::SUCCESS;
    }
}
