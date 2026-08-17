<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula un ExternalPlayer (comprador sin cuenta) con CUALQUIER entidad
     * transaccional de cualquier juego — un cartón de Bingo hoy
     * (BingoCard::class), una entrada/pago de Trivia mañana
     * (TriviaEntry::class o similar), etc.
     *
     * Mismo patrón polimórfico "manual" que audits.auditable_type/id (ver
     * create_audits_table.php): NO se usa morphs() para poder guardar IDs
     * Snowflake sin problemas de precisión JS ni de tipo en MySQL, y para no
     * atar el esquema a la convención de tabla singular de Eloquent morphs.
     *
     * Un ExternalPlayer puede tener N participaciones (varios cartones, o
     * mañana varias entradas de trivia) — de ahí la tabla dedicada en vez de
     * una columna 1:1 en cada tabla de juego (bingo_cards, trivia_entries...).
     */
    public function up(): void
    {
        Schema::create('external_player_participations', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único de la participación');

            $table->foreignSnowflake('external_player_id')
                ->constrained('external_players')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Jugador externo dueño de esta participación');

            // Entidad participable (polimórfica manual, ver docblock de la clase).
            $table->string('participable_type')
                ->comment('Clase FQCN de la entidad de juego (ej. BingoCard)');

            $table->json('participable_id')
                ->comment('ID (Snowflake u otro) de la entidad de juego');

            $table->index('participable_type', 'idx_ext_player_participable_type');

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['external_player_id', 'participable_type'],
                'idx_ext_player_participations_player_type'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_player_participations');
    }
};
