<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class IntegrationDriverResolver
{
    /**
     * Resolve SMS/MDM driver names. Unknown drivers fall back to `log` so queued jobs
     * do not fail in production when a gateway is not wired yet.
     */
    public function resolve(string $integration, ?string $configuredDriver = null): string
    {
        $driver = strtolower(trim((string) ($configuredDriver ?? config("services.{$integration}.driver", 'log'))));

        if ($driver === '' || $driver === 'log') {
            return 'log';
        }

        $implemented = (array) config("services.{$integration}.implemented_drivers", ['log']);

        if (in_array($driver, $implemented, true)) {
            return $driver;
        }

        Log::critical("{$integration} driver [{$driver}] is not implemented — falling back to log driver.", [
            'integration' => $integration,
            'configured_driver' => $driver,
        ]);

        return 'log';
    }
}
