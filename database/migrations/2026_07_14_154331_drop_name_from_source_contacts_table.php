<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contact names now resolve from the locally stored entities (characters,
     * corporations, alliances, factions), which the sync keeps up to date.
     */
    public function up(): void
    {
        Schema::table('source_contacts', function (Blueprint $table): void {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('source_contacts', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('contact_type');
        });
    }
};
