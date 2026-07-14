<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contact names are moving from source_contacts onto the entity tables.
     * Seed missing character/corporation/alliance rows from the stored names
     * before the column is dropped, so nothing loses its display name; the
     * affiliation sync fills in the details later. Factions ship with the SDE.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('source_contacts', 'name')) {
            return;
        }

        $tables = [
            'character' => 'characters',
            'corporation' => 'corporations',
            'alliance' => 'alliances',
        ];

        $contacts = DB::table('source_contacts')
            ->whereNotNull('name')
            ->get(['contact_id', 'contact_type', 'name']);

        foreach ($contacts as $contact) {
            $table = $tables[$contact->contact_type] ?? null;

            if ($table === null || DB::table($table)->where('id', $contact->contact_id)->exists()) {
                continue;
            }

            DB::table($table)->insert([
                'id' => $contact->contact_id,
                'name' => $contact->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The seeded rows are real EVE entities; keep them.
    }
};
