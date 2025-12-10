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
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],

    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'api_key' => env('N8N_API_KEY'),
        'webhook_secret' => env('N8N_WEBHOOK_SECRET'),
        'timeout' => env('N8N_TIMEOUT', 120),
        'rate_limit' => [
            'max_attempts' => env('N8N_RATE_LIMIT_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('N8N_RATE_LIMIT_DECAY_MINUTES', 1),
        ],
        'retry' => [
            'max_attempts' => env('N8N_RETRY_MAX_ATTEMPTS', 3),
            'delay_seconds' => env('N8N_RETRY_DELAY_SECONDS', 5),
            'backoff_multiplier' => env('N8N_RETRY_BACKOFF_MULTIPLIER', 2),
        ],
    ],

];
