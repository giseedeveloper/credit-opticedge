<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\PhoneModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ExternalDeviceCatalogService
{
    public function isEnabled(): bool
    {
        return (bool) config('device_catalog.enabled', false);
    }

    public function syncModelsForBrand(Brand $brand, bool $force = false): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $blocked = collect((array) config('device_catalog.sync_blocklist_slugs', []))
            ->map(fn ($slug) => trim((string) $slug))
            ->filter()
            ->values();

        if ($blocked->contains($brand->slug)) {
            return 0;
        }

        $provider = (string) config('device_catalog.provider', 'mobileapi');

        return match ($provider) {
            'mobileapi' => $this->syncFromMobileApi($brand, $force),
            default => 0,
        };
    }

    private function syncFromMobileApi(Brand $brand, bool $force): int
    {
        $key = (string) config('device_catalog.mobileapi.key', '');
        $baseUrl = rtrim((string) config('device_catalog.mobileapi.base_url', ''), '/');

        if ($key === '' || $baseUrl === '') {
            return 0;
        }

        $cacheKey = "device_catalog:mobileapi:last_sync:brand:{$brand->id}";
        $today = CarbonImmutable::now()->toDateString();

        if (! $force && Cache::get($cacheKey) === $today) {
            return 0;
        }

        $request = $this->mobileApiRequest();
        $maxPages = max(1, (int) config('device_catalog.mobileapi.max_pages', 20));

        $createdOrUpdated = 0;
        $page = 1;

        while ($page <= $maxPages) {
            $response = $request->get("{$baseUrl}/devices/by-manufacturer/", [
                'manufacturer' => $brand->name,
                'page' => $page,
            ]);

            if (! $response->successful()) {
                break;
            }

            /** @var array<string,mixed> $payload */
            $payload = $response->json() ?? [];
            $devices = $payload['devices'] ?? [];

            if (! is_array($devices) || $devices === []) {
                break;
            }

            foreach ($devices as $device) {
                if (! is_array($device)) {
                    continue;
                }

                $externalId = isset($device['id']) ? (string) $device['id'] : '';
                $name = isset($device['name']) ? trim((string) $device['name']) : '';

                if ($name === '') {
                    continue;
                }

                $slug = $this->uniquePhoneModelSlug(
                    brandName: $brand->name,
                    modelName: $name,
                    externalSource: 'mobileapi',
                    externalId: $externalId !== '' ? $externalId : null
                );

                $match = $externalId !== ''
                    ? ['external_source' => 'mobileapi', 'external_id' => $externalId]
                    : ['brand_id' => $brand->id, 'name' => $name];

                PhoneModel::query()->updateOrCreate(
                    $match,
                    [
                        'brand_id' => $brand->id,
                        'slug' => $slug,
                        'name' => $name,
                        'retail_price' => 0,
                        'cost_price' => 0,
                        'specifications' => [],
                        'is_active' => true,
                        'external_source' => 'mobileapi',
                        'external_id' => $externalId !== '' ? $externalId : null,
                        'last_synced_at' => now(),
                    ]
                );

                $createdOrUpdated++;
            }

            $hasNext = (bool) ($payload['has_next'] ?? false);
            if (! $hasNext) {
                break;
            }

            $page++;
        }

        Cache::put($cacheKey, $today, now()->addDay());

        return $createdOrUpdated;
    }

    private function uniquePhoneModelSlug(string $brandName, string $modelName, string $externalSource, ?string $externalId): string
    {
        $base = Str::slug($brandName.' '.$modelName);

        $exists = PhoneModel::query()
            ->where('slug', $base)
            ->when($externalId, fn ($q) => $q->where(function ($q) use ($externalSource, $externalId): void {
                $q->whereNull('external_id')
                    ->orWhere(function ($q) use ($externalSource, $externalId): void {
                        $q->where('external_source', '!=', $externalSource)
                            ->orWhere('external_id', '!=', $externalId);
                    });
            }))
            ->exists();

        if (! $exists) {
            return $base;
        }

        if ($externalId) {
            $candidate = "{$base}-{$externalId}";

            if (! PhoneModel::query()->where('slug', $candidate)->exists()) {
                return $candidate;
            }
        }

        $suffix = Str::lower(Str::random(6));

        return "{$base}-{$suffix}";
    }

    private function mobileApiRequest(): PendingRequest
    {
        $key = (string) config('device_catalog.mobileapi.key', '');
        $connectTimeout = max(1, (int) config('device_catalog.mobileapi.connect_timeout', 5));
        $timeout = max(5, (int) config('device_catalog.mobileapi.timeout', 20));

        return Http::asJson()
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->retry(2, 250, throw: false)
            ->withHeaders([
                'Authorization' => "Token {$key}",
                'Accept' => 'application/json',
            ]);
    }
}
