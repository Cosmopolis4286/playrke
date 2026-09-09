<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guard de idempotencia para índices — Schema::hasColumn() no aplica
     * (no son columnas nuevas). Sin esto, un migrate:fresh que corre en
     * paralelo con otra sesión que ya deja el índice físicamente creado
     * pero sin registrar en `migrations` revienta con "Duplicate key name"
     * y aborta toda la corrida de tests antes de llegar al seeder — mismo
     * problema ya visto con receipt_hash.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    /**
     * Ejecutar la migración: agregar índice compuesto (type, status).
     *
     * Soporta el WHERE type IN (...) AND status = 'PENDING' de
     * SidebarMenuService::pendingWalletMovementsCount(), que hoy solo cuenta
     * con los índices (wallet_id, created_at) y (user_id, type).
     */
    public function up(): void
    {
        if ($this->indexExists('wallet_movements', 'wallet_movements_type_status_index')) {
            return;
        }

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->index(['type', 'status']);
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        if (! $this->indexExists('wallet_movements', 'wallet_movements_type_status_index')) {
            return;
        }

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
        });
    }
};
