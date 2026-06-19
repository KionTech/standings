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
