<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tracking URL Configuration
    |--------------------------------------------------------------------------
    */
    'url' => env('TRACKING_URL', env('APP_URL') . '/track'),

    /*
    |--------------------------------------------------------------------------
    | Tracking Code Configuration
    |--------------------------------------------------------------------------
    */
    'code' => [
        'length' => env('TRACKING_CODE_LENGTH', 12),
        'characters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'prefix' => '',
        'case_sensitive' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Click Deduplication
    |--------------------------------------------------------------------------
    */
    'deduplication' => [
        'enabled' => true,
        'method' => 'ip+user_agent',
        'window_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled' => env('CLICK_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('CLICK_RATE_LIMIT_MAX_ATTEMPTS', 10),
        'decay_seconds' => env('CLICK_RATE_LIMIT_SECONDS', 300),
        'key_prefix' => 'click_limit:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie Configuration
    |--------------------------------------------------------------------------
    */
    'cookie' => [
        'name' => 'tg_bot_tracker',
        'lifetime' => env('TRACKING_COOKIE_LIFETIME', 2592000),
        'secure' => env('APP_ENV') === 'production',
        'http_only' => true,
        'same_site' => 'lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Address Detection
    |--------------------------------------------------------------------------
    */
    'ip_detection' => [
        'trust_proxies' => env('TRUST_PROXIES', false),
        'headers' => [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Detection
    |--------------------------------------------------------------------------
    */
    'bot_detection' => [
        'enabled' => true,
        'user_agents' => [
            'bot', 'crawl', 'spider', 'scraper', 'curl', 'wget',
            'python-requests', 'java', 'apache-httpclient',
        ],
        'exclude_bots' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Referrer Tracking
    |--------------------------------------------------------------------------
    */
    'referrer' => [
        'enabled' => true,
        'store_full_url' => false,
        'max_length' => 255,
    ],

    /*
    |--------------------------------------------------------------------------
    | Geolocation
    |--------------------------------------------------------------------------
    */
    'geolocation' => [
        'enabled' => false,
        'provider' => 'ip-api',
        'cache_duration' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => true,
        'connection' => env('QUEUE_CONNECTION', 'redis'),
        'queue_name' => 'click-tracking',
        'retry_after' => 90,
        'max_tries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'driver' => env('CACHE_STORE', 'redis'),
        'ttl' => 3600,
        'prefix' => 'tracking:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Integration
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'google_analytics' => [
            'enabled' => false,
            'measurement_id' => env('GA_MEASUREMENT_ID'),
        ],
        'yandex_metrika' => [
            'enabled' => false,
            'counter_id' => env('YM_COUNTER_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'delete_old_clicks' => true,
        'keep_days' => 90,
        'archive_before_delete' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect Configuration
    |--------------------------------------------------------------------------
    */
    'redirect' => [
        'type' => 'permanent',
        'status_code' => 301,
        'delay_ms' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Notifications
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'enabled' => false,
        'events' => [
            'click_threshold' => 100,
            'daily_summary' => true,
        ],
        'endpoints' => [],
    ],
];
