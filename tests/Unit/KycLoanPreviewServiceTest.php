<?php

use App\Services\KycLoanPreviewService;
use Tests\TestCase;

uses(TestCase::class);

it('calculates daily loan preview from cash price and deposit', function () {
    $preview = app(KycLoanPreviewService::class)->preview([
        'cash_price' => 400000,
        'deposit_amount' => 60000,
        'preferred_repayment' => 'daily',
        'duration_weeks' => 12,
        'interest_rate' => 20,
        'interest_type' => 'flat',
    ]);

    expect($preview['financed_principal'])->toBe(340000.0)
        ->and($preview['repayment_frequency'])->toBe('daily')
        ->and($preview['installment_count'])->toBe(84)
        ->and($preview['installment_amount'])->toBeGreaterThan(0)
        ->and($preview['total_payable'])->toBeGreaterThan($preview['financed_principal']);
});
