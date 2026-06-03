<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'banks_venezuela'.
     */
    public function up(): void
    {
        if (Schema::hasTable('banks_venezuela')) {
            return;
        }

        Schema::create('banks_venezuela', function (Blueprint $table) {
            // ID Snowflake para consistencia y seguridad
            $table->snowflake('id')->primary()->comment('ID único del banco');

            // Nombre del banco
            $table->string('name', 50)
                ->comment('Nombre del banco');

            // Código único del banco
            $table->string('code', 4)
                ->unique()
                ->comment('Código único del banco');

            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('name', 'idx_banks_name');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'banks_venezuela'.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks_venezuela');
    }
};
