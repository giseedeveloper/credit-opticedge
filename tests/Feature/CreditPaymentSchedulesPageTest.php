<?php

use App\Livewire\Credit\PaymentSchedules;
use App\Models\Loan;
use App\Models\Permission;
use App\Models\User;
use Livewire\Livewire;

test('users with loans.view can load the payment schedules page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web'])
    );

    $this->actingAs($user)
        ->get(route('credit.schedules'))
        ->assertSuccessful();
});

test('selecting a loan loads detail without relation errors', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'loans.view', 'guard_name' => 'web'])
    );
    $loan = Loan::factory()->create(['status' => 'active']);

    Livewire::actingAs($user)
        ->test(PaymentSchedules::class)
        ->call('selectLoan', $loan->id)
        ->assertSet('selectedLoanId', $loan->id)
        ->assertOk();
});
