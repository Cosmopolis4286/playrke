<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        if ($this->indexExists('system_logs', 'system_logs_fingerprint_unique')) {
            Schema::table('system_logs', function(Blueprint $table) {
                $table->dropUnique(['fingerprint']);
            });
        }

        if ($this->indexExists('system_logs', 'system_logs_level_last_seen_at_index')) {
            Schema::table('system_logs', function(Blueprint $table) {
                $table->dropIndex(['level', 'last_seen_at']);
            });
        }

        if (! $this->indexExists('system_logs', 'system_logs_fingerprint_index')) {
            Schema::table('system_logs', function(Blueprint $table) {
                $table->index('fingerprint');
            });
        }
    }

    public function up() : void
    {
        // Ya migrado (el unique ya existe) — no repetir el drop+create.
        if ($this->indexExists('system_logs', 'system_logs_fingerprint_unique')) {
            return;
        }

        if ($this->indexExists('system_logs', 'system_logs_fingerprint_index')) {
            Schema::table('system_logs', function(Blueprint $table) {
                /*
                 * fingerprint pasa de index() a unique() — DatabaseLogHandler::write()
                 * asume que el fingerprint identifica una única fila (para agrupar
                 * ocurrencias repetidas vía occurrences++), pero sin esta restricción
                 * a nivel de BD, dos escrituras concurrentes con el mismo fingerprint
                 * (ej. dos workers de queue fallando al mismo tiempo con el mismo
                 * error) podían pasar ambas el where(...)->first() antes de que
                 * cualquiera terminara su create(), generando dos filas en vez de
                 * una. MySQL permite múltiples NULL en una columna unique() sin
                 * conflicto, así que los logs sin fingerprint calculable no se ven
                 * afectados.
                 */
                $table->dropIndex(['fingerprint']);
            });
        }

        Schema::table('system_logs', function(Blueprint $table) {
            $table->unique('fingerprint');
        });

        if (! $this->indexExists('system_logs', 'system_logs_level_last_seen_at_index')) {
            Schema::table('system_logs', function(Blueprint $table) {
                /*
                 * Cubre el patrón de consulta dominante de SystemLogService:
                 * ORDER BY last_seen_at DESC combinado con filtro por level — sin
                 * esto, last_seen_at (la columna de ORDER BY por defecto) no tenía
                 * ningún índice propio.
                 */
                $table->index(['level', 'last_seen_at']);
            });
        }
    }

    /**
     * Guard de idempotencia para índices — Schema::hasColumn() no aplica
     * (no son columnas nuevas). Sin esto, un migrate:fresh que corre en
     * paralelo con otra sesión que ya deja el índice físicamente creado
     * pero sin registrar en `migrations` revienta con "Duplicate key name"
     * (o, al revés, "check that column/key exists" en el dropIndex) y
     * aborta toda la corrida de tests antes de llegar al seeder — mismo
     * problema ya visto con receipt_hash.
     */
    private function indexExists(string $table, string $indexName) : bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
