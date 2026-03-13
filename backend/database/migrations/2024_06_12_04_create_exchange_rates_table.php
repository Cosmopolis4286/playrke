<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'exchange_rates'.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la tasa de cambio');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->comment('Usuario que registró/actualizó la tasa');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->comment('Moneda asociada a la tasa');

            // Datos de la tasa
            $table->decimal('amount', 12, 4)
                ->default(1)
                ->comment('Valor de cambio de la moneda');

            $table->decimal('percentage', 8, 3)->default(0)->comment('Porcentaje de subida/bajada');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'DELETED', 'OLD', 'DIRECTORY'])
                ->default('ACTIVE')
                ->comment('Estado de la tasa de cambio');

            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Índice compuesto para consultas frecuentes por moneda y valor
            $table->index(['currency_id', 'amount']);
            $table->index('status');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'exchange_rates'.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
