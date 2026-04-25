<?php

use App\Livewire\Stock\ImeiSearch;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\InventoryUnit;
use App\Models\Permission;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot open imei search', function () {
    $this->get(route('stock.imei'))->assertRedirect(route('login'));
});

test('finds customer by imei including digit-only variant', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'devices.view', 'guard_name' => 'web']));

    Customer::factory()->create([
        'imei_number' => '123456789012345',
        'phone' => '0712345678',
        'nida_number' => str_repeat('1', 20),
    ]);

    Livewire::actingAs($user)
        ->test(ImeiSearch::class)
        ->set('query', '12345 67890 12345')
        ->call('search')
        ->assertSee('0712345678');
});

test('shows inventory when no customer exists for imei', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'devices.view', 'guard_name' => 'web']));

    $dealer = Dealer::factory()->create(['name' => 'Test Counter TZ']);
    InventoryUnit::factory()->create([
        'dealer_id' => $dealer->id,
        'imei_1' => '555555555555555',
        'status' => 'available',
    ]);

    Livewire::actingAs($user)
        ->test(ImeiSearch::class)
        ->set('query', '555555555555555')
        ->call('search')
        ->assertSee('Stock unit matched')
        ->assertSee('Test Counter TZ');
});

test('resolves customer linked to inventory unit by id', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'devices.view', 'guard_name' => 'web']));

    $unit = InventoryUnit::factory()->create([
        'imei_1' => '444444444444444',
        'status' => 'available',
    ]);

    Customer::factory()->create([
        'inventory_unit_id' => $unit->id,
        'imei_number' => '000000000000000',
        'phone' => '0720000000',
        'nida_number' => str_repeat('2', 20),
    ]);

    Livewire::actingAs($user)
        ->test(ImeiSearch::class)
        ->set('query', '444444444444444')
        ->call('search')
        ->assertSee('0720000000');
});
