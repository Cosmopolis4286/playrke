<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'sellers'.
     */
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del vendedor');

            // Datos del vendedor
            $table->string('name', 30)->comment('Nombre del vendedor');
            $table->string('phone', 14)->nullable()->comment('Teléfono de contacto');
            $table->decimal('commission', 5, 2)->default(0.00)->comment('Comisión (%)');

            // Relaciones Snowflake
            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->comment('País del vendedor');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->comment('Moneda utilizada por el vendedor');

            $table->foreignSnowflake('commerce_id')
                ->nullable()
                ->constrained('commerces')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Comercio asociado (opcional)');

            $table->foreignSnowflake('subsidiary_id')
                ->nullable()
                ->constrained('subsidiaries')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Sucursal asociada (opcional)');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('ID del usuario');

            // Estado del vendedor
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado activo/inactivo');

            // Timestamps automáticos
            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['status', 'commerce_id', 'subsidiary_id']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'sellers'.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
