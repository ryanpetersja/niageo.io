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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5-20250929'),
        // Voice assistant (natural-language UI). Effort is only sent when set;
        // leave ANTHROPIC_VOICE_EFFORT empty for models without effort control (e.g. Haiku 4.5).
        'voice_model' => env('ANTHROPIC_VOICE_MODEL', 'claude-opus-5'),
        'voice_effort' => env('ANTHROPIC_VOICE_EFFORT', 'low'),
        // Seconds of silence before the microphone switches itself off (0 = never).
        'voice_idle_seconds' => (int) env('ANTHROPIC_VOICE_IDLE_SECONDS', 15),
    ],

];
