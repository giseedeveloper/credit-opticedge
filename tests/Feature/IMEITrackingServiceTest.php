<?php

use App\Models\Brand;
use App\Models\Dealer;
use App\Models\InventoryUnit;
use App\Models\PhoneModel;
use App\Services\IMEITrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(IMEITrackingService::class);
    $brand = Brand::factory()->create();
    $this->model = PhoneModel::factory()->create(['brand_id' => $brand->id]);
    $this->vendor = Dealer::factory()->create();
});

test('registers a unit with unique IMEI', function () {
    $unit = $this->service->registerUnit([
        'phone_model_id' => $this->model->id,
        'dealer_id' => $this->vendor->id,
        'imei_1' => '123456789012345',
        'status' => 'available',
    ]);

    expect(InventoryUnit::count())->toBe(1)
        ->and($unit->imei_1)->toBe('123456789012345');
});

test('throws validation exception for duplicate IMEI', function () {
    InventoryUnit::factory()->create([
        'phone_model_id' => $this->model->id,
        'imei_1' => '111111111111111',
    ]);

    $this->service->assertImeiUnique('111111111111111');
})->throws(ValidationException::class);

test('bulk register inserts unique rows and skips duplicates', function () {
    InventoryUnit::factory()->create([
        'phone_model_id' => $this->model->id,
        'imei_1' => 'DUPE00000000001',
    ]);

    $rows = collect([
        ['phone_model_id' => $this->model->id, 'imei_1' => 'NEW000000000001'],
        ['phone_model_id' => $this->model->id, 'imei_1' => 'NEW000000000002'],
        ['phone_model_id' => $this->model->id, 'imei_1' => 'DUPE00000000001'],
    ]);

    $report = $this->service->bulkRegister($rows);

    expect($report['inserted'])->toBe(2)
        ->and($report['skipped'])->toBe(1)
        ->and(InventoryUnit::count())->toBe(3);
});

test('bulk register handles empty IMEI rows', function () {
    $rows = collect([
        ['phone_model_id' => $this->model->id, 'imei_1' => ''],
    ]);

    $report = $this->service->bulkRegister($rows);

    expect($report['inserted'])->toBe(0)
        ->and($report['skipped'])->toBe(1)
        ->and($report['errors'])->toHaveCount(1);
});

test('transferUnit assigns vendor and sets status to assigned', function () {
    $unit = InventoryUnit::factory()->create([
        'phone_model_id' => $this->model->id,
        'imei_1' => '999000000000001',
        'status' => 'available',
    ]);

    $this->service->transferUnit($unit, $this->vendor->id);

    expect($unit->fresh()->dealer_id)->toBe($this->vendor->id)
        ->and($unit->fresh()->status)->toBe('assigned');
});
