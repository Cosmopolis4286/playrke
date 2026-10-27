<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración: eliminar tabla 'app_parameters'.
     */
    public function down() : void
    {
        Schema::dropIfExists('app_parameters');
    }

    /**
     * Ejecutar la migración: crear tabla 'app_parameters'.
     */
    public function up() : void
    {
        Schema::create('app_parameters', function(Blueprint $table) {
            // ID Snowflake con el paquete bradietilley/laravel-snowflake
            $table->snowflake('id')->primary()->comment('ID único del parámetro');

            // Parámetro único y legible
            $table->string('param', 50)->unique()
                ->comment('Nombre del parámetro de configuración');
            $table->string('value', 100)->nullable()
                ->comment('Valor del parámetro');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')
                ->comment('Estado del parámetro');

            // Timestamps automáticos
            $table->timestamps();

            // Índice opcional por estado para consultas rápidas
            $table->index(['status', 'param']);
        });
    }
};
