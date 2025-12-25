<?php

/**
 * Sentry Laravel SDK configuration file.
 *
 * @see https://docs.sentry.io/platforms/php/guides/laravel/configuration/options/
 */
return [
    // Sentry DSN - get this from sentry.io project settings
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Release version
    'release' => env('SENTRY_RELEASE', env('APP_VERSION', '1.0.0')),

    // Environment
    'environment' => env('APP_ENV', 'production'),

    // Enable breadcrumbs for better context
    'breadcrumbs' => [
        // Capture Laravel logs as breadcrumbs
        'logs' => true,

        // Capture SQL queries as breadcrumbs
        'sql_queries' => true,

        // Capture SQL query bindings (parameters)
        'sql_bindings' => true,

        // Capture queue job information
        'queue_info' => true,

        // Capture command information
        'command_info' => true,
    ],

    // Sample rate for performance tracing (0.0 to 1.0)
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    // Sample rate for profiling (0.0 to 1.0)
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // Send default PII (user info, etc)
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    // Controllers to not capture exceptions from
    'controllers_base_namespace' => env('SENTRY_CONTROLLERS_BASE_NAMESPACE', 'App\\Http\\Controllers'),
];
