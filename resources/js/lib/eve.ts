/**
 * The EVE image-server URL for an entity's portrait/logo, or null for types
 * (e.g. factions) that have no image endpoint.
 */
export function eveImage(type: string, id: number, size = 64): string | null {
    switch (type) {
        case 'character':
            return `https://images.evetech.net/characters/${id}/portrait?size=${size}`;
        case 'corporation':
            return `https://images.evetech.net/corporations/${id}/logo?size=${size}`;
        case 'alliance':
            return `https://images.evetech.net/alliances/${id}/logo?size=${size}`;
        default:
            return null;
    }
}

/**
 * A character portrait URL (always available).
 */
export function characterPortrait(id: number, size = 64): string {
    return `https://images.evetech.net/characters/${id}/portrait?size=${size}`;
}

/**
 * A standing value formatted with an explicit sign (e.g. "+5", "-10", "0").
 */
export function standingLabel(standing: number): string {
    return standing > 0 ? `+${standing}` : `${standing}`;
}

/**
 * The Tailwind text-colour class for a standing, from blue (positive) through
 * muted (neutral) to red (negative).
 */
export function standingTextClass(standing: number): string {
    if (standing >= 5) {
        return 'text-blue-600 dark:text-blue-400';
    }
    if (standing > 0) {
        return 'text-sky-600 dark:text-sky-400';
    }
    if (standing === 0) {
        return 'text-muted-foreground';
    }
    if (standing > -5) {
        return 'text-orange-600 dark:text-orange-400';
    }
    return 'text-red-600 dark:text-red-400';
}
