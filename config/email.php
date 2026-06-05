<?php

/*
|--------------------------------------------------------------------------
| Email Sender Identities
|--------------------------------------------------------------------------
|
| Each key maps to a named sender identity. All identities currently point
| to the same address. To separate them in the future, set the environment
| variables below — no business logic changes required.
|
*/

return [

    'senders' => [

        'default' => [
            'address' => env('MAIL_FROM_ADDRESS', 'hello@startyourstory.in'),
            'name'    => env('MAIL_FROM_NAME', 'Start Your Story'),
        ],

        'verify' => [
            'address' => env('MAIL_VERIFY_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@startyourstory.in')),
            'name'    => env('MAIL_VERIFY_NAME', env('MAIL_FROM_NAME', 'Start Your Story')),
        ],

        'interview' => [
            'address' => env('MAIL_INTERVIEW_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@startyourstory.in')),
            'name'    => env('MAIL_INTERVIEW_NAME', env('MAIL_FROM_NAME', 'Start Your Story')),
        ],

        'support' => [
            'address' => env('MAIL_SUPPORT_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@startyourstory.in')),
            'name'    => env('MAIL_SUPPORT_NAME', env('MAIL_FROM_NAME', 'Start Your Story')),
        ],

        'billing' => [
            'address' => env('MAIL_BILLING_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@startyourstory.in')),
            'name'    => env('MAIL_BILLING_NAME', env('MAIL_FROM_NAME', 'Start Your Story')),
        ],

        'marketing' => [
            'address' => env('MAIL_MARKETING_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@startyourstory.in')),
            'name'    => env('MAIL_MARKETING_NAME', env('MAIL_FROM_NAME', 'Start Your Story')),
        ],

    ],

];
