<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'document_types'.
     */
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del tipo de documento');

            // Código único del documento (ej: CPF, DNI, PASSPORT)
            $table->string('code', 15)
                ->unique()
                ->comment('Código corto del documento');

            // Descripción legible
            $table->string('description', 100)
                ->comment('Descripción legible del documento');

            // Relación opcional con país
            $table->foreignSnowflake('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('ID del país asociado (opcional)');

            // Estado activo/inactivo
            $table->boolean('is_active')
                ->default(true)
                ->comment('true = activo, false = inactivo');

            $table->timestamps();

            // Índice compuesto para consultas frecuentes por país y estado
            $table->index(['country_id', 'is_active'], 'idx_document_types_country_status');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'document_types'.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
