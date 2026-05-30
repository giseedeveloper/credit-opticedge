<?php

namespace App\Jobs;

use App\Services\Integrations\IntegrationGatewayManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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
    public function handle(IntegrationGatewayManager $gateways): void
    {
        $gateway = $gateways->sms();
        $sent = $gateway->send($this->phone, $this->message, $this->meta);

        if (! $sent) {
            throw new \RuntimeException("SMS gateway [{$gateway->driverName()}] returned failure.");
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SMS job failed', [
            'phone' => $this->phone,
            'exception' => $exception->getMessage(),
        ]);
    }
}
