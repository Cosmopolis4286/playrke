<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `game_modes` (ONLINE/OFFLINE/MIXED) nunca representó modos de juego reales
 * (reglas: línea, cartón lleno, etc.) sino el canal de conexión de la sala.
 * El nombre confundía a los administradores con el futuro concepto real de
 * "modo de juego". Esta migración traslada esos 3 valores a una tabla nueva
 * `connection_types` y reapunta ahí las FKs de todas las tablas dependientes,
 * dejando `game_modes` vacía y libre para su propósito original a futuro.
 *
 * Tablas con FK real a game_modes.id (relevadas antes de escribir esto):
 * bingo_rooms_config, bingo_room_templates, bingo_rooms_automatic,
 * game_prize_pool_types, currencies_game_rooms, transactions.
 * `game_mode_assignment` queda fuera deliberadamente: no tiene ningún
 * consumidor (ni binding de repositorio, ni controlador) y se deja huérfana.
 */
return new class extends Migration
{
    /**
     * Tablas afectadas: [tabla, nullable].
     */
    private array $affectedTables = [
        ['bingo_rooms_config', false],
        ['bingo_room_templates', true],
        ['bingo_rooms_automatic', false],
        ['game_prize_pool_types', false],
        ['currencies_game_rooms', true],
        ['transactions', false],
    ];

    public function up(): void
    {
        // 1. Copiar el catálogo de game_modes a connection_types, preservando
        // el mismo id (evita tener que traducir ids en el backfill de cada
        // tabla dependiente: basta con renombrar la columna FK).
        DB::statement('
            INSERT INTO connection_types (id, code, label, status, created_at, updated_at)
            SELECT id, code, label, status, created_at, updated_at
            FROM game_modes
            WHERE NOT EXISTS (
                SELECT 1 FROM connection_types WHERE connection_types.id = game_modes.id
            )
        ');

        // 2. Por cada tabla dependiente: agregar connection_type_id, copiar el
        // valor de game_mode_id (mismo id, ver paso 1), apuntar el FK a
        // connection_types, y finalmente eliminar la columna game_mode_id.
        foreach ($this->affectedTables as [$table, $nullable]) {
            if (!Schema::hasColumn($table, 'game_mode_id')) {
                continue;
            }

            if (!Schema::hasColumn($table, 'connection_type_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($nullable) {
                    $column = $blueprint->foreignSnowflake('connection_type_id')
                        ->nullable()
                        ->after('game_mode_id')
                        ->constrained('connection_types')
                        ->comment('Tipo de conexión asignado');

                    if (!$nullable) {
                        $column->restrictOnDelete()->cascadeOnUpdate();
                    } else {
                        $column->nullOnDelete();
                    }
                });
            }

            DB::statement("UPDATE {$table} SET connection_type_id = game_mode_id WHERE connection_type_id IS NULL");

            $existingIndexes = fn () => collect(DB::select("SHOW INDEX FROM {$table}"))
                ->pluck('Key_name')
                ->unique();

            // currencies_game_rooms tenía un unique explícito sobre
            // game_mode_id (uniq_game_mode_room_currency). MySQL exige que la
            // FK de game_mode_id quede respaldada por algún índice hasta el
            // momento en que se dropea esa FK — como ese unique compuesto es
            // uno de los índices que la respaldan, hay que crear primero el
            // índice de reemplazo (sobre connection_type_id) y solo después
            // dropear el viejo, o el DROP falla con error 1553.
            if ($table === 'currencies_game_rooms' && !$existingIndexes()->contains('uniq_connection_type_room_currency')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unique(
                        ['game_id', 'connection_type_id', 'room_id', 'currency_id'],
                        'uniq_connection_type_room_currency'
                    );
                });
            }

            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = 'game_mode_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table]))->pluck('CONSTRAINT_NAME');

            Schema::table($table, function (Blueprint $blueprint) use ($foreignKeys) {
                foreach ($foreignKeys as $foreignKey) {
                    $blueprint->dropForeign($foreignKey);
                }
            });

            $indexes = $existingIndexes();

            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $indexName) {
                    if (str_contains($indexName, 'game_mode')) {
                        $blueprint->dropIndex($indexName);
                    }
                }
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('game_mode_id');
            });

            if (!$nullable) {
                // El FK a connection_types ya fue creado al agregar la columna
                // (arriba); solo falta forzar NOT NULL una vez completado el
                // backfill. MODIFY COLUMN crudo en vez de ->change(): el
                // proyecto no tiene doctrine/dbal instalado, requisito de
                // Blueprint::change().
                DB::statement("ALTER TABLE {$table} MODIFY connection_type_id BIGINT UNSIGNED NOT NULL");
            }
        }

        // 3. Vaciar game_modes: sus 3 filas ya no representan nada en uso;
        // queda lista para un futuro catálogo real de modos de juego.
        DB::table('game_modes')->delete();
    }

    public function down(): void
    {
        // No reversible de forma segura: game_modes fue vaciada en el paso 3
        // y las columnas game_mode_id fueron eliminadas. Restaurar requiere
        // un backup previo a esta migración.
        throw new \RuntimeException(
            'Esta migración no es reversible automáticamente. Restaurar desde backup previo a su ejecución.'
        );
    }
};
