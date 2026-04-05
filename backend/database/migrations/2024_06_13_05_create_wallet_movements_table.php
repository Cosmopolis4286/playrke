<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'wallet_movements'.
     */
    public function up(): void
    {
        Schema::create('wallet_movements', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del movimiento');

            // Billetera que registra el movimiento
            $table->foreignSnowflake('wallet_id')
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID de la billetera');

            // Usuario dueño (desnormalizado para consultas rápidas)
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del usuario dueño de la billetera');

            // Tipo de movimiento
            $table->enum('type', ['DEPOSIT', 'WITHDRAWAL', 'PURCHASE', 'PRIZE'])
                ->comment('Tipo de movimiento');

            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED', 'REJECTED'])
                ->default('PENDING')
                ->comment('Estado del movimiento: PENDIENTE, COMPLETADO, CANCELADO, RECHAZADO');

            // Montos
            $table->decimal('amount', 15, 2)->comment('Monto del movimiento (siempre positivo)');
            $table->decimal('balance_before', 15, 2)->comment('Saldo antes del movimiento');
            $table->decimal('balance_after', 15, 2)->comment('Saldo después del movimiento');

            // Descripción opcional
            $table->string('description', 255)->nullable()->comment('Descripción del movimiento');

            // Referencia externa opcional (ej: id de carton comprado, premio, etc.)
            $table->string('reference_id', 64)->nullable()->index()->comment('ID de referencia externa');

            $table->timestamps();

            // Índices frecuentes
            $table->index(['wallet_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_movements');
    }
};
