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
    | Payment Gateways
    |--------------------------------------------------------------------------
    */

    'powertranz' => [
        'id' => env('POWERTRANZ_ID'),
        'password' => env('POWERTRANZ_PASSWORD'),
        'test_mode' => env('POWERTRANZ_TEST_MODE', true),
    ],

    'wipay' => [
        'platform_account_id' => env('WIPAY_PLATFORM_ACCOUNT_ID'),
        'api_key' => env('WIPAY_API_KEY'),
        'developer_id' => env('WIPAY_DEVELOPER_ID'),
        'secret_key' => env('WIPAY_SECRET_KEY'),
        'test_mode' => env('WIPAY_TEST_MODE', true),
    ],

    'fygaro' => [
        'merchant_id' => env('FYGARO_MERCHANT_ID'),
        'api_key' => env('FYGARO_API_KEY'),
        'secret_key' => env('FYGARO_SECRET_KEY'),
        'test_mode' => env('FYGARO_TEST_MODE', true),
    ],

];
