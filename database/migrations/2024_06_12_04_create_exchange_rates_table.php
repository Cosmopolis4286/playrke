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

            // Relación con moneda usando Snowflake
            $table->foreignSnowflake('coin_id')
                ->constrained('coins')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID de la moneda asociada');

            // Valor de la tasa de cambio
            $table->decimal('value', 12, 4)->default(1)->comment('Valor de la moneda respecto a referencia');

            // Timestamps automáticos
            $table->timestamps();

            // Índice compuesto para consultas frecuentes por moneda y valor
            $table->index(['coin_id', 'value']);
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
