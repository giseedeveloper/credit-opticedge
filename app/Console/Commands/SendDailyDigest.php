<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportGenerationService;
use App\Models\User;
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
    protected $description = 'Aggregates EOD Metrics and dispatches to Admins & Owners';

    /**
     * Execute the console command.
     */
    public function handle(ReportGenerationService $reportService)
    {
        $this->info("Compiling End of Day Business Digest...");

        $payload = $reportService->generateDailyDigestMessage();

        $owners = User::role(['owner', 'admin'])->get();

        foreach ($owners as $executive) {
            // Placeholder: Replace with actual Laravel Notification / Mail class / WhatsApp API sender
            Log::info("Dispatching Daily Digest Email to: " . $executive->email);
            Log::info("Digest Payload:\n" . $payload);
            
            // Mail::to($executive->email)->send(new DailyDigestMail($payload));
        }

        $this->info("Broadcast Complete. " . $owners->count() . " executives notified.");
    }
}
