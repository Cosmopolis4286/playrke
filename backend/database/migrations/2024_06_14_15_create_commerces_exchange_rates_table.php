<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'commerces_exchange_rates'.
     */
    public function up(): void
    {
        Schema::create('commerces_exchange_rates', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la tasa de cambio');

            // Comercio relacionado
            $table->foreignSnowflake('commerce_id')
                ->constrained('commerces')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Comercio al que aplica la tasa');

            // Moneda relacionada
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

            // Índices compuestos para consultas frecuentes
            $table->index(['commerce_id', 'currency_id']);
            $table->index(['commerce_id', 'status']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'commerces_exchange_rates'.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerces_exchange_rates');
    }
};
