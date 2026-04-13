<?php

return [
    'defaults' => [
        'interest_rate' => (float) env('CREDIT_DEFAULT_INTEREST_RATE', 3.5),
        'interest_type' => env('CREDIT_DEFAULT_INTEREST_TYPE', 'flat'),
        'duration_weeks' => (int) env('CREDIT_DEFAULT_DURATION_WEEKS', 52),
        'repayment_frequency' => env('CREDIT_DEFAULT_REPAYMENT_FREQUENCY', 'monthly'),
        'grace_period_days' => (int) env('CREDIT_DEFAULT_GRACE_PERIOD_DAYS', 3),
    ],
];
