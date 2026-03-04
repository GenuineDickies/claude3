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

    'telnyx' => [
        'api_key' => env('TELNYX_API_KEY'),
        'from_number' => env('TELNYX_FROM_NUMBER'),
        'messaging_profile_id' => env('TELNYX_MESSAGING_PROFILE_ID'),
        // Base64-encoded ED25519 public key from Telnyx Mission Control.
        'public_key' => env('TELNYX_PUBLIC_KEY'),
        'webhook_tolerance' => (int) env('TELNYX_WEBHOOK_TOLERANCE', 300),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'location' => [
        // Public HTTPS URL to the standalone locate.php on your hosting.
        // e.g. https://yourdomain.com/webhook-proxy/locate.php
        'base_url' => env('LOCATION_BASE_URL'),
    ],

    'document_ai' => [
        'enabled'                => env('DOCUMENT_AI_ENABLED', true),
        'provider'               => env('DOCUMENT_AI_PROVIDER', 'openai'),
        'api_key'                => env('OPENAI_API_KEY'),
        'model'                  => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_file_size_for_vision' => (int) env('DOCUMENT_AI_MAX_VISION_SIZE', 10 * 1024 * 1024),
    ],

];
