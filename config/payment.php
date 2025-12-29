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
    | Supported: "wipay"
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'wipay'),

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

        // Automatically disburse payouts via WiPay API (requires API credentials)
        'auto_disburse' => env('PAYOUT_AUTO_DISBURSE', false),
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
    | WiPay Jamaica rate: 3.80% + GCT (15%) = 4.37% total
    |
    */

    'processing_fees' => [
        // WiPay base rate (3.80%)
        'base_rate' => env('PROCESSING_FEE_BASE_RATE', 0.038),

        // GCT rate in Jamaica (15%)
        'gct_rate' => env('PROCESSING_FEE_GCT_RATE', 0.15),

        // Total effective rate: 3.80% * 1.15 = 4.37%
        'default_rate' => env('PROCESSING_FEE_RATE', 0.0437),

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
