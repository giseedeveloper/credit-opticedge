<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
        public readonly array $meta = []
    ) {
        $this->onQueue('sms');
    }

    /**
     * Execute the job — integrate with your SMS gateway here.
     */
    public function handle(): void
    {
        $gateway = config('services.sms.driver', 'log');

        if ($gateway === 'log') {
            Log::channel('sms')->info('SMS sent', [
                'phone' => $this->phone,
                'message' => $this->message,
                'meta' => $this->meta,
            ]);

            return;
        }

        throw new RuntimeException("SMS gateway driver [{$gateway}] is not implemented.");
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SMS job failed', [
            'phone' => $this->phone,
            'exception' => $exception->getMessage(),
        ]);
    }
}
