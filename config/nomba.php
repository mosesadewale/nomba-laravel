<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Nomba API Credentials
    |--------------------------------------------------------------------------
    */

    'client_id'     => env('NOMBA_CLIENT_ID', ''),
    'client_secret' => env('NOMBA_CLIENT_SECRET', ''),
    'account_id'    => env('NOMBA_ACCOUNT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |
    | Supported: "sandbox", "production"
    |--------------------------------------------------------------------------
    */

    'environment' => env('NOMBA_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    */

    'webhook_secret'         => env('NOMBA_WEBHOOK_SECRET', ''),
    'webhook_path'           => env('NOMBA_WEBHOOK_PATH', 'nomba/webhook'),
    'register_webhook_route' => env('NOMBA_REGISTER_ROUTE', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */

    'timeout'        => (float) env('NOMBA_TIMEOUT', 30),
    'retry_attempts' => (int) env('NOMBA_RETRY_ATTEMPTS', 3),

];
