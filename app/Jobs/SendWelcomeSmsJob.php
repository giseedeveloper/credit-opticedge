<?php

namespace App\Jobs;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWelcomeSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Loan $loan)
    {
    }

    public function handle(): void
    {
        // Simulate SMS sending logic via third party API (e.g. NextSMS, Beem)
        $customer = $this->loan->customer;
        
        if (!$customer || !$customer->phone) {
            return;
        }

        $message = "Welcome {$customer->full_name} to Opticedge Credit. Your loan #{$this->loan->loan_number} for TZS " . number_format($this->loan->total_debt, 0) . " is approved. Dial *150*... to pay.";

        // External API HTTP Request here
        Log::info("SMS Sent to {$customer->phone}: {$message}");
    }
}
