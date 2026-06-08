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

    /*
    | Mandatory FO checklist (unbox / boot / MDM lock) before asset release.
    */
    'require_pre_handover_checklist' => (bool) env('CREDIT_REQUIRE_PRE_HANDOVER_CHECKLIST', true),

    /*
    | When true, handover fails if MDM lock cannot be sent (requires MDM_HTTP_* env).
    | Keep false until the MDM vendor integration is live in production.
    */
    'require_mdm_lock_at_handover' => (bool) env('CREDIT_REQUIRE_MDM_LOCK_AT_HANDOVER', false),
];
