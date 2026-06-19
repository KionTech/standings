<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger of source contacts this app has applied to each character, so we can
     * delete only what we added once the source drops a contact.
     */
    public function up(): void
    {
        Schema::create('character_synced_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->unsignedBigInteger('contact_id');
            $table->decimal('standing', 4, 2);
            $table->timestamps();

            $table->unique(['character_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_synced_contacts');
    }
};
