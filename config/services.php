<?php

return [

    'paymob' => [
        'api_key' => env('PAYMOB_API_KEY'),
        // 'secret_key' => env('PAYMOB_SECRET_KEY'),
        'hmac' => env('PAYMOB_HMAC'),
        'integration_id' => env('PAYMOB_INTEGRATION_ID'),
        'iframe_url' => env('PAYMOB_IFRAME_URL'),
        'notification_url' => env('PAYMOB_NOTIFICATION_URL'),
        'redirect_url' => env('PAYMOB_REDIRECT_URL'),
    ],

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

];
