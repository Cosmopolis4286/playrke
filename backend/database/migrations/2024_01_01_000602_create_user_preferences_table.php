<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración: eliminar tabla 'user_preferences'.
     */
    public function down() : void
    {
        Schema::dropIfExists('user_preferences');
    }

    /**
     * Ejecutar la migración: crear tabla 'user_preferences'.
     */
    public function up() : void
    {
        Schema::create('user_preferences', function(Blueprint $table) {
            // ID primario auto-incremental
            $table->id();

            // Relación con el usuario (nullable para preferencias globales)
            $table->foreignId('user_id')->nullable()
                ->comment('ID del usuario (null para preferencias globales)');

            // Categoría de la preferencia
            $table->string('category', 50)->default('general')
                ->comment('Categoría de la preferencia');

            // Clave de la preferencia
            $table->string('key', 100)
                ->comment('Clave de la preferencia');

            // Valor de la preferencia
            $table->text('value')->nullable()
                ->comment('Valor de la preferencia');

            // Tipo de dato
            $table->enum('type', ['string', 'number', 'boolean', 'json', 'array'])->default('string')
                ->comment('Tipo de dato de la preferencia');

            // Estado
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')
                ->comment('Estado de la preferencia');

            // Orden de visualización
            $table->integer('sort_order')->default(0)
                ->comment('Orden de visualización');

            // Descripción
            $table->text('description')->nullable()
                ->comment('Descripción de la preferencia');

            // Valores permitidos (JSON)
            $table->json('allowed_values')->nullable()
                ->comment('Valores permitidos para la preferencia');

            // Valor por defecto
            $table->text('default_value')->nullable()
                ->comment('Valor por defecto de la preferencia');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índice único compuesto
            $table->unique(['user_id', 'category', 'key']);

            // Índices de rendimiento
            $table->index(['status', 'category']);
            $table->index(['key', 'status']);
        });
    }
};
