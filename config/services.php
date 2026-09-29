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

    'philsms' => [
        // Off by default so development never spends SMS credits; messages
        // are written to the log instead. Set SMS_ENABLED=true in production.
        'enabled' => env('SMS_ENABLED', false),
        'token' => env('PHILSMS_TOKEN'),
        'sender_id' => env('PHILSMS_SENDER_ID', 'PhilSMS'),
    ],

    'osm' => [
        // Nominatim's usage policy (operations.osmfoundation.org/policies/nominatim)
        // requires a genuine, identifying contact in the User-Agent. A missing or
        // placeholder value gets requests blocked outright (HTTP 403).
        'contact' => env('OSM_CONTACT_EMAIL'),
    ],

    'libreoffice' => [
        // Path/command for the LibreOffice CLI used to convert merged .docx
        // document-type templates to PDF with full layout fidelity (floating
        // images, headers, etc.) that PhpWord's own PDF writer can't render.
        'binary' => env('LIBREOFFICE_BINARY', 'soffice'),
        'timeout' => env('LIBREOFFICE_TIMEOUT', 120),
    ],

];
