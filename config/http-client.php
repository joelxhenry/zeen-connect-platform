<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | These settings apply to all HTTP requests made through the HttpClient
    | service unless overridden by a preset or fluent configuration.
    |
    */

    'default' => [
        'timeout' => env('HTTP_CLIENT_TIMEOUT', 30),
        'connect_timeout' => env('HTTP_CLIENT_CONNECT_TIMEOUT', 10),
        'retry_attempts' => env('HTTP_CLIENT_RETRY_ATTEMPTS', 3),
        'retry_delays' => [100, 500, 1000], // milliseconds
        'retryable_status_codes' => [429, 500, 502, 503, 504],
        'retry_on_connection_error' => true,
        'log_channel' => env('HTTP_CLIENT_LOG_CHANNEL', 'stack'),
        'logging_enabled' => env('HTTP_CLIENT_LOGGING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Preset Configurations
    |--------------------------------------------------------------------------
    |
    | Define preset configurations for different use cases. These inherit
    | from the default configuration and override specific values.
    |
    */

    'presets' => [
        'payments' => [
            'timeout' => 60,
            'connect_timeout' => 15,
            'retry_attempts' => 3,
            'retry_delays' => [200, 1000, 3000], // More conservative for payments
            'log_channel' => 'payments',
            'retryable_status_codes' => [429, 502, 503, 504], // Don't retry 500 for payments
        ],

        'bookings' => [
            'timeout' => 30,
            'retry_attempts' => 2,
            'retry_delays' => [100, 500],
            'log_channel' => 'stack',
        ],

        'external' => [
            'timeout' => 45,
            'retry_attempts' => 3,
            'retry_delays' => [100, 500, 2000],
            'log_channel' => 'stack',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Header Patterns
    |--------------------------------------------------------------------------
    |
    | Headers containing these patterns (case-insensitive) will be redacted
    | in logs. Add any custom authentication headers used by your APIs.
    |
    */

    'sensitive_headers' => [
        'authorization',
        'x-api-key',
        'api-key',
        'powertranz-password',
        'powertranz-powertranzid',
        'x-secret-key',
        'x-merchant-id',
        'bearer',
        'cookie',
        'set-cookie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Body Key Patterns
    |--------------------------------------------------------------------------
    |
    | Request/response body keys containing these patterns (case-insensitive)
    | will have their values redacted in logs.
    |
    */

    'sensitive_body_keys' => [
        'password',
        'secret',
        'token',
        'card_number',
        'cardpan',
        'cvv',
        'cardcvv',
        'card_expiration',
        'cardexpiration',
        'cardholdername',
        'api_key',
        'apikey',
        'private_key',
        'access_token',
        'refresh_token',
        'credentials',
    ],

];
