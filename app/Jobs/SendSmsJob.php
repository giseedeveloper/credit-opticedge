<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
            \Illuminate\Support\Facades\Log::channel('sms')->info('SMS sent', [
                'phone'   => $this->phone,
                'message' => $this->message,
                'meta'    => $this->meta,
            ]);

            return;
        }

        // TODO: Integrate with real SMS gateway (e.g. Beem Africa, Africa's Talking)
        // Example: app(SmsGateway::class)->send($this->phone, $this->message);
    }

    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('SMS job failed', [
            'phone'     => $this->phone,
            'exception' => $exception->getMessage(),
        ]);
    }
}
