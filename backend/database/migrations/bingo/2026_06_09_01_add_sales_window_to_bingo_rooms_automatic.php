<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            // Mínimo de cartones vendidos para que la sala pueda iniciar su juego.
            // null = sin condición.
            $table->unsignedSmallInteger('min_cards_to_start')
                ->nullable()
                ->after('jackpot_pct')
                ->comment('Cartones mínimos necesarios para iniciar el juego (null = sin condición)');

            // Días de anticipación con que se publica la sala antes de que inicie el juego.
            // null = la sala se publica y juega el mismo día (comportamiento legacy).
            // Ej: 3 → la sala se crea 3 días antes de la hora programada en `time`.
            $table->unsignedTinyInteger('sales_start_offset_days')
                ->nullable()
                ->after('min_cards_to_start')
                ->comment('Días de antelación que se publica la sala antes de que inicie el juego (null = mismo día)');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->dropColumn(['min_cards_to_start', 'sales_start_offset_days']);
        });
    }
};
