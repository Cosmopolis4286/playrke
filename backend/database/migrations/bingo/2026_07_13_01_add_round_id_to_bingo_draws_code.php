<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * bingo_draws_code no tenía round_id: la secuencia persistida de una sala se
 * identificaba solo por room_id, sin distinguir a qué ronda pertenecía. En
 * salas con múltiples rondas, códigos huérfanos de una ronda anterior (por un
 * fallo entre rondas que impidiera el DELETE de limpieza) podían confundirse
 * con la secuencia de la ronda actual al "resumir" un sorteo interrumpido —
 * BingoDrawService::startDraw() ahora valida la integridad de la secuencia
 * como defensa en profundidad, pero la causa raíz es la falta de esta columna.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bingo_draws_code', 'round_id')) {
            Schema::table('bingo_draws_code', function (Blueprint $table) {
                $table->foreignSnowflake('round_id')
                    ->nullable()
                    ->after('room_id')
                    ->constrained('bingo_rounds')
                    ->cascadeOnDelete()
                    ->comment('Ronda asociada a esta secuencia de sorteo');
            });
        }

        // Backfill best-effort: asigna cada fila existente a la ronda más
        // antigua (round=1) de su sala — es el caso predominante ya que
        // hasta ahora la tabla no distinguía rondas. Filas de salas sin
        // ninguna ronda registrada quedan con round_id NULL.
        DB::statement('
            UPDATE bingo_draws_code bdc
            INNER JOIN (
                SELECT room_id, MIN(id) AS first_round_id
                FROM bingo_rounds
                GROUP BY room_id
            ) br ON br.room_id = bdc.room_id
            SET bdc.round_id = br.first_round_id
            WHERE bdc.round_id IS NULL
        ');

        // Nombres de índice esperados según la migración de creación de la tabla,
        // verificados contra el esquema real antes de dropearlos: en al menos un
        // entorno 'unique_num_per_room' no existía pese a que la migración de
        // creación estaba marcada como ejecutada (drift entre migrations y el
        // esquema real) — sin esta verificación, dropUnique() sobre un índice
        // inexistente lanza 1091 y aborta toda la migración.
        $existingIndexes = collect(DB::select('SHOW INDEX FROM bingo_draws_code'))
            ->pluck('Key_name')
            ->unique();

        // room_id tiene una FK (bingo_draws_code_room_id_foreign) que MySQL exige
        // mantener respaldada por algún índice que empiece por room_id en todo
        // momento — hay que crear los índices nuevos ANTES de dropear los viejos,
        // o el DROP del último índice que respalda la FK falla con error 1553.
        Schema::table('bingo_draws_code', function (Blueprint $table) use ($existingIndexes) {
            if (!$existingIndexes->contains('unique_num_per_room_round')) {
                $table->unique(['room_id', 'round_id', 'num'], 'unique_num_per_room_round');
            }

            if (!$existingIndexes->contains('unique_sym_per_room_round')) {
                $table->unique(['room_id', 'round_id', 'sym'], 'unique_sym_per_room_round');
            }

            if (!$existingIndexes->contains('idx_bingo_draws_code_room_round')) {
                $table->index(['room_id', 'round_id'], 'idx_bingo_draws_code_room_round');
            }
        });

        Schema::table('bingo_draws_code', function (Blueprint $table) use ($existingIndexes) {
            // Los unique constraints originales (room_id+num, room_id+sym) ya
            // no permitirían una segunda secuencia completa por ronda — se
            // reemplazan por las versiones que incluyen round_id creadas arriba.
            if ($existingIndexes->contains('unique_num_per_room')) {
                $table->dropUnique('unique_num_per_room');
            }

            if ($existingIndexes->contains('unique_sym_per_room')) {
                $table->dropUnique('unique_sym_per_room');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bingo_draws_code', function (Blueprint $table) {
            $table->dropUnique('unique_num_per_room_round');
            $table->dropUnique('unique_sym_per_room_round');
            $table->dropIndex('idx_bingo_draws_code_room_round');
            $table->unique(['room_id', 'num'], 'unique_num_per_room');
            $table->unique(['room_id', 'sym'], 'unique_sym_per_room');
            $table->dropConstrainedForeignId('round_id');
        });
    }
};
