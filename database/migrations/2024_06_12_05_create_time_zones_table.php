<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'time_zones'.
     */
    public function up(): void
    {
        Schema::create('time_zones', function (Blueprint $table) {
            // ID autoincremental estándar
            $table->snowflake('id')->primary()->comment('ID único de la zona horaria');

            // Nombre legible y único
            $table->string('name', 60)->unique()
                ->comment('Nombre completo de la zona horaria (ej. America/Sao_Paulo)');

            // Offset UTC
            $table->string('utc', 8)->comment('Offset UTC (ej. UTC-03)');

            // Relación opcional con país
            $table->foreignSnowflake('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('ID del país asociado (opcional)');

            // Estado activo/inactivo
            $table->boolean('is_active')->default(true)
                ->comment('true = activo, false = inactivo');

            // Timestamps automáticos
            $table->timestamps();

            // Índice compuesto opcional para búsquedas rápidas por país y estado
            $table->index(['country_id', 'is_active']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'time_zones'.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_zones');
    }
};
