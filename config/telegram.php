<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Token
    |--------------------------------------------------------------------------
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'url' => env('TELEGRAM_WEBHOOK_URL'),
        'certificate' => env('TELEGRAM_WEBHOOK_CERTIFICATE'),
        'max_connections' => env('TELEGRAM_WEBHOOK_MAX_CONNECTIONS', 40),
        'allowed_updates' => [
            'message',
            'callback_query',
            'channel_post',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Channel IDs
    |--------------------------------------------------------------------------
    */
    'channels' => [
        'management' => env('TELEGRAM_MANAGEMENT_CHANNEL_ID'),
        'main' => env('TELEGRAM_MAIN_CHANNEL_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin User IDs
    |--------------------------------------------------------------------------
    */
    'admin_ids' => array_filter(
        explode(',', env('TELEGRAM_ADMIN_IDS', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org/bot'),
        'timeout' => env('TELEGRAM_API_TIMEOUT', 60),
        'connect_timeout' => env('TELEGRAM_API_CONNECT_TIMEOUT', 10),
        'retry_attempts' => env('TELEGRAM_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('TELEGRAM_API_RETRY_DELAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('LOG_TELEGRAM_REQUESTS', false),
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'vacancy' => [
            'title_max_length' => 100,
            'description_max_length' => 500,
            'parse_mode' => 'HTML',
        ],

        'buttons' => [
            'approve' => '✅ Tasdiqlash',
            'reject' => '❌ Rad etish',
            'view_details' => '📝 Batafsil',
            'statistics' => '📊 Statistika',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'api_requests_per_second' => 25,
        'group_messages_per_minute' => 18,
        'channel_messages_per_minute' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Retry Configuration
    |--------------------------------------------------------------------------
    */
    'auto_retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'delay_seconds' => 2,
        'exponential_backoff' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    */
    'file_limits' => [
        'photo_max_size' => 10 * 1024 * 1024,
        'document_max_size' => 50 * 1024 * 1024,
        'video_max_size' => 50 * 1024 * 1024,
    ],
];
