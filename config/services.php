<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'collections' => [
        'webhook_secret' => env('COLLECTIONS_WEBHOOK_SECRET'),
        'signature_header' => env('COLLECTIONS_SIGNATURE_HEADER', 'X-MNO-Signature'),
    ],

    'tra' => [
        'vfd_verify_base_url' => env('TRA_VFD_VERIFY_BASE_URL', 'https://vfd.tra.go.tz/verify'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'implemented_drivers' => ['log', 'http', 'beem'],
        'http' => [
            'url' => env('SMS_HTTP_URL'),
            'method' => env('SMS_HTTP_METHOD', 'post'),
            'api_key' => env('SMS_HTTP_API_KEY'),
            'api_key_header' => env('SMS_HTTP_API_KEY_HEADER', 'Authorization'),
            'api_key_prefix' => env('SMS_HTTP_API_KEY_PREFIX', 'Bearer'),
            'sender_id' => env('SMS_SENDER_ID'),
        ],
    ],

    'mdm' => [
        'driver' => env('MDM_DRIVER', 'log'),
        'implemented_drivers' => ['log', 'http'],
        'http' => [
            'lock_url' => env('MDM_HTTP_LOCK_URL'),
            'unlock_url' => env('MDM_HTTP_UNLOCK_URL'),
            'api_key' => env('MDM_HTTP_API_KEY'),
            'api_key_header' => env('MDM_HTTP_API_KEY_HEADER', 'Authorization'),
            'api_key_prefix' => env('MDM_HTTP_API_KEY_PREFIX', 'Bearer'),
        ],
    ],

    'beem' => [
        'base_url' => env('BEEM_BASE_URL', 'https://apisms.beem.africa'),
        'api_key' => env('BEEM_API_KEY'),
        'secret_key' => env('BEEM_SECRET_KEY'),
        'sender_id' => env('BEEM_SENDER_ID'),
    ],

    'selcom' => [
        'base_url' => env('SELCOM_BASE_URL', 'https://apigw.selcommobile.com'),
        'vendor' => env('SELCOM_VENDOR'),
        'api_key' => env('SELCOM_API_KEY'),
        'api_secret' => env('SELCOM_API_SECRET'),
        'payment_status_poll_max_attempts' => (int) env('SELCOM_PAYMENT_STATUS_POLL_MAX_ATTEMPTS', 8),
        'payment_status_poll_sleep_ms' => (int) env('SELCOM_PAYMENT_STATUS_POLL_SLEEP_MS', 2000),
    ],

    'face_match' => [
        'url' => env('FACE_MATCH_URL'),
        'required' => filter_var(
            env('FACE_MATCH_REQUIRED', env('APP_ENV') === 'production'),
            FILTER_VALIDATE_BOOL
        ),
    ],

];
