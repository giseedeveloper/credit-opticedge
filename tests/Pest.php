<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function kycMarkPreHandoverComplete(
    Customer $customer,
    ?string $mdmStatus = 'skipped_no_mdm_id',
): Customer {
    $metadata = $customer->metadata ?? [];
    $metadata['pre_handover_checklist'] = [
        'device_unboxed' => true,
        'device_boot_verified' => true,
        'mdm_lock_confirmed' => true,
        'completed_at' => now()->toIso8601String(),
        'completed_by' => 'test',
        'mdm_lock_status' => $mdmStatus,
    ];
    $customer->update(['metadata' => $metadata]);

    return $customer->fresh();
}
