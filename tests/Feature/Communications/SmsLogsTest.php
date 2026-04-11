<?php

use App\Livewire\Communications\SmsLogs;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true]);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['description' => 'Administrator']);
    $this->user->assignRole('admin');
    $this->user->syncRoleColumn('admin');
    $this->user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'sms_campaign.view', 'guard_name' => 'web'])
    );
});

it('loads the sms logs page and segments sms types correctly', function () {
    $customer = Customer::factory()->create();

    activity('system')
        ->performedOn($customer)
        ->causedBy($this->user)
        ->withProperties(['channel' => 'bulk'])
        ->log('Bulk SMS: Loan reminder for customer');

    activity('system')
        ->causedBy($this->user)
        ->log('Automated SMS: Device lock notification');

    activity('system')
        ->performedOn($customer)
        ->log('Welcome SMS: Karibu kwenye OpticEdge');

    activity('system')
        ->log('System SMS queue reconciled successfully');

    $this->actingAs($this->user)
        ->get(route('comms.sms'))
        ->assertOk()
        ->assertSeeText('4 total messages')
        ->assertSeeText('1 Bulk SMS')
        ->assertSeeText('1 Automated')
        ->assertSeeText('1 Welcome')
        ->assertSeeText('1 System');
});

it('shows recipient details in the sms log slide over', function () {
    $customer = Customer::factory()->create([
        'first_name' => 'Asha',
        'last_name' => 'Mrema',
        'phone' => '0712345678',
    ]);

    $activity = activity('system')
        ->performedOn($customer)
        ->causedBy($this->user)
        ->log('Bulk SMS: Special follow-up reminder');

    Livewire::actingAs($this->user)
        ->test(SmsLogs::class)
        ->call('openDetail', $activity->id)
        ->assertSet('showDetail', true)
        ->assertSee('Asha Mrema')
        ->assertSee('0712345678')
        ->assertSee('Bulk SMS');
});
