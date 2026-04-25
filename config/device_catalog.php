<?php

return [
    'enabled' => (bool) env('DEVICE_CATALOG_ENABLED', false),

    'provider' => env('DEVICE_CATALOG_PROVIDER', 'mobileapi'),

    'mobileapi' => [
        'base_url' => env('MOBILEAPI_BASE_URL', 'https://api.mobileapi.dev'),
        'key' => env('MOBILEAPI_KEY'),
        'connect_timeout' => (int) env('MOBILEAPI_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('MOBILEAPI_TIMEOUT', 20),
        'max_pages' => (int) env('MOBILEAPI_MAX_PAGES', 20),
    ],

    // Prevent syncing for internal/fake brands that shouldn't expand into global catalogs.
    'sync_blocklist_slugs' => array_values(array_filter(explode(',', (string) env('DEVICE_CATALOG_SYNC_BLOCKLIST', 'oe')))),
];
