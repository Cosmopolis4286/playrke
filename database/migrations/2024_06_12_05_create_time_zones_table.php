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
            $table->id()->comment('ID de la zona horaria');
            $table->string('name', 50)->unique()->comment('Nombre completo de la zona horaria (ej. America/Sao_Paulo)');
            $table->string('utc', 8)->comment('Offset UTC (ej. UTC-03)');
            $table->foreignId('country')->nullable()
                ->constrained('countries')->cascadeOnUpdate()
                ->nullOnDelete()->comment('ID del país asociado (opcional)');
            $table->boolean('status')->default(true)->comment('Estado activo/inactivo');
            $table->timestamps();
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
