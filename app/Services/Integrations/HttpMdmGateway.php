<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\MdmGateway;
use App\Models\InventoryUnit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Generic HTTP MDM vendor hook — configure MDM_HTTP_LOCK_URL / MDM_HTTP_UNLOCK_URL.
 */
class HttpMdmGateway implements MdmGateway
{
    public function driverName(): string
    {
        return 'http';
    }

    public function lock(InventoryUnit $unit, string $reason): bool
    {
        return $this->dispatch('lock', $unit, $reason);
    }

    public function unlock(InventoryUnit $unit, string $reason): bool
    {
        return $this->dispatch('unlock', $unit, $reason);
    }

    private function dispatch(string $command, InventoryUnit $unit, string $reason): bool
    {
        $urlKey = $command === 'lock' ? 'lock_url' : 'unlock_url';
        $url = config("services.mdm.http.{$urlKey}");

        if (! is_string($url) || trim($url) === '') {
            throw new RuntimeException("MDM HTTP driver requires MDM_HTTP_{$urlKey}.");
        }

        $payload = [
            'command' => $command,
            'mdm_id' => $unit->mdm_id,
            'imei' => $unit->imei_1,
            'inventory_unit_id' => $unit->id,
            'reason' => $reason,
        ];

        $response = Http::timeout(25)
            ->acceptJson()
            ->withHeaders($this->headers())
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('MDM HTTP gateway failed', [
                'command' => $command,
                'url' => $url,
                'status' => $response->status(),
                'body' => substr((string) $response->body(), 0, 500),
            ]);

            return false;
        }

        Log::info('MDM command sent via HTTP gateway', [
            'command' => $command,
            'inventory_unit_id' => $unit->id,
        ]);

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = [];
        $apiKey = config('services.mdm.http.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            $headerName = (string) config('services.mdm.http.api_key_header', 'Authorization');
            $prefix = (string) config('services.mdm.http.api_key_prefix', 'Bearer');
            $headers[$headerName] = trim($prefix.' '.$apiKey);
        }

        return $headers;
    }
}
