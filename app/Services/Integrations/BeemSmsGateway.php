<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * BEEM Africa SMS — enable with SMS_DRIVER=beem and BEEM_* credentials.
 */
class BeemSmsGateway implements SmsGateway
{
    public function driverName(): string
    {
        return 'beem';
    }

    public function send(string $phone, string $message, array $meta = []): bool
    {
        $apiKey = config('services.beem.api_key');
        $secretKey = config('services.beem.secret_key');
        $senderId = config('services.beem.sender_id');
        $baseUrl = rtrim((string) config('services.beem.base_url', 'https://apisms.beem.africa'), '/');

        if (! $apiKey || ! $secretKey || ! $senderId) {
            throw new RuntimeException('BEEM SMS requires BEEM_API_KEY, BEEM_SECRET_KEY, and BEEM_SENDER_ID.');
        }

        $normalizedPhone = $this->normalizePhone($phone);

        $response = Http::timeout(25)
            ->withBasicAuth($apiKey, $secretKey)
            ->post("{$baseUrl}/v1/send", [
                'source_addr' => $senderId,
                'schedule_time' => '',
                'encoding' => '0',
                'message' => $message,
                'recipients' => [
                    [
                        'recipient_id' => 1,
                        'dest_addr' => $normalizedPhone,
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('BEEM SMS failed', [
                'phone' => $normalizedPhone,
                'status' => $response->status(),
                'body' => substr((string) $response->body(), 0, 500),
            ]);

            return false;
        }

        Log::channel('sms')->info('SMS sent via BEEM', [
            'phone' => $normalizedPhone,
            'meta' => $meta,
        ]);

        return true;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if (str_starts_with($digits, '255')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        return '255'.$digits;
    }
}
