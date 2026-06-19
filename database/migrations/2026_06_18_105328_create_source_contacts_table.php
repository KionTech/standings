<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The canonical contacts fetched from the standings source.
     */
    public function up(): void
    {
        Schema::create('source_contacts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->string('contact_type');
            $table->decimal('standing', 4, 2);
            $table->timestamps();

            $table->unique('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_contacts');
    }
};
