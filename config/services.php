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

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway - WiPay
    |--------------------------------------------------------------------------
    |
    | WiPay is the sole payment gateway. Supports both split payments
    | (instant payouts to provider's WiPay account) and escrow mode
    | (platform collects, then pays out to provider's bank account).
    |
    */

    'wipay' => [
        // Platform credentials for collecting payments
        'platform_account_id' => env('WIPAY_PLATFORM_ACCOUNT_ID', '1234567890'),
        'api_key' => env('WIPAY_API_KEY', '123'),
        'developer_id' => env('WIPAY_DEVELOPER_ID'),
        'secret_key' => env('WIPAY_SECRET_KEY'),

        // Environment settings
        'test_mode' => env('WIPAY_TEST_MODE', true),

        // Country code for WiPay API (JM = Jamaica, TT = Trinidad)
        'country_code' => env('WIPAY_COUNTRY_CODE', 'JM'),

        // API endpoints
        'api_url' => env('WIPAY_API_URL', 'https://jm.wipayfinancial.com/plugins/payments/request'),
        'disbursement_url' => env('WIPAY_DISBURSEMENT_URL', 'https://jm.wipayfinancial.com/plugins/payments/request'),
    ],

];
