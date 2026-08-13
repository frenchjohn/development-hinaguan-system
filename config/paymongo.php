<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMongo API Keys
    |--------------------------------------------------------------------------
    |
    | Public key is safe to expose in the browser and is used by the PayMongo
    | JS SDK to create payment methods client-side. The secret key is used
    | server-side only to create payment intents and verify payments.
    |
    */

    'public_key' => env('PAYMONGO_PUBLIC_KEY', ''),

    'secret_key' => env('PAYMONGO_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | PayMongo Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Optional. Used to verify that incoming webhook requests really come
    | from PayMongo (HMAC-SHA256 of the raw body). Leave empty while testing.
    |
    */

    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Intent settings
    |--------------------------------------------------------------------------
    |
    | deposit_percentage: the share of the reservation total collected at
    | booking time. The rest is settled at check-in (matching the site's
    | "pay a small deposit" promise).
    |
    */

    'deposit_percentage' => (float) env('PAYMONGO_DEPOSIT_PERCENTAGE', 50),

    'statement_descriptor' => env('PAYMONGO_STATEMENT_DESCRIPTOR', 'HINAGUAN NATURE PARK'),

];
