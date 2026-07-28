<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `game_modes` quedó vacía tras el rename a `connection_types` (ver
 * 2026_07_25_02_migrate_game_mode_to_connection_type.php), reservada para su
 * propósito original: modalidades de participación reales por juego
 * (Solitario, Múltiple, Ranking, etc.), administrables por admins.
 *
 * Cada modo pertenece a un único juego (game_id NOT NULL) — no se comparten
 * modos entre juegos — por lo que no hace falta tabla pivot. `code` deja de
 * ser único global y pasa a ser único por juego.
 *
 * `game_mode_assignment` se elimina: era la pivot para el diseño
 * many-to-many descartado, sin ningún consumidor real (confirmado antes de
 * esta migración — ni bindeada en ServiceProvider ni referenciada por
 * ningún controlador/servicio).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('game_mode_assignment')) {
            Schema::dropIfExists('game_mode_assignment');
        }

        if (!Schema::hasColumn('game_modes', 'game_id')) {
            Schema::table('game_modes', function (Blueprint $table) {
                $table->foreignSnowflake('game_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('games')
                    ->cascadeOnDelete()
                    ->comment('Juego al que pertenece este modo');
            });
        }

        // game_modes quedó vacía por la migración anterior — no hay filas
        // que backfillear, pero por si acaso se corre este código con datos
        // preexistentes de algún entorno no limpiado, se asigna BINGO por
        // defecto a cualquier fila huérfana antes de forzar NOT NULL.
        $bingoId = DB::table('games')->where('name', 'BINGO')->value('id');
        if ($bingoId) {
            DB::table('game_modes')->whereNull('game_id')->update(['game_id' => $bingoId]);
        } else {
            DB::table('game_modes')->whereNull('game_id')->delete();
        }

        DB::statement('ALTER TABLE game_modes MODIFY game_id BIGINT UNSIGNED NOT NULL');

        $existingIndexes = collect(DB::select('SHOW INDEX FROM game_modes'))
            ->pluck('Key_name')
            ->unique();

        Schema::table('game_modes', function (Blueprint $table) use ($existingIndexes) {
            if ($existingIndexes->contains('game_modes_code_unique')) {
                $table->dropUnique('game_modes_code_unique');
            }

            if ($existingIndexes->contains('game_modes_label_unique')) {
                $table->dropUnique('game_modes_label_unique');
            }

            if (!$existingIndexes->contains('uniq_game_modes_game_code')) {
                $table->unique(['game_id', 'code'], 'uniq_game_modes_game_code');
            }

            if (!$existingIndexes->contains('uniq_game_modes_game_label')) {
                $table->unique(['game_id', 'label'], 'uniq_game_modes_game_label');
            }
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Esta migración no es reversible automáticamente: dropea game_mode_assignment. Restaurar desde backup previo a su ejecución.'
        );
    }
};
