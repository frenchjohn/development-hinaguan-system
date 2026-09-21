<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Xendit API Keys
    |--------------------------------------------------------------------------
    |
    | Public key is used client-side (Xendit.js) if needed.
    | Secret key is used server-side only for all API calls.
    | Use xnd_development_* keys for testing mode.
    |
    */

    'public_key' => env('XENDIT_PUBLIC_KEY', ''),

    'secret_key' => env('XENDIT_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Xendit Webhook Token
    |--------------------------------------------------------------------------
    |
    | The callback token Xendit sends in the x-callback-token header.
    | Set this in your Xendit dashboard under Developers > Webhooks.
    | Leave empty during testing to skip verification.
    |
    */

    'webhook_token' => env('XENDIT_WEBHOOK_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | deposit_percentage: portion of the total collected at booking time.
    | Falls back to PAYMONGO_DEPOSIT_PERCENTAGE for backwards compatibility.
    |
    */

    'deposit_percentage' => (float) env('XENDIT_DEPOSIT_PERCENTAGE', env('PAYMONGO_DEPOSIT_PERCENTAGE', 50)),

    'statement_descriptor' => env('XENDIT_STATEMENT_DESCRIPTOR', 'HINAGUAN NATURE PARK'),

];
