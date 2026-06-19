<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The single source whose contacts are mirrored onto registered characters.
     */
    public function up(): void
    {
        Schema::create('standings_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->unsignedBigInteger('entity_id');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standings_sources');
    }
};
