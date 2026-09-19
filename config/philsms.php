<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PhilSMS API Endpoint
    |--------------------------------------------------------------------------
    |
    | Base API URL for PhilSMS v3.
    | Default: https://dashboard.philsms.com/api/v3/
    |
    */

    'api_endpoint' => env('PHILSMS_API_ENDPOINT', 'https://dashboard.philsms.com/api/v3/'),

    /*
    |--------------------------------------------------------------------------
    | PhilSMS API Bearer Token
    |--------------------------------------------------------------------------
    |
    | Your PhilSMS API token obtained from your PhilSMS dashboard.
    |
    */

    'api_token' => env('PHILSMS_API_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | PhilSMS Sender ID
    |--------------------------------------------------------------------------
    |
    | Default Sender ID registered on your PhilSMS account.
    | Common default is 'PhilSMS' or your custom approved sender name.
    |
    */

    'sender_id' => env('PHILSMS_SENDER_ID', 'PhilSMS'),

];
