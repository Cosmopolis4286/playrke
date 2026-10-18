<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'commerces'.
     */
    public function up(): void
    {
        Schema::create('commerces', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del comercio');

            // Datos del comercio
            $table->string('name', 30)->comment('Nombre del comercio');
            $table->decimal('commission', 5, 2)->comment('Comisión (%) por defecto');
            $table->string('phone', 20)->unique()->comment('Teléfono del comercio');

            // Relaciones Snowflake
            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del país del comercio');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del usuario commercio');

            $table->foreignSnowflake('unique_code_id')
                ->constrained('unique_codes')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Código único de referencia');

            // Qué hacer con un pago de cartón de su red que sigue PENDIENTE
            // cuando arranca el sorteo (ver HandlePendingBingoPaymentsAtStartCommand).
            $table->string('pending_payment_policy', 20)
                ->default('keep_review')
                ->comment('Pago pendiente al iniciar el sorteo: reject_refund | keep_review');

            // Estado del comercio
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'])
                ->default('ACTIVE')
                ->comment('Estados del comercio');

            // Timestamps automáticos
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Índices compuestos para consultas frecuentes
            $table->index('user_id');
            $table->index(['country_id', 'status']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'commerces'.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerces');
    }
};
