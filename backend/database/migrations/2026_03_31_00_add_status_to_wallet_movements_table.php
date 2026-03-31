<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar columna status a wallet_movements.
     */
    public function up(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED', 'REJECTED'])
                ->default('PENDING')
                ->after('type')
                ->comment('Estado del movimiento: PENDIENTE, COMPLETADO, CANCELADO, RECHAZADO');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
