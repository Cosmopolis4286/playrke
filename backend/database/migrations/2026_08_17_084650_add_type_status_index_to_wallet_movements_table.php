<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: agregar índice compuesto (type, status).
     *
     * Soporta el WHERE type IN (...) AND status = 'PENDING' de
     * SidebarMenuService::pendingWalletMovementsCount(), que hoy solo cuenta
     * con los índices (wallet_id, created_at) y (user_id, type).
     */
    public function up(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->index(['type', 'status']);
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
        });
    }
};
