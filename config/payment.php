<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used when
    | a provider has not configured their own gateway credentials.
    |
    | Supported: "powertranz", "wipay", "fygaro"
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'powertranz'),

    /*
    |--------------------------------------------------------------------------
    | Default Gateway Type
    |--------------------------------------------------------------------------
    |
    | The default money flow strategy when a provider hasn't configured
    | a gateway. Options: "escrow" (platform collects 100%, pays out later)
    | or "split" (instant split at payment time).
    |
    */

    'default_gateway_type' => env('PAYMENT_DEFAULT_TYPE', 'escrow'),

    /*
    |--------------------------------------------------------------------------
    | Payout Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how and when scheduled payouts are processed for providers
    | using the escrow payment model.
    |
    */

    'payout_schedule' => [
        // Frequency: daily, weekly, biweekly, monthly
        'frequency' => env('PAYOUT_FREQUENCY', 'weekly'),

        // Day of week for weekly payouts (0=Sunday, 5=Friday)
        'day_of_week' => env('PAYOUT_DAY_OF_WEEK', 'friday'),

        // Minimum amount required to trigger a payout (in JMD)
        'minimum_amount' => env('PAYOUT_MINIMUM_AMOUNT', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ledger Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the ledger system for tracking provider balances.
    |
    */

    'ledger' => [
        // Number of days to hold funds before they become available for payout
        'hold_period_days' => env('LEDGER_HOLD_PERIOD_DAYS', 7),

        // Automatically release held funds after this period
        'auto_release' => env('LEDGER_AUTO_RELEASE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Fees
    |--------------------------------------------------------------------------
    |
    | Default processing fee configuration for payment gateways.
    |
    */

    'processing_fees' => [
        // Default processing fee rate (percentage as decimal)
        'default_rate' => env('PROCESSING_FEE_RATE', 0.035),

        // Default processing fee payer: 'platform', 'provider', 'client'
        'default_payer' => env('PROCESSING_FEE_PAYER', 'client'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies supported by the payment system.
    |
    */

    'supported_currencies' => ['JMD', 'USD'],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for payments when not specified.
    |
    */

    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'JMD'),

];
