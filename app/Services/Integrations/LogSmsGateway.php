<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\SmsGateway;
use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGateway
{
    public function driverName(): string
    {
        return 'log';
    }

    public function send(string $phone, string $message, array $meta = []): bool
    {
        Log::channel('sms')->info('SMS (log driver)', [
            'phone' => $phone,
            'message' => $message,
            'meta' => $meta,
        ]);

        return true;
    }
}
