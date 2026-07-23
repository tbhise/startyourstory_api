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

    // Google Sign-In for CA Library students only (SYS auth is untouched).
    // The OAuth Client ID is public by design — it ships in the frontend JS —
    // so there is no client secret here; ID tokens are verified server-side.
    // Leave unset to disable Google sign-in; the UI hides the button.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
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

    'phonepe' => [
        'merchant_id'      => env('PHONEPE_MERCHANT_ID'),
        'client_id'        => env('PHONEPE_CLIENT_ID'),
        'client_secret'    => env('PHONEPE_CLIENT_SECRET'),
        'client_version'   => env('PHONEPE_CLIENT_VERSION', 1),
        'base_url'         => env('PHONEPE_BASE_URL', 'https://api-preprod.phonepe.com/apis/pg-sandbox'),
        'frontend_url'     => env('FRONTEND_URL', env('APP_URL')),
        'webhook_username' => env('PHONEPE_WEBHOOK_USERNAME'),
        'webhook_password' => env('PHONEPE_WEBHOOK_PASSWORD'),
    ],

    // Cashfree PG (Orders API). Sandbox by default; the webhook is verified with
    // the same secret key. Leave unset to keep Cashfree unavailable.
    'cashfree' => [
        'app_id'      => env('CASHFREE_APP_ID'),
        'secret_key'  => env('CASHFREE_SECRET_KEY'),
        'api_version' => env('CASHFREE_API_VERSION', '2023-08-01'),
        'base_url'    => env('CASHFREE_BASE_URL', 'https://sandbox.cashfree.com/pg'),
        'mode'        => env('CASHFREE_MODE', 'sandbox'),
    ],

    // Firebase Cloud Messaging (admin push). Values come from the Firebase
    // service-account JSON. When any are absent, FcmService is a safe no-op.
    'fcm' => [
        'project_id'   => env('FCM_PROJECT_ID'),
        'client_email' => env('FCM_CLIENT_EMAIL'),
        'private_key'  => env('FCM_PRIVATE_KEY'),
        // Used to build absolute click-through links for push notifications.
        'frontend_url' => env('FRONTEND_URL', env('APP_URL')),
    ],

];
