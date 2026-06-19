<?php

declare(strict_types=1);

namespace App\Enums;

use NicolasKion\Esi\Enums\EsiScope;

enum StandingsSourceType: string
{
    case Character = 'character';
    case Corporation = 'corporation';
    case Alliance = 'alliance';

    /**
     * The ESI scope the admin character must hold to read this source's contacts.
     */
    public function requiredScope(): EsiScope
    {
        return match ($this) {
            self::Character => EsiScope::ReadCharacterContacts,
            self::Corporation => EsiScope::ReadCorporationContacts,
            self::Alliance => EsiScope::ReadAllianceContacts,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
