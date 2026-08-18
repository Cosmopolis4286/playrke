<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_logs', function (Blueprint $table) {
            /**
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

        Schema::table('system_logs', function (Blueprint $table) {
            $table->unique('fingerprint');

            /**
             * Cubre el patrón de consulta dominante de SystemLogService:
             * ORDER BY last_seen_at DESC combinado con filtro por level — sin
             * esto, last_seen_at (la columna de ORDER BY por defecto) no tenía
             * ningún índice propio.
             */
            $table->index(['level', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropUnique(['fingerprint']);
            $table->dropIndex(['level', 'last_seen_at']);
        });

        Schema::table('system_logs', function (Blueprint $table) {
            $table->index('fingerprint');
        });
    }
};
