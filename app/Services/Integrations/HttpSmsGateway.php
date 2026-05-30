<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Generic HTTP SMS vendor hook — point SMS_HTTP_URL at your provider's REST API.
 */
class HttpSmsGateway implements SmsGateway
{
    public function driverName(): string
    {
        return 'http';
    }

    public function send(string $phone, string $message, array $meta = []): bool
    {
        $url = config('services.sms.http.url');
        $method = strtolower((string) config('services.sms.http.method', 'post'));

        if (! is_string($url) || trim($url) === '') {
            throw new RuntimeException('SMS HTTP driver requires SMS_HTTP_URL.');
        }

        $payload = [
            'phone' => $phone,
            'message' => $message,
            'sender_id' => config('services.sms.http.sender_id'),
            'meta' => $meta,
        ];

        $request = Http::timeout(20)
            ->acceptJson()
            ->withHeaders($this->headers());

        $response = match ($method) {
            'get' => $request->get($url, $payload),
            default => $request->post($url, $payload),
        };

        if ($response->failed()) {
            Log::error('SMS HTTP gateway failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr((string) $response->body(), 0, 500),
            ]);

            return false;
        }

        Log::channel('sms')->info('SMS sent via HTTP gateway', [
            'phone' => $phone,
            'status' => $response->status(),
        ]);

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = [];
        $apiKey = config('services.sms.http.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            $headerName = (string) config('services.sms.http.api_key_header', 'Authorization');
            $prefix = (string) config('services.sms.http.api_key_prefix', 'Bearer');
            $headers[$headerName] = trim($prefix.' '.$apiKey);
        }

        return $headers;
    }
}
