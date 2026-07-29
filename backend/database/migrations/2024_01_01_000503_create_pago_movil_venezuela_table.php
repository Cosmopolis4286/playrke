<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'pago_movil_venezuela'.
     */
    public function up(): void
    {
        Schema::create('pago_movil_venezuela', function (Blueprint $table) {
            // ID seguro tipo Snowflake para consistencia con el resto de tablas
            $table->snowflake('id')->primary()->comment('ID único del registro de pago móvil');

            $table->string('holder_name', 100)
                ->nullable()
                ->comment('Nombre del titular de la cuenta');

            // Tipo de documento
            $table->foreignSnowflake('document_type_id')
                ->constrained('document_types')
                ->cascadeOnUpdate()
                ->comment('Tipo de documento del titular');

            // Número de documento
            $table->string('document_number', 15)
                ->index('idx_document')
                ->comment('Número de documento del titular');

            // Banco
            $table->foreignSnowflake('bank_id')
                ->constrained('banks_venezuela')
                ->cascadeOnUpdate()
                ->comment('Banco asociado al pago móvil');

            // Teléfono
            $table->string('phone', 15)
                ->index('idx_phone')
                ->comment('Teléfono asociado al pago móvil');

            // Usuario
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario propietario del registro');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'])
                ->default('ACTIVE')
                ->comment('Estados del registro');

            $table->timestamps();

            // Índices compuestos opcionales si se consultan frecuentemente por usuario + banco
            $table->index(['user_id', 'bank_id'], 'idx_user_bank');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'pago_movil_venezuela'.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_movil_venezuela');
    }
};
