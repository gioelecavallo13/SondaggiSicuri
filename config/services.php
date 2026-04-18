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

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA v3
    |--------------------------------------------------------------------------
    |
    | Verifica server-to-server in fasi successive del piano anti-bot.
    | Fasce score (default piano): < block_below blocco; fino a challenge_max incluso retry/challenge; sopra pass.
    |
    */
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.6),
        'timeout_ms' => (int) env('RECAPTCHA_TIMEOUT_MS', 5000),
        'score_block_below' => 0.3,
        'score_challenge_max' => 0.6,
        // Log diagnostico dopo siteverify OK (solo se APP_ENV=local o true esplicito). Vedi RegisterAntiBotService.
        'log_siteverify_success' => (bool) env('RECAPTCHA_LOG_SITEVERIFY_SUCCESS', false),
    ],

];
