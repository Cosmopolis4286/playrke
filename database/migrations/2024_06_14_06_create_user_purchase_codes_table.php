<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'user_purchase_codes'.
     */
    public function up(): void
    {
        Schema::create('user_purchase_codes', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del código de compra');

            // Datos del usuario que recibe el código
            $table->string('first_name', 50)->comment('Nombre del usuario');
            $table->string('last_name', 50)->nullable()->comment('Apellido del usuario');
            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('País asociado al usuario');

            $table->string('phone', 14)->comment('Teléfono del usuario');

            // Código de compra
            $table->string('purchase_code', 12)->unique()->comment('Código de compra único');

            // Estado del código
            $table->enum('status', ['ACTIVE', 'USED', 'EXPIRED'])
                ->default('ACTIVE')
                ->comment('Estado del código de compra');

            $table->timestamp('expiration_date')->nullable()->comment('Fecha de expiración del código');

            // Timestamps automáticos
            $table->timestamps();

            // Índices útiles para consultas
            $table->index(['country_id', 'status'], 'idx_country_status');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'user_purchase_codes'.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_purchase_codes');
    }
};
