<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->string('character_owner_hash')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'character_owner_hash']);
        });
    }
};
