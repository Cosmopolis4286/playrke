<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración: eliminar tabla 'treasurers_currencies'.
     */
    public function down() : void
    {
        Schema::dropIfExists('treasurers_currencies');
    }

    /**
     * Ejecutar la migración: crear tabla 'treasurers_currencies'.
     */
    public function up() : void
    {
        Schema::create('treasurers_currencies', function(Blueprint $table) {
            // Tesorero
            $table->foreignSnowflake('treasurer_id')
                ->constrained('treasurers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Tesorero asociado');

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

            // Soft deletes
            $table->softDeletes();

            // Índice único: la PRIMARY KEY compuesta ya cubre la búsqueda por
            // (treasurer_id, currency_id); se elimina el INDEX redundante
            // 'idx_treasurer_currency' que existía sobre las mismas columnas.
            $table->primary(['treasurer_id', 'currency_id']);
        });
    }
};
