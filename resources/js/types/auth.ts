export type EntitySummary = {
    id: number;
    name: string | null;
    ticker: string | null;
};

export type Character = {
    id: number;
    name: string;
    corporation_id: number | null;
    alliance_id: number | null;
    corporation: EntitySummary | null;
    alliance: EntitySummary | null;
};

export type User = {
    id: number;
    name: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    is_admin: boolean;
    source_unreadable: boolean;
    reauth_characters: { id: number; name: string | null }[];
    active_character: Character;
    characters: Character[];
};
