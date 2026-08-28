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

    'weatherapi' => [
        'key' => env('WEATHERAPI_KEY'),
        'location' => env('WEATHERAPI_LOCATION', 'Jasaan, Misamis Oriental, Philippines'),
    ],

    'cloudflare' => [
        'turnstile_site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY', '0x4AAAAAAEfCC0KJOL1zsgrX'),
        'turnstile_secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY', '0x4AAAAAAEfCCzHV3kNQl3IPpR1GADxq188'),
    ],

];
