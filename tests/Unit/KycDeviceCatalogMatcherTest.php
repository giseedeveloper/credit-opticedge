<?php

use App\Models\Brand;
use App\Models\PhoneModel;
use App\Services\KycDeviceCatalogMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('auto-selects a catalog model when scan text matches with high confidence', function () {
    $brand = Brand::factory()->create(['name' => 'Samsung']);
    $model = PhoneModel::factory()->create([
        'brand_id' => $brand->id,
        'name' => 'Galaxy A15',
        'is_active' => true,
        'specifications' => [
            'model_code' => 'SM-A155F',
            'ram' => '6GB',
            'storage' => '128GB',
        ],
    ]);

    $match = app(KycDeviceCatalogMatcher::class)->matchFromScan([
        'detected_model_text' => 'Samsung Galaxy A15',
        'detected_model_code' => 'SM-A155F',
        'detected_ram' => '6GB',
        'detected_storage' => '128GB',
        'raw_text' => 'Samsung Galaxy A15 SM-A155F 6GB 128GB',
    ]);

    expect($match['phone_model_id'])->toBe((string) $model->id)
        ->and($match['brand_id'])->toBe((string) $brand->id)
        ->and($match['auto_selected'])->toBeTrue()
        ->and($match['confidence'])->toBeGreaterThanOrEqual(0.62);
});

it('returns empty match when scan text is blank', function () {
    $match = app(KycDeviceCatalogMatcher::class)->matchFromScan([]);

    expect($match['phone_model_id'])->toBeNull()
        ->and($match['auto_selected'])->toBeFalse()
        ->and($match['confidence'])->toBe(0.0);
});

it('does not auto-select when confidence is below threshold', function () {
    $brand = Brand::factory()->create(['name' => 'Samsung']);
    PhoneModel::factory()->create([
        'brand_id' => $brand->id,
        'name' => 'Galaxy A15',
        'is_active' => true,
    ]);

    $match = app(KycDeviceCatalogMatcher::class)->matchFromScan([
        'raw_text' => 'UNKNOWN DEVICE XYZ',
    ]);

    expect($match['phone_model_id'])->toBeNull()
        ->and($match['auto_selected'])->toBeFalse();
});
