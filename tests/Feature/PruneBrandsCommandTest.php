<?php

use App\Models\Brand;
use App\Models\PhoneModel;

use function Pest\Laravel\artisan;

it('dry-run does not change anything', function () {
    $keep = Brand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung', 'is_active' => true]);
    $fake = Brand::factory()->create(['name' => 'Amazzo', 'slug' => 'amazzo', 'is_active' => true]);

    artisan('app:prune-brands', ['--keep' => ['samsung']])->assertSuccessful();

    expect($keep->fresh()->is_active)->toBeTrue();
    expect($fake->fresh()->is_active)->toBeTrue();
});

it('disables brands not in keep list and can disable their models', function () {
    $keep = Brand::factory()->create(['name' => 'Tecno', 'slug' => 'tecno', 'is_active' => true]);
    $fake = Brand::factory()->create(['name' => 'OE', 'slug' => 'oe', 'is_active' => true]);

    $model = PhoneModel::factory()->create([
        'brand_id' => $fake->id,
        'name' => 'OE Model 1',
        'slug' => 'oe-model-1',
        'is_active' => true,
    ]);

    artisan('app:prune-brands', [
        '--apply' => true,
        '--also-disable-models' => true,
        '--keep' => ['tecno'],
    ])->assertSuccessful();

    expect($keep->fresh()->is_active)->toBeTrue();
    expect($fake->fresh()->is_active)->toBeFalse();
    expect($model->fresh()->is_active)->toBeFalse();
});
