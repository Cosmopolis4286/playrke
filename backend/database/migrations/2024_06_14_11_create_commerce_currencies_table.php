<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'commerces_currencies'.
     */
    public function up(): void
    {
        Schema::create('commerces_currencies', function (Blueprint $table) {
            // Comercio
            $table->foreignSnowflake('commerce_id')
                ->constrained('commerces')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Comercio asociado');

            // Moneda
            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda asociada');

            // Nivel de asignación
            $table->tinyInteger('level')
                ->default(1)
                ->comment('Nivel asignado: 1 = básico, 2 = intermedio, 3 = avanzado');

            // Estado
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado del registro');

            $table->timestamps();

            // Índices para optimizar búsquedas frecuentes
            $table->primary(['commerce_id', 'currency_id']);
            $table->index(['commerce_id', 'currency_id'], 'idx_commerce_currency');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'commerces_currencies'.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerces_currencies');
    }
};
