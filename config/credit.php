<?php

return [
    /*
    | Manual disbursement from the web Lending Panel bypasses the release workflow.
    | Keep false in production — loans should be created via asset release + provisioning.
    */
    'allow_manual_disbursement' => (bool) env('CREDIT_ALLOW_MANUAL_DISBURSEMENT', false),

    'defaults' => [
        'interest_rate' => (float) env('CREDIT_DEFAULT_INTEREST_RATE', 3.5),
        'interest_type' => env('CREDIT_DEFAULT_INTEREST_TYPE', 'flat'),
        'duration_weeks' => (int) env('CREDIT_DEFAULT_DURATION_WEEKS', 52),
        'repayment_frequency' => env('CREDIT_DEFAULT_REPAYMENT_FREQUENCY', 'monthly'),
        'grace_period_days' => (int) env('CREDIT_DEFAULT_GRACE_PERIOD_DAYS', 3),
    ],

    /*
    | Starting deposit (DP) as a percentage of catalog device price when a
    | phone model is selected during KYC Stage 1.
    */
    'default_deposit_percentage' => (float) env('CREDIT_DEFAULT_DEPOSIT_PERCENTAGE', 15),
];
