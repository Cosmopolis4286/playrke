<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::table('audits', function(Blueprint $table) {
            if ($this->indexExists('audits', 'audits_created_at_index')) {
                $table->dropIndex(['created_at']);
            }
            if ($this->indexExists('audits', 'audits_auditable_type_created_at_index')) {
                $table->dropIndex(['auditable_type', 'created_at']);
            }
        });
    }

    public function up() : void
    {
        Schema::table('audits', function(Blueprint $table) {
            // Toda consulta de listado ordena por latest('created_at').
            if (! $this->indexExists('audits', 'audits_created_at_index')) {
                $table->index('created_at');
            }

            // Caso de uso dominante: "historial de esta entidad" (deep-link
            // desde /admin/users, etc.) — filtra por auditable_type y ordena
            // por created_at. El índice individual de auditable_type ya
            // existente solo cubre parcialmente ese patrón.
            if (! $this->indexExists('audits', 'audits_auditable_type_created_at_index')) {
                $table->index(['auditable_type', 'created_at']);
            }
        });
    }

    /**
     * Guard de idempotencia para índices — Schema::hasColumn() no aplica
     * aquí (no son columnas nuevas), así que se consulta
     * information_schema.statistics directo. Sin esto, un migrate:fresh que
     * corre en paralelo con otra sesión que ya deja el índice físicamente
     * creado pero sin registrar en `migrations` (crash a mitad de camino,
     * RefreshDatabase de otra sesión pisando el mismo schema) revienta con
     * "Duplicate key name" y aborta toda la corrida de tests antes de
     * llegar al seeder — mismo problema ya visto con receipt_hash.
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
