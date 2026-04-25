<?php

use App\Models\Brand;
use App\Models\PhoneModel;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

it('syncs phone models for an active brand when enabled', function () {
    config()->set('device_catalog.enabled', true);
    config()->set('device_catalog.provider', 'mobileapi');
    config()->set('device_catalog.mobileapi.base_url', 'https://api.mobileapi.dev');
    config()->set('device_catalog.mobileapi.key', 'test-key');
    config()->set('device_catalog.mobileapi.max_pages', 3);
    config()->set('device_catalog.sync_blocklist_slugs', []);

    $brand = Brand::factory()->create([
        'name' => 'Infinix',
        'slug' => 'infinix',
        'is_active' => true,
    ]);

    Http::fake([
        'https://api.mobileapi.dev/devices/by-manufacturer/*' => Http::sequence()
            ->push([
                'manufacturer' => 'Infinix',
                'has_next' => true,
                'devices' => [
                    ['id' => 101, 'name' => 'Hot 30'],
                    ['id' => 102, 'name' => 'Note 30'],
                ],
            ], 200)
            ->push([
                'manufacturer' => 'Infinix',
                'has_next' => false,
                'devices' => [
                    ['id' => 103, 'name' => 'Smart 8'],
                ],
            ], 200),
    ]);

    artisan('app:sync-device-catalog')->assertSuccessful();

    expect(PhoneModel::query()->where('brand_id', $brand->id)->count())->toBe(3);
    expect(PhoneModel::query()->where('external_source', 'mobileapi')->count())->toBe(3);
});

it('avoids slug collisions by appending external id', function () {
    config()->set('device_catalog.enabled', true);
    config()->set('device_catalog.provider', 'mobileapi');
    config()->set('device_catalog.mobileapi.base_url', 'https://api.mobileapi.dev');
    config()->set('device_catalog.mobileapi.key', 'test-key');
    config()->set('device_catalog.mobileapi.max_pages', 1);
    config()->set('device_catalog.sync_blocklist_slugs', []);

    $brand = Brand::factory()->create([
        'name' => 'Huawei',
        'slug' => 'huawei',
        'is_active' => true,
    ]);

    PhoneModel::factory()->create([
        'brand_id' => $brand->id,
        'name' => '20L',
        'slug' => 'huawei-20l',
        'is_active' => true,
    ]);

    Http::fake([
        'https://api.mobileapi.dev/devices/by-manufacturer/*' => Http::response([
            'manufacturer' => 'Huawei',
            'has_next' => false,
            'devices' => [
                ['id' => 679, 'name' => '20L+'],
            ],
        ], 200),
    ]);

    artisan('app:sync-device-catalog')->assertSuccessful();

    expect(PhoneModel::query()->where('external_id', '679')->firstOrFail()->slug)->toBe('huawei-20l-679');
});
