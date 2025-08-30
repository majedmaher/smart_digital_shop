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

    'zoho' => [
        'client_id'     => env('ZOHO_CLIENT_ID'),
        'client_secret' => env('ZOHO_CLIENT_SECRET'),
        'redirect_uri'  => env('ZOHO_REDIRECT_URI'),
        'accounts_url'  => env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.sa'),
        'api_base'      => env('ZOHO_API_BASE', 'https://www.zohoapis.sa'),
        'org_id'        => env('ZOHO_BOOKS_ORG_ID'),
    ],


    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URL'),
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
