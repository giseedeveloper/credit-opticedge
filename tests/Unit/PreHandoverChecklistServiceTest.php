<?php

use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\User;
use App\Services\PreHandoverChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('marks checklist complete and skips mdm when unit has no mdm id', function () {
    $agent = User::factory()->create();
    $unit = InventoryUnit::factory()->create(['mdm_id' => null]);
    $customer = Customer::factory()->create(['inventory_unit_id' => $unit->id]);

    $checklist = app(PreHandoverChecklistService::class)->complete(
        $customer,
        [
            'device_unboxed' => true,
            'device_boot_verified' => true,
            'mdm_lock_confirmed' => true,
        ],
        $agent,
    );

    expect($checklist['device_unboxed'])->toBeTrue()
        ->and($checklist['mdm_lock_status'])->toBe('skipped_no_mdm_id')
        ->and($customer->fresh()->hasCompletedPreHandoverChecklist())->toBeTrue();
});

it('rejects incomplete checklist submissions', function () {
    $agent = User::factory()->create();
    $customer = Customer::factory()->create();

    app(PreHandoverChecklistService::class)->complete(
        $customer,
        [
            'device_unboxed' => true,
            'device_boot_verified' => false,
            'mdm_lock_confirmed' => true,
        ],
        $agent,
    );
})->throws(ValidationException::class);
