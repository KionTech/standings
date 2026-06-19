<?php

declare(strict_types=1);

use NicolasKion\Esi\Enums\EsiScope;

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

    'discord' => [
        'standing_request_webhook' => env('DISCORD_STANDING_REQUEST_WEBHOOK'),
    ],

    'eveonline' => [
        'client_id' => env('EVEONLINE_CLIENT_ID'),
        'client_secret' => env('EVEONLINE_CLIENT_SECRET'),
        'redirect' => env('EVEONLINE_REDIRECT_URI'),

        /*
         * EVE character_ids of the admins, comma-separated. Anyone owning one of
         * these characters can administer the standings.
         */
        'admin_character_ids' => array_values(array_filter(array_map(
            static fn ($id): int => (int) mb_trim((string) $id),
            explode(',', (string) env('EVE_ADMIN_CHARACTER_ID', '')),
        ))),

        'required_scopes' => [
            EsiScope::PublicData,
            EsiScope::ReadCharacterContacts,
            EsiScope::WriteCharacterContacts,
            EsiScope::ReadCorporationContacts,
            EsiScope::ReadAllianceContacts,
            EsiScope::SendMail,
        ],
    ],

];
