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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama3-8b-8192'),
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'mock'),
        'eskiz_email' => env('ESKIZ_EMAIL'),
        'eskiz_password' => env('ESKIZ_PASSWORD'),
        'play_username' => env('PLAY_MOBILE_USERNAME'),
        'play_password' => env('PLAY_MOBILE_PASSWORD'),
        'sender_name' => env('SMS_SENDER_NAME', 'AgroMind'),
    ],

];

