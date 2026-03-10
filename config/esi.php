<?php

declare(strict_types=1);

return [
    /** @phpstan-ignore larastan.noEnvCallsOutsideOfConfig  */
    'user_agent' => env('APP_NAME'),
    /** @phpstan-ignore larastan.noEnvCallsOutsideOfConfig  */
    'client_id' => env('EVEONLINE_CLIENT_ID'),
    /** @phpstan-ignore larastan.noEnvCallsOutsideOfConfig  */
    'client_secret' => env('EVEONLINE_CLIENT_SECRET'),
    'retry_policy' => [
        'tries' => 5,
        'delay' => 5000,
    ],
    'base_url' => 'https://esi.evetech.net',
    'compatibility_date' => '2025-08-26',
];
