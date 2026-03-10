export type Character = {
    id: number;
    name: string;
    corporation_id: number | null;
    alliance_id: number | null;
};

export type User = {
    id: number;
    name: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    active_character: Character;
    characters: Character[];
};
