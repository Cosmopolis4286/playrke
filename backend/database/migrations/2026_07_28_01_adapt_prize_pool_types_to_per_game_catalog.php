<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `prize_pool_types` (Bote Fijo/Acumulativo/Mixto/Premio) era un catálogo
 * global compartido entre juegos vía la tabla pivot `game_prize_pool_types`
 * (many-to-many). Se simplifica al mismo patrón que `game_modes`: cada tipo
 * de bote pertenece a un único juego (game_id NOT NULL) — no se comparten
 * entre juegos — eliminando la necesidad de la pivot.
 *
 * Backfill: los 4 tipos existentes hoy están asociados exclusivamente a
 * BINGO en game_prize_pool_types (relevado antes de escribir esta
 * migración), así que el backfill es 1:1 sin ambigüedad.
 *
 * `game_prize_pool_types` se elimina: confirmado sin consumidores reales
 * (GamePrizePoolTypeRepository/Contract nunca bindeados ni usados fuera de
 * sí mismos) — el único uso real de "tipo de bote" en el sistema es la FK
 * directa `bingo_rooms_config.prize_pool_type_id` (y equivalentes en
 * bingo_room_templates/bingo_rooms_automatic), que no se ve afectada por
 * este cambio: el id de cada fila de prize_pool_types no cambia.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('prize_pool_types', 'game_id')) {
            Schema::table('prize_pool_types', function (Blueprint $table) {
                $table->foreignSnowflake('game_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('games')
                    ->cascadeOnDelete()
                    ->comment('Juego al que pertenece este tipo de bote');
            });
        }

        // Backfill desde game_prize_pool_types: cada prize_pool_type toma el
        // game_id de su (única) asignación existente. Si un tipo tuviera más
        // de un juego asignado, se toma el primero (no aplica hoy: relevado
        // 1:1 antes de escribir esta migración).
        DB::statement('
            UPDATE prize_pool_types ppt
            INNER JOIN (
                SELECT prize_pool_type_id, MIN(game_id) AS game_id
                FROM game_prize_pool_types
                GROUP BY prize_pool_type_id
            ) gppt ON gppt.prize_pool_type_id = ppt.id
            SET ppt.game_id = gppt.game_id
            WHERE ppt.game_id IS NULL
        ');

        // Cualquier tipo sin asignación previa (huérfano) se asigna a BINGO
        // por defecto en vez de quedar NULL, para poder forzar NOT NULL.
        $bingoId = DB::table('games')->where('name', 'BINGO')->value('id');
        if ($bingoId) {
            DB::table('prize_pool_types')->whereNull('game_id')->update(['game_id' => $bingoId]);
        }

        DB::statement('ALTER TABLE prize_pool_types MODIFY game_id BIGINT UNSIGNED NOT NULL');

        $existingIndexes = collect(DB::select('SHOW INDEX FROM prize_pool_types'))
            ->pluck('Key_name')
            ->unique();

        Schema::table('prize_pool_types', function (Blueprint $table) use ($existingIndexes) {
            if ($existingIndexes->contains('prize_pool_types_code_unique')) {
                $table->dropUnique('prize_pool_types_code_unique');
            }

            if (!$existingIndexes->contains('uniq_prize_pool_types_game_code')) {
                $table->unique(['game_id', 'code'], 'uniq_prize_pool_types_game_code');
            }
        });

        Schema::dropIfExists('game_prize_pool_types');
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Esta migración no es reversible automáticamente: dropea game_prize_pool_types. Restaurar desde backup previo a su ejecución.'
        );
    }
};
