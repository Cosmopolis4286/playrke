<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'commerces_countries_coins'.
     */
    public function up(): void
    {
        Schema::create('commerces_countries_coins', function (Blueprint $table) {
            // ID tipo Snowflake para consistencia
            $table->snowflake('id')->primary()->comment('ID único del registro');

            // Comercio
            $table->foreignId('commerce_id')
                ->constrained('commerces')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Comercio asociado');

            // País (opcional)
            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->comment('País asociado (opcional)');

            // Moneda
            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda asociada');

            // Nivel de asignación
            $table->tinyInteger('level')
                ->default(1)
                ->comment('Nivel asignado: 1 = básico, 2 = intermedio, 3 = avanzado');

            // Usuario creador
            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario creador del registro');

            // Estado
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado del registro');

            $table->timestamps();

            // Índices para optimizar búsquedas frecuentes
            $table->index(['commerce_id', 'country_id', 'coin_id'], 'idx_commerce_country_coin');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'commerces_countries_coins'.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerces_countries_coins');
    }
};
