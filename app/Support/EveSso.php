<?php

declare(strict_types=1);

namespace App\Support;

use NicolasKion\Esi\Enums\EsiScope;

final class EveSso
{
    /**
     * The login URL that re-authenticates a character onto the current
     * account with the given config scope set (e.g.
     * services.eveonline.sync_scopes).
     */
    public static function grantScopesUrl(string $configKey): string
    {
        return route('login', [
            'add_to_account' => 1,
            'scopes' => implode(',', array_map(
                static fn (EsiScope $scope): string => $scope->value,
                config($configKey, []),
            )),
        ]);
    }
}
