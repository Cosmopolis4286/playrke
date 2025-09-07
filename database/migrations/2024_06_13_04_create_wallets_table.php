<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'wallets'.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la wallet');

            // Relación con usuario usando Snowflake
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del usuario dueño de la wallet');

            // Relación con moneda usando Snowflake
            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID de la moneda asociada');

            // Balance de la wallet
            $table->decimal('balance', 15, 2)
                ->default(0.00)
                ->comment('Balance en moneda local');

            // Estado de la wallet
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE');

            // Timestamps automáticos
            $table->timestamps();

            // Índice compuesto para consultas frecuentes por usuario y moneda
            $table->index(['user_id', 'currency_id']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'wallets'.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
