<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            // Campos de ciclo de vida de la sala
            $table->timestamp('started_at')->nullable()->after('scheduled_at');
            $table->timestamp('ended_at')->nullable()->after('started_at');

            // Alias legible para 'config' — se rellena automáticamente via trigger o desde app
            // Preferimos renombrar 'config' a 'settings' para consistencia con el modelo
        });

        // Renombrar config → settings para alinear con el modelo
        DB::connection('pgsql_trivia')->statement(
            'ALTER TABLE trivia_rooms RENAME COLUMN config TO settings'
        );
    }

    public function down(): void
    {
        DB::connection('pgsql_trivia')->statement(
            'ALTER TABLE trivia_rooms RENAME COLUMN settings TO config'
        );

        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'ended_at']);
        });
    }
};
